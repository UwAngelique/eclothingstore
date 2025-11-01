<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Enhanced product display with discount badges and pricing
 * Add this to your product listing/shop page
 */

include 'db_connect.php';
include 'discount_functions.php';

// Fetch products with discount calculation
$products = [];
$query = "SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Calculate if product has active discount
        $discountInfo = calculateProductDiscount($row['id'], $row['price'], $conn);
        $row['has_discount'] = $discountInfo['discount_amount'] > 0;
        $row['final_price'] = $discountInfo['discounted_price'];
        $row['discount_amount'] = $discountInfo['discount_amount'];
        $row['discount_percentage'] = $discountInfo['discount_percentage'] ?? 0;
        
        $products[] = $row;
    }
}

// Get active promotions to display banner
$activePromotions = getActivePromotions($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Products on Sale</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, sans-serif; background: #f3f4f6; }
        
        .promo-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            text-align: center;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .promo-banner i { margin-right: 0.5rem; }
        
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .product-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            position: relative;
        }
        
        .discount-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #ef4444;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 700;
            font-size: 0.875rem;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
            z-index: 10;
        }
        
        .featured-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            z-index: 10;
        }
        
        .product-content {
            padding: 1.5rem;
        }
        
        .product-category {
            color: #6b7280;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }
        
        .product-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
            line-height: 1.4;
        }
        
        .product-pricing {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .original-price {
            color: #9ca3af;
            text-decoration: line-through;
            font-size: 1rem;
        }
        
        .current-price {
            color: #1f2937;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .discount-price {
            color: #ef4444;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .savings-text {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .product-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
            flex: 1;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid #e5e7eb;
            color: #6b7280;
            padding: 0.65rem 1rem;
        }
        
        .btn-outline:hover {
            border-color: #667eea;
            color: #667eea;
        }
        
        .stock-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        
        .stock-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-weight: 500;
            font-size: 0.75rem;
        }
        
        .in-stock { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .low-stock { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .out-of-stock { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            font-size: 2rem;
            color: #1f2937;
        }
        
        .filter-section {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 0.5rem 1rem;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .filter-btn:hover, .filter-btn.active {
            border-color: #667eea;
            color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }
        
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Promotion Banner -->
    <?php if (!empty($activePromotions)): ?>
        <?php $promo = $activePromotions[0]; ?>
        <div class="promo-banner">
            <i class="fas fa-fire"></i>
            <strong><?= htmlspecialchars($promo['name']) ?>:</strong>
            Use code <strong><?= htmlspecialchars($promo['code']) ?></strong> 
            <?php if ($promo['discount_type'] === 'percentage'): ?>
                for <?= $promo['discount_value'] ?>% off
            <?php elseif ($promo['discount_type'] === 'fixed_amount'): ?>
                for $<?= number_format($promo['discount_value'], 2) ?> off
            <?php endif; ?>
            <?php if ($promo['min_purchase_amount'] > 0): ?>
                on orders over $<?= number_format($promo['min_purchase_amount'], 2) ?>
            <?php endif; ?>
            - Ends <?= date('M j, Y', strtotime($promo['end_date'])) ?>
        </div>
    <?php endif; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Shop All Products</h1>
            <div class="filter-section">
                <button class="filter-btn active" onclick="filterProducts('all')">All Products</button>
                <button class="filter-btn" onclick="filterProducts('sale')">
                    <i class="fas fa-tag"></i> On Sale
                </button>
                <button class="filter-btn" onclick="filterProducts('featured')">
                    <i class="fas fa-star"></i> Featured
                </button>
            </div>
        </div>
        
        <div class="products-grid" id="productsGrid">
            <?php foreach ($products as $product): ?>
                <?php
                // Determine stock status
                $quantity = intval($product['quantity']);
                $minStock = $product['min_stock'] ? intval($product['min_stock']) : 5;
                
                if ($quantity === 0) {
                    $stockClass = 'out-of-stock';
                    $stockText = 'Out of Stock';
                } elseif ($quantity <= $minStock) {
                    $stockClass = 'low-stock';
                    $stockText = "Only $quantity left!";
                } else {
                    $stockClass = 'in-stock';
                    $stockText = 'In Stock';
                }
                ?>
                
                <div class="product-card" data-sale="<?= $product['has_discount'] ? 'true' : 'false' ?>" 
                     data-featured="<?= $product['is_featured'] ? 'true' : 'false' ?>">
                    
                    <div style="position: relative;">
                        <?php if ($product['product_image']): ?>
                            <img src="<?= htmlspecialchars($product['product_image']) ?>" 
                                 class="product-image" alt="<?= htmlspecialchars($product['product_name']) ?>">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/280x250/667eea/ffffff?text=No+Image" 
                                 class="product-image" alt="No Image">
                        <?php endif; ?>
                        
                        <?php if ($product['has_discount']): ?>
                            <div class="discount-badge">
                                -<?= round($product['discount_percentage']) ?>% OFF
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($product['is_featured']): ?>
                            <div class="featured-badge">
                                <i class="fas fa-star"></i> Featured
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-content">
                        <div class="product-category">
                            <?= htmlspecialchars($product['category']) ?>
                        </div>
                        
                        <h3 class="product-name">
                            <?= htmlspecialchars($product['product_name']) ?>
                        </h3>
                        
                        <div class="product-pricing">
                            <?php if ($product['has_discount']): ?>
                                <span class="original-price">
                                    $<?= number_format($product['price'], 2) ?>
                                </span>
                                <span class="discount-price">
                                    $<?= number_format($product['final_price'], 2) ?>
                                </span>
                                <span class="savings-text">
                                    Save $<?= number_format($product['discount_amount'], 2) ?>
                                </span>
                            <?php else: ?>
                                <span class="current-price">
                                    $<?= number_format($product['price'], 2) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="stock-status">
                            <span class="stock-badge <?= $stockClass ?>">
                                <?= $stockText ?>
                            </span>
                        </div>
                        
                        <div class="product-actions">
                            <button class="btn btn-primary" onclick="addToCart(<?= $product['id'] ?>)" 
                                    <?= $quantity === 0 ? 'disabled' : '' ?>>
                                <i class="fas fa-shopping-cart"></i>
                                <?= $quantity === 0 ? 'Out of Stock' : 'Add to Cart' ?>
                            </button>
                            <button class="btn btn-outline" onclick="viewProduct(<?= $product['id'] ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script>
        function filterProducts(type) {
            const cards = document.querySelectorAll('.product-card');
            const buttons = document.querySelectorAll('.filter-btn');
            
            // Update active button
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Filter products
            cards.forEach(card => {
                if (type === 'all') {
                    card.style.display = 'block';
                } else if (type === 'sale') {
                    card.style.display = card.dataset.sale === 'true' ? 'block' : 'none';
                } else if (type === 'featured') {
                    card.style.display = card.dataset.featured === 'true' ? 'block' : 'none';
                }
            });
        }
        
        function addToCart(productId) {
            // Add your cart functionality here
            console.log('Adding product to cart:', productId);
            alert('Product added to cart! (Implement your cart logic)');
        }
        
        function viewProduct(productId) {
            // Navigate to product details page
            window.location.href = 'product-details.php?id=' + productId;
        }
    </script>
</body>
</html>