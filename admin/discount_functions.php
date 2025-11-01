<?php
/**
 * discount_functions.php
 * Complete Discount & Promotion Helper Functions
 * Include this file wherever you need discount calculations
 * 
 * Usage: include 'discount_functions.php';
 */

/**
 * Validate and apply promotion code to cart
 * 
 * @param string $code - Promotion code entered by user
 * @param float $cartTotal - Total cart amount before discount
 * @param array $cartItems - Array of cart items with product_id, quantity, price
 * @param mysqli $conn - Database connection
 * @param int $userId - User ID (optional, for usage tracking)
 * @return array - ['success' => bool, 'discount' => float, 'message' => string, 'promotion' => array]
 */
function applyPromotionCode($code, $cartTotal, $cartItems, $conn, $userId = null) {
    $code = strtoupper(trim($code));
    $result = [
        'success' => false,
        'discount' => 0,
        'message' => '',
        'promotion' => null,
        'free_shipping' => false
    ];
    
    // Fetch promotion
    $stmt = $conn->prepare("SELECT * FROM promotions WHERE code = ? AND status = 'active'");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $promotion = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$promotion) {
        $result['message'] = "Invalid or inactive promotion code";
        return $result;
    }
    
    // Check date validity
    $now = date('Y-m-d H:i:s');
    if ($now < $promotion['start_date']) {
        $result['message'] = "This promotion hasn't started yet. Valid from " . date('M d, Y', strtotime($promotion['start_date']));
        return $result;
    }
    if ($now > $promotion['end_date']) {
        $result['message'] = "This promotion has expired on " . date('M d, Y', strtotime($promotion['end_date']));
        return $result;
    }
    
    // Check usage limit
    if ($promotion['usage_limit'] && $promotion['times_used'] >= $promotion['usage_limit']) {
        $result['message'] = "This promotion has reached its usage limit";
        return $result;
    }
    
    // Check per-customer usage limit
    if ($userId) {
        $stmt = $conn->prepare("SELECT COUNT(*) as usage_count FROM promotion_usage WHERE promotion_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $promotion['id'], $userId);
        $stmt->execute();
        $usage = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($usage['usage_count'] >= $promotion['usage_per_customer']) {
            $result['message'] = "You've already used this promotion the maximum number of times allowed";
            return $result;
        }
    }
    
    // Check minimum purchase amount
    if ($cartTotal < $promotion['min_purchase_amount']) {
        $result['message'] = "Minimum purchase of $" . number_format($promotion['min_purchase_amount'], 2) . " required for this promotion";
        return $result;
    }
    
    // Check product/category eligibility
    if ($promotion['apply_to'] !== 'all') {
        $eligibleTotal = getEligibleCartTotal($promotion['id'], $cartItems, $conn);
        if ($eligibleTotal <= 0) {
            $result['message'] = "No eligible items in cart for this promotion";
            return $result;
        }
        // Use eligible total for percentage calculations
        $cartTotal = $eligibleTotal;
    }
    
    // Calculate discount based on type
    $discount = 0;
    
    switch ($promotion['discount_type']) {
        case 'percentage':
            $discount = $cartTotal * ($promotion['discount_value'] / 100);
            if ($promotion['max_discount_amount'] && $discount > $promotion['max_discount_amount']) {
                $discount = $promotion['max_discount_amount'];
            }
            break;
            
        case 'fixed_amount':
            $discount = min($promotion['discount_value'], $cartTotal);
            break;
            
        case 'free_shipping':
            $discount = 0; // Shipping discount handled separately
            $result['free_shipping'] = true;
            break;
            
        case 'bogo':
            $discount = calculateBOGODiscount($cartItems, $promotion, $conn);
            break;
    }
    
    $result['success'] = true;
    $result['discount'] = round($discount, 2);
    $result['message'] = "Promotion '" . $promotion['name'] . "' applied successfully!";
    $result['promotion'] = $promotion;
    
    return $result;
}

/**
 * Get total of eligible cart items for a promotion
 * 
 * @param int $promotionId
 * @param array $cartItems
 * @param mysqli $conn
 * @return float - Total of eligible items
 */
function getEligibleCartTotal($promotionId, $cartItems, $conn) {
    $stmt = $conn->prepare("SELECT entity_type, entity_id FROM promotion_eligibility WHERE promotion_id = ?");
    $stmt->bind_param("i", $promotionId);
    $stmt->execute();
    $eligibility = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    if (empty($eligibility)) return 0;
    
    $eligibleTotal = 0;
    
    foreach ($cartItems as $item) {
        // Get product details
        $stmt = $conn->prepare("SELECT category, id FROM products WHERE id = ?");
        $stmt->bind_param("i", $item['product_id']);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$product) continue;
        
        $isEligible = false;
        
        foreach ($eligibility as $rule) {
            if ($rule['entity_type'] === 'product' && $rule['entity_id'] == $item['product_id']) {
                $isEligible = true;
                break;
            }
            if ($rule['entity_type'] === 'category') {
                // Get category ID from name
                $stmt = $conn->prepare("SELECT id FROM categories WHERE category_name = ?");
                $stmt->bind_param("s", $product['category']);
                $stmt->execute();
                $catResult = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                if ($catResult && $catResult['id'] == $rule['entity_id']) {
                    $isEligible = true;
                    break;
                }
            }
        }
        
        if ($isEligible) {
            $eligibleTotal += $item['price'] * $item['quantity'];
        }
    }
    
    return $eligibleTotal;
}

/**
 * Check if cart items are eligible for promotion
 * 
 * @param int $promotionId
 * @param array $cartItems
 * @param mysqli $conn
 * @return bool
 */
function checkPromotionEligibility($promotionId, $cartItems, $conn) {
    return getEligibleCartTotal($promotionId, $cartItems, $conn) > 0;
}

/**
 * Calculate BOGO (Buy One Get One) discount
 * Gives the cheapest item for free when buying 2 or more eligible items
 * 
 * @param array $cartItems
 * @param array $promotion
 * @param mysqli $conn
 * @return float
 */
function calculateBOGODiscount($cartItems, $promotion, $conn) {
    $eligibleItems = [];
    
    // If promotion applies to specific items, filter them
    if ($promotion['apply_to'] !== 'all') {
        $stmt = $conn->prepare("SELECT entity_type, entity_id FROM promotion_eligibility WHERE promotion_id = ?");
        $stmt->bind_param("i", $promotion['id']);
        $stmt->execute();
        $eligibility = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        foreach ($cartItems as $item) {
            $stmt = $conn->prepare("SELECT category FROM products WHERE id = ?");
            $stmt->bind_param("i", $item['product_id']);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            foreach ($eligibility as $rule) {
                if ($rule['entity_type'] === 'product' && $rule['entity_id'] == $item['product_id']) {
                    $eligibleItems[] = $item;
                    break;
                }
                if ($rule['entity_type'] === 'category') {
                    $stmt = $conn->prepare("SELECT id FROM categories WHERE category_name = ?");
                    $stmt->bind_param("s", $product['category']);
                    $stmt->execute();
                    $catResult = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    if ($catResult && $catResult['id'] == $rule['entity_id']) {
                        $eligibleItems[] = $item;
                        break;
                    }
                }
            }
        }
    } else {
        $eligibleItems = $cartItems;
    }
    
    // Need at least 2 items for BOGO
    if (count($eligibleItems) < 2) return 0;
    
    // Find cheapest item
    $prices = array_map(function($item) {
        return $item['price'];
    }, $eligibleItems);
    
    sort($prices);
    return $prices[0]; // Cheapest item is free
}

/**
 * Calculate product-specific discount (from discount_percentage field)
 * 
 * @param int $productId
 * @param float $price
 * @param mysqli $conn
 * @return array - ['discounted_price' => float, 'discount_amount' => float, 'discount_percentage' => float]
 */
function calculateProductDiscount($productId, $price, $conn) {
    $stmt = $conn->prepare("SELECT discount_percentage, discount_start_date, discount_end_date 
                           FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$product || $product['discount_percentage'] <= 0) {
        return [
            'discounted_price' => $price, 
            'discount_amount' => 0,
            'discount_percentage' => 0
        ];
    }
    
    // Check date validity
    $now = date('Y-m-d H:i:s');
    if ($product['discount_start_date'] && $now < $product['discount_start_date']) {
        return [
            'discounted_price' => $price, 
            'discount_amount' => 0,
            'discount_percentage' => 0
        ];
    }
    if ($product['discount_end_date'] && $now > $product['discount_end_date']) {
        return [
            'discounted_price' => $price, 
            'discount_amount' => 0,
            'discount_percentage' => 0
        ];
    }
    
    $discountAmount = $price * ($product['discount_percentage'] / 100);
    $discountedPrice = $price - $discountAmount;
    
    return [
        'discounted_price' => round($discountedPrice, 2),
        'discount_amount' => round($discountAmount, 2),
        'discount_percentage' => $product['discount_percentage']
    ];
}

/**
 * Calculate cart total with all applicable discounts
 * 
 * @param array $cartItems - Array of items with product_id, quantity, price
 * @param mysqli $conn
 * @param string $promoCode - Optional promotion code
 * @param int $userId - Optional user ID
 * @return array - Complete breakdown of pricing
 */
function calculateCartTotals($cartItems, $conn, $promoCode = null, $userId = null) {
    $subtotal = 0;
    $productDiscounts = 0;
    $itemsWithDiscounts = [];
    
    // Calculate product-level discounts
    foreach ($cartItems as $item) {
        $discountInfo = calculateProductDiscount($item['product_id'], $item['price'], $conn);
        $itemTotal = $discountInfo['discounted_price'] * $item['quantity'];
        $itemDiscount = $discountInfo['discount_amount'] * $item['quantity'];
        
        $subtotal += $itemTotal;
        $productDiscounts += $itemDiscount;
        
        $itemsWithDiscounts[] = array_merge($item, [
            'original_price' => $item['price'],
            'discounted_price' => $discountInfo['discounted_price'],
            'discount_amount' => $itemDiscount,
            'line_total' => $itemTotal
        ]);
    }
    
    $promoDiscount = 0;
    $promoDetails = null;
    $freeShipping = false;
    
    // Apply promotion code if provided
    if ($promoCode) {
        $promoResult = applyPromotionCode($promoCode, $subtotal, $itemsWithDiscounts, $conn, $userId);
        if ($promoResult['success']) {
            $promoDiscount = $promoResult['discount'];
            $promoDetails = $promoResult['promotion'];
            $freeShipping = $promoResult['free_shipping'];
        }
    }
    
    $totalDiscount = $productDiscounts + $promoDiscount;
    $finalTotal = $subtotal - $promoDiscount;
    
    return [
        'items' => $itemsWithDiscounts,
        'subtotal' => round($subtotal, 2),
        'product_discounts' => round($productDiscounts, 2),
        'promo_discount' => round($promoDiscount, 2),
        'total_discount' => round($totalDiscount, 2),
        'final_total' => round($finalTotal, 2),
        'promo_details' => $promoDetails,
        'free_shipping' => $freeShipping
    ];
}

/**
 * Record promotion usage (call this after order is placed successfully)
 * 
 * @param int $promotionId
 * @param int $userId
 * @param int $orderId
 * @param float $discountAmount
 * @param mysqli $conn
 * @return bool
 */
function recordPromotionUsage($promotionId, $userId, $orderId, $discountAmount, $conn) {
    // Insert usage record
    $stmt = $conn->prepare("INSERT INTO promotion_usage (promotion_id, user_id, order_id, discount_amount) 
                           VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiid", $promotionId, $userId, $orderId, $discountAmount);
    $success = $stmt->execute();
    $stmt->close();
    
    if (!$success) return false;
    
    // Increment times_used counter
    $stmt = $conn->prepare("UPDATE promotions SET times_used = times_used + 1 WHERE id = ?");
    $stmt->bind_param("i", $promotionId);
    $stmt->execute();
    $stmt->close();
    
    return true;
}

/**
 * Get all active promotions for display (e.g., on homepage banner)
 * 
 * @param mysqli $conn
 * @param int $limit - Maximum number of promotions to return
 * @return array
 */
function getActivePromotions($conn, $limit = 5) {
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("SELECT * FROM promotions 
                           WHERE status = 'active' 
                           AND start_date <= ? 
                           AND end_date >= ? 
                           AND (usage_limit IS NULL OR times_used < usage_limit)
                           ORDER BY discount_value DESC
                           LIMIT ?");
    $stmt->bind_param("ssi", $now, $now, $limit);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $result;
}

/**
 * Get promotion details by code
 * 
 * @param string $code
 * @param mysqli $conn
 * @return array|null
 */
function getPromotionByCode($code, $conn) {
    $code = strtoupper(trim($code));
    $stmt = $conn->prepare("SELECT * FROM promotions WHERE code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result;
}

/**
 * Check if free shipping applies based on promotion code
 * 
 * @param string $promotionCode
 * @param mysqli $conn
 * @return bool
 */
function checkFreeShipping($promotionCode, $conn) {
    if (!$promotionCode) return false;
    
    $code = strtoupper(trim($promotionCode));
    $now = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare("SELECT id FROM promotions 
                           WHERE code = ? 
                           AND status = 'active' 
                           AND discount_type = 'free_shipping'
                           AND start_date <= ?
                           AND end_date >= ?");
    $stmt->bind_param("sss", $code, $now, $now);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return (bool)$result;
}

/**
 * Get products on sale (with active product discounts)
 * 
 * @param mysqli $conn
 * @param int $limit - Maximum number of products
 * @return array
 */
function getProductsOnSale($conn, $limit = 10) {
    $now = date('Y-m-d H:i:s');
    $query = "SELECT p.*, 
              (p.price * (p.discount_percentage / 100)) as discount_amount,
              (p.price - (p.price * (p.discount_percentage / 100))) as sale_price
              FROM products p
              WHERE p.status = 'active' 
              AND p.discount_percentage > 0
              AND (p.discount_start_date IS NULL OR p.discount_start_date <= ?)
              AND (p.discount_end_date IS NULL OR p.discount_end_date >= ?)
              ORDER BY p.discount_percentage DESC
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $now, $now, $limit);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $result;
}

/**
 * Validate if a promotion can be applied (without actually applying it)
 * Useful for showing available promotions to users
 * 
 * @param string $code
 * @param float $cartTotal
 * @param mysqli $conn
 * @param int $userId
 * @return array - ['valid' => bool, 'message' => string]
 */
function validatePromotionCode($code, $cartTotal, $conn, $userId = null) {
    $code = strtoupper(trim($code));
    $result = ['valid' => false, 'message' => ''];
    
    $promotion = getPromotionByCode($code, $conn);
    
    if (!$promotion) {
        $result['message'] = "Invalid promotion code";
        return $result;
    }
    
    if ($promotion['status'] !== 'active') {
        $result['message'] = "This promotion is not active";
        return $result;
    }
    
    $now = date('Y-m-d H:i:s');
    if ($now < $promotion['start_date'] || $now > $promotion['end_date']) {
        $result['message'] = "This promotion is not valid at this time";
        return $result;
    }
    
    if ($promotion['usage_limit'] && $promotion['times_used'] >= $promotion['usage_limit']) {
        $result['message'] = "This promotion has reached its usage limit";
        return $result;
    }
    
    if ($cartTotal < $promotion['min_purchase_amount']) {
        $result['message'] = "Minimum purchase of $" . number_format($promotion['min_purchase_amount'], 2) . " required";
        return $result;
    }
    
    $result['valid'] = true;
    $result['message'] = "Promotion is valid!";
    return $result;
}

/**
 * Format discount description for display
 * 
 * @param array $promotion
 * @return string
 */
function formatDiscountDescription($promotion) {
    $description = "";
    
    switch ($promotion['discount_type']) {
        case 'percentage':
            $description = $promotion['discount_value'] . "% off";
            if ($promotion['max_discount_amount']) {
                $description .= " (max $" . number_format($promotion['max_discount_amount'], 2) . ")";
            }
            break;
        case 'fixed_amount':
            $description = "$" . number_format($promotion['discount_value'], 2) . " off";
            break;
        case 'bogo':
            $description = "Buy One Get One Free";
            break;
        case 'free_shipping':
            $description = "Free Shipping";
            break;
    }
    
    if ($promotion['min_purchase_amount'] > 0) {
        $description .= " on orders over $" . number_format($promotion['min_purchase_amount'], 2);
    }
    
    return $description;
}

/**
 * Get promotion statistics for admin dashboard
 * 
 * @param mysqli $conn
 * @return array
 */
function getPromotionStatistics($conn) {
    $stats = [];
    
    // Total active promotions
    $now = date('Y-m-d H:i:s');
    $result = $conn->query("SELECT COUNT(*) as count FROM promotions 
                           WHERE status = 'active' AND start_date <= '$now' AND end_date >= '$now'");
    $stats['active_promotions'] = $result->fetch_assoc()['count'];
    
    // Total promotion usage
    $result = $conn->query("SELECT SUM(times_used) as total FROM promotions");
    $stats['total_uses'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Total discount amount given
    $result = $conn->query("SELECT SUM(discount_amount) as total FROM promotion_usage");
    $stats['total_discount_amount'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Products on sale
    $result = $conn->query("SELECT COUNT(*) as count FROM products WHERE discount_percentage > 0 AND status = 'active'");
    $stats['products_on_sale'] = $result->fetch_assoc()['count'];
    
    // Most popular promotion
    $result = $conn->query("SELECT code, name, times_used FROM promotions ORDER BY times_used DESC LIMIT 1");
    $stats['most_popular'] = $result->fetch_assoc();
    
    return $stats;
}

/**
 * Clean up expired promotions (set status to 'expired')
 * Run this periodically via cron job
 * 
 * @param mysqli $conn
 * @return int - Number of promotions expired
 */
function cleanupExpiredPromotions($conn) {
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("UPDATE promotions SET status = 'expired' WHERE end_date < ? AND status = 'active'");
    $stmt->bind_param("s", $now);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    return $affected;
}

?>