<?php
// shop.php  — full page (UI + backend endpoints)

// ---- DEBUG (remove in production) ----
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Start session FIRST so session_id() is valid everywhere
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// require __DIR__ . '/db_connect.php';
require_once 'admin/db_connect.php';

const PUBLIC_UPLOADS_PREFIX = '';

/**
 * DEV: force a fixed session identifier so ALL requests share the same cart.
 * - Set to 'angelique' for your test
 * - Set to '' (empty string) to use real PHP session ids in production
 */
const DEV_FORCE_SESSION_ID = 'angelique';

/** Always call this when you need the session id for DB reads/writes */
function sid(): string {
    return DEV_FORCE_SESSION_ID !== '' ? DEV_FORCE_SESSION_ID : session_id();
}

// ----- helpers -----
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($n) { return ' RWF' . number_format((float)$n, 2); }

// Enhanced image function with hardcoded images (fallbacks)
function img_or_placeholder(?string $relPath, int $w = 300, int $h = 300): string {
    $relPath = trim((string)$relPath);

    // Hardcoded high-quality fashion images
    $fashionImages = [
        'https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?w=400&h=400&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=400&h=400&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1506629905607-d81034956c7e?w=400&h=400&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1583743089695-4b816a340f82?w=400&h=400&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=400&h=400&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=400&h=400&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=400&h=400&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1472851294608-062f824d29cc?w=400&h=400&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1544441893-675973e31985?w=400&h=400&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1558769132-cb1aea458c5e?w=400&h=400&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400&h=400&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1564584217132-2271feaeb3c5?w=400&h=400&fit=crop&crop=center'
    ];

    if ($relPath !== '') {
        $try1 = __DIR__ . '/' . $relPath;
        $try2 = __DIR__ . '/' . PUBLIC_UPLOADS_PREFIX . $ $relPath;
        if (is_file($try1)) return $relPath;
        if (is_file($try2)) return PUBLIC_UPLOADS_PREFIX . $relPath;
    }

    // Return random fashion image instead of placeholder
    return $fashionImages[array_rand($fashionImages)];
}

function sale_badge(?float $price, ?float $sale): string {
    if ($sale !== null && $sale > 0 && $price > 0 && $sale < $price) {
        $pct = round(100 - ($sale / $price) * 100);
        return "<div class=\"sale-badge animate-pulse\">{$pct}% OFF</div>";
    }
    return '';
}

// ---------- AJAX actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    // add_to_cart
    if ($action === 'add_to_cart') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity  = max(1, (int)($_POST['quantity'] ?? 1));
        $sessionId = sid();

        if ($productId > 0) {
            // Check if product exists (active)
            $stmt = $conn->prepare("SELECT id, product_name FROM products WHERE id = ? AND status = 'active'");
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($product) {
                // Check if item already in cart
                $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE product_id = ? AND session_id = ?");
                $stmt->bind_param("is", $productId, $sessionId);
                $stmt->execute();
                $existing = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($existing) {
                    // Update quantity
                    $newQty = (int)$existing['quantity'] + $quantity;
                    $stmt = $conn->prepare("UPDATE cart SET quantity = ?, updated_at=NOW() WHERE id = ? AND session_id = ?");
                    $stmt->bind_param("iis", $newQty, $existing['id'], $sessionId);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    // Insert new cart item
                    $stmt = $conn->prepare("INSERT INTO cart (product_id, session_id, quantity, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                    $stmt->bind_param("isi", $productId, $sessionId, $quantity);
                    $stmt->execute();
                    $stmt->close();
                }

                // Get cart count
                $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) as total FROM cart WHERE session_id = ?");
                $stmt->bind_param("s", $sessionId);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                $cartCount = (int)($result['total'] ?? 0);
                $stmt->close();

                echo json_encode([
                    'success' => true,
                    'message' => 'Product added to cart',
                    'cart_count' => $cartCount,
                    'product_name' => $product['product_name']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        }
        exit;
    }

    // get_cart (LEFT JOIN + compute price/subtotal safely)
    if ($action === 'get_cart') {
        $sessionId = sid();

        $stmt = $conn->prepare("
            SELECT c.id, c.quantity, p.id as product_id, p.product_name, p.price, p.sale_price, p.product_image, p.category
            FROM cart c 
            LEFT JOIN products p ON c.product_id = p.id 
            WHERE c.session_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->bind_param("s", $sessionId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $items = [];
        $total = 0.0;

        foreach ($rows as $r) {
            $qty = (int)$r['quantity'];
            $unit = 0.0;
            if ($r['sale_price'] !== null && (float)$r['sale_price'] > 0 && (float)$r['price'] > 0 && (float)$r['sale_price'] < (float)$r['price']) {
                $unit = (float)$r['sale_price'];
            } else {
                $unit = (float)($r['price'] ?? 0);
            }
            $subtotal = $unit * $qty;
            $total += $subtotal;

            $items[] = [
                'id' => (int)$r['id'],
                'product_id' => $r['product_id'] ? (int)$r['product_id'] : null,
                'product_name' => $r['product_name'] ?: '(Unavailable product)',
                'category' => $r['category'] ?? '',
                'quantity' => $qty,
                'price' => $unit,
                'subtotal' => $subtotal,
                'product_image' => img_or_placeholder($r['product_image']),
            ];
        }

        echo json_encode([
            'success' => true,
            'items' => $items,
            'total' => $total,
            'count' => array_sum(array_column($items, 'quantity'))
        ]);
        exit;
    }

    // update_cart
    if ($action === 'update_cart') {
        $cartId    = (int)($_POST['cart_id'] ?? 0);
        $quantity  = (int)($_POST['quantity'] ?? 0);
        $sessionId = sid();

        if ($cartId <= 0) {
            echo json_encode(['success'=>false, 'message'=>'Invalid cart id']); exit;
        }

        if ($quantity <= 0) {
            $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND session_id = ?");
            $stmt->bind_param("is", $cartId, $sessionId);
        } else {
            $stmt = $conn->prepare("UPDATE cart SET quantity = ?, updated_at=NOW() WHERE id = ? AND session_id = ?");
            $stmt->bind_param("iis", $quantity, $cartId, $sessionId);
        }

        $stmt->execute();
        $stmt->close();

        // Get updated cart count
        $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) as total FROM cart WHERE session_id = ?");
        $stmt->bind_param("s", $sessionId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $cartCount = (int)($result['total'] ?? 0);
        $stmt->close();

        echo json_encode([
            'success' => true,
            'cart_count' => $cartCount,
            'message' => $quantity <= 0 ? 'Item removed from cart' : 'Cart updated'
        ]);
        exit;
    }

    // search (query + category)
    if ($action === 'search') {
        $query = trim($_POST['query'] ?? '');
        $category = trim($_POST['category'] ?? '');

        if (strlen($query) >= 2 || !empty($category)) {
            $sql = "SELECT id, product_name, sku, category, price, sale_price, quantity, product_image, is_featured, created_at
                    FROM products
                    WHERE status='active'";
            $params = [];
            $types = '';

            if (!empty($query)) {
                $sql .= " AND (product_name LIKE ? OR category LIKE ? OR sku LIKE ?)";
                $searchTerm = "%$query%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
                $types .= 'sss';
            }

            if (!empty($category)) {
                $sql .= " AND category = ?";
                $params[] = $category;
                $types .= 's';
            }

            $sql .= " ORDER BY created_at DESC LIMIT 20";

            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // ensure image present
            foreach ($results as &$r) {
                $r['product_image'] = img_or_placeholder($r['product_image']);
            }

            echo json_encode([
                'success' => true,
                'products' => $results,
                'count' => count($results)
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Search query too short']);
        }
        exit;
    }

    // checkout
    if ($action === 'checkout') {
        $sessionId       = sid();
        $customerName    = trim($_POST['customer_name'] ?? 'Guest Customer');
        $customerEmail   = trim($_POST['customer_email'] ?? '');
        $customerPhone   = trim($_POST['customer_phone'] ?? '');
        $shippingAddress = trim($_POST['shipping_address'] ?? '');

        // Get cart items with prices
        $stmt = $conn->prepare("
            SELECT c.quantity, c.product_id, p.product_name, p.price, p.sale_price
            FROM cart c 
            LEFT JOIN products p ON c.product_id = p.id 
            WHERE c.session_id = ?
        ");
        $stmt->bind_param("s", $sessionId);
        $stmt->execute();
        $cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($cartItems)) {
            echo json_encode(['success' => false, 'message' => 'Cart is empty']);
            exit;
        }

        // Calculate total + prepare rows
        $total = 0.0;
        $prepared = [];
        foreach ($cartItems as $item) {
            if (!$item['product_id']) continue;
            $price = 0.0;
            if ($item['sale_price'] !== null && (float)$item['sale_price'] > 0 && (float)$item['price'] > 0 && (float)$item['sale_price'] < (float)$item['price']) {
                $price = (float)$item['sale_price'];
            } else {
                $price = (float)($item['price'] ?? 0);
            }
            $qty = (int)$item['quantity'];
            $subtotal = $price * $qty;
            $total += $subtotal;
            $prepared[] = ['pid'=>(int)$item['product_id'], 'qty'=>$qty, 'price'=>$price, 'subtotal'=>$subtotal];
        }

        if (!$prepared) { echo json_encode(['success'=>false,'message'=>'No valid items to order']); exit; }

        try {
            $conn->begin_transaction();

            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid('',true)),0,6));
            $stmt = $conn->prepare("
                INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, shipping_address, total_amount, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->bind_param("sssssd", $orderNumber, $customerName, $customerEmail, $customerPhone, $shippingAddress, $total);
            $stmt->execute();
            $orderId = $conn->insert_id;
            $stmt->close();

            $stmt = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) 
                VALUES (?, ?, ?, ?, ?)
            ");
            foreach ($prepared as $it) {
                $stmt->bind_param("iiidd", $orderId, $it['pid'], $it['qty'], $it['price'], $it['subtotal']);
                $stmt->execute();
            }
            $stmt->close();

            // Clear cart
            $stmt = $conn->prepare("DELETE FROM cart WHERE session_id = ?");
            $stmt->bind_param("s", $sessionId);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Order placed successfully!',
                'order_number' => $orderNumber,
                'order_id' => $orderId,
                'total' => $total
            ]);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode([
                'success' => false, 
                'message' => 'Order failed: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    echo json_encode(['success'=>false,'message'=>'Unknown action']); exit;
}

// ----- data fetch for initial render -----
$sessionId = sid();

// Cart count
$cartCount = 0;
$stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) as total FROM cart WHERE session_id = ?");
$stmt->bind_param("s", $sessionId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$cartCount = (int)($result['total'] ?? 0);
$stmt->close();

// limits
$limitGrid = 12;

// categories + counts
$categories = [];
$res = $conn->query("SELECT category, COUNT(*) AS cnt 
                     FROM products 
                     WHERE status='active' AND category IS NOT NULL AND category<>'' 
                     GROUP BY category 
                     ORDER BY category ASC");
while ($row = $res->fetch_assoc()) { $categories[] = $row; }

// new products for main grid
$stmt = $conn->prepare("SELECT id, product_name, sku, category, price, sale_price, quantity, product_image, is_featured, created_at
                        FROM products
                        WHERE status='active'
                        ORDER BY created_at DESC
                        LIMIT ?");
$stmt->bind_param("i", $limitGrid);
$stmt->execute();
$newProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// "New Arrivals"
$stmt = $conn->prepare("SELECT id, product_name, category, price, sale_price, product_image
                        FROM products
                        WHERE status='active'
                        ORDER BY created_at DESC
                        LIMIT 8");
$stmt->execute();
$newArrivals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// "Trending"
$stmt = $conn->prepare("SELECT id, product_name, category, price, sale_price, product_image, quantity
                        FROM products
                        WHERE status='active'
                        ORDER BY quantity DESC, created_at DESC
                        LIMIT 8");
$stmt->execute();
$trending = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// "Top Rated"
$stmt = $conn->prepare("SELECT id, product_name, category, price, sale_price, product_image
                        FROM products
                        WHERE status='active'
                        ORDER BY is_featured DESC, created_at DESC
                        LIMIT 8");
$stmt->execute();
$topRated = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anon - Premium eCommerce Experience</title>
    <link rel="shortcut icon" href="./assets/images/logo/favicon.ico" type="image/x-icon">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-solid: #667eea;
            --secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --secondary-solid: #f093fb;
            --accent: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --accent-solid: #4facfe;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;

            --dark: #0f172a;
            --dark-light: #1e293b;
            --text: #334155;
            --text-light: #64748b;
            --text-lighter: #94a3b8;

            --white: #ffffff;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;

            --shadow-glass: 0 8px 32px rgba(31, 38, 135, 0.15);
            --shadow-float: 0 20px 60px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 30px 80px rgba(0, 0, 0, 0.15);
            --shadow-inset: inset 0 1px 3px rgba(0, 0, 0, 0.1);

            --radius-sm: 8px;
            --radius: 16px;
            --radius-lg: 24px;
            --radius-xl: 32px;

            --font-heading: 'Outfit', sans-serif;
            --font-body: 'Space Grotesk', sans-serif;

            --blur: blur(20px);
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            --bounce: cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        body {
            font-family: var(--font-body);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background:
                radial-gradient(circle at 20% 20%, rgba(120, 119, 198, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 119, 198, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 60%, rgba(79, 172, 254, 0.1) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        .container { max-width: 1400px; margin: 0 auto; padding: 0 24px; }

        /* Glass */
        .glass { background: rgba(255, 255, 255, 0.1); backdrop-filter: var(--blur); border: 1px solid rgba(255,255,255,0.2); }
        .glass-dark { background: rgba(15, 23, 42, 0.8); backdrop-filter: var(--blur); border: 1px solid rgba(255,255,255,0.1); }

        /* Animations */
        @keyframes float { 0%,100%{transform:translateY(0) rotate(0)} 33%{transform:translateY(-10px) rotate(1deg)} 66%{transform:translateY(5px) rotate(-1deg)} }
        @keyframes glow { 0%,100%{ box-shadow:0 0 20px rgba(102,126,234,.3)} 50%{ box-shadow:0 0 40px rgba(102,126,234,.6),0 0 60px rgba(102,126,234,.3)} }
        @keyframes shimmer { 0%{background-position:-1000px 0} 100%{background-position:1000px 0} }
        @keyframes slideInUp { from{opacity:0; transform:translateY(30px)} to{opacity:1; transform:translateY(0)} }
        @keyframes slideInLeft { from{transform:translateX(-120%);opacity:0} to{transform:translateX(0);opacity:1} }
        @keyframes slideInRight { from{transform:translateX(120%);opacity:0} to{transform:translateX(0);opacity:1} }
        @keyframes heartFloat { 0%{opacity:1;transform:translate(0,0) scale(1)} 100%{opacity:0;transform:translate(var(--random-x,0),-150px) scale(.3)} }
        @keyframes particleFloat { 0%{opacity:1;transform:translate(0,0) scale(1) rotate(0)} 100%{opacity:0;transform:translate(var(--random-x),-120px) scale(.3) rotate(360deg)} }
        @keyframes spin { 0%{transform:rotate(0)} 100%{transform:rotate(360deg)} }

        /* Header */
        .header { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; transition: var(--transition); }
        .header-top { background: var(--dark); color: var(--white); padding: 12px 0; text-align: center; font-weight: 500; letter-spacing: .5px; position: relative; overflow: hidden; }
        .header-top::before { content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg,transparent,rgba(255,255,255,.1),transparent); animation: shimmer 3s infinite; }
        .header-main { background: rgba(255,255,255,.95); backdrop-filter: var(--blur); padding: 20px 0; border-bottom: 1px solid rgba(255,255,255,.2); }
        .header-container { display:flex; align-items:center; justify-content:space-between; gap:32px; }
        .header-logo { font-family: var(--font-heading); font-size:36px; font-weight:900; background:var(--primary); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; text-decoration:none; position:relative; animation: glow 3s ease-in-out infinite; }

        .header-search { flex:1; max-width:600px; position:relative; }
        .search-container { position:relative; background:rgba(255,255,255,.9); border-radius: var(--radius-xl); padding:4px; box-shadow: var(--shadow-glass); border:1px solid rgba(255,255,255,.3); }
        .search-field { width:100%; padding:16px 60px 16px 24px; border:none; border-radius: var(--radius-lg); font-size:16px; background:transparent; color:var(--dark); font-weight:500; }
        .search-field::placeholder{ color: var(--text-light); }
        .search-field:focus{ outline:none; }
        .search-btn { position:absolute; right:8px; top:50%; transform:translateY(-50%); background:var(--primary); border:none; padding:12px; border-radius: var(--radius-lg); color:var(--white); cursor:pointer; transition: var(--transition); box-shadow: var(--shadow-glass); }
        .search-btn:hover{ transform: translateY(-50%) scale(1.1); box-shadow: var(--shadow-hover); }

        .header-actions { display:flex; align-items:center; gap:20px; }
        .action-btn { position:relative; background:rgba(255,255,255,.9); border:1px solid rgba(255,255,255,.3); padding:12px; border-radius: var(--radius-lg); cursor:pointer; color:var(--dark); font-size:20px; transition: var(--transition); backdrop-filter: var(--blur); box-shadow: var(--shadow-glass); }
        .action-btn:hover{ background: var(--primary); color: var(--white); transform: translateY(-4px) scale(1.05); box-shadow: var(--shadow-hover); }

        .count { position:absolute; top:-8px; right:-8px; background: var(--secondary); color:var(--white); font-size:12px; padding:6px 8px; border-radius:50%; min-width:24px; text-align:center; font-weight:700; animation: float 3s ease-in-out infinite; }

        /* Navigation */
        .navigation{ background: rgba(255,255,255,.9); backdrop-filter: var(--blur); border-top:1px solid rgba(255,255,255,.2); padding:16px 0; }
        .nav-list{ display:flex; justify-content:center; gap:48px; list-style:none; }
        .nav-link{ font-family: var(--font-heading); font-weight:600; color:var(--dark); text-decoration:none; padding:12px 0; position:relative; transition: var(--transition); text-transform:uppercase; letter-spacing:.5px; }
        .nav-link::before{ content:''; position:absolute; bottom:0; left:50%; width:0; height:3px; background:var(--primary); transition: var(--transition); border-radius:2px; transform: translateX(-50%); }
        .nav-link:hover{ color: var(--primary-solid); transform: translateY(-2px); }
        .nav-link:hover::before{ width:100%; }

        /* Hero */
        .hero{ margin-top:140px; padding:80px 0; position:relative; overflow:hidden;
               background-image: linear-gradient(135deg, rgba(102,126,234,.9) 0%, rgba(118,75,162,.9) 100%), url('https://images.unsplash.com/photo-1483985988355-763728e1935b?w=1920&h=1080&fit=crop&crop=center');
               background-size:cover; background-position:center; background-attachment:fixed; }
        .hero::before{ content:''; position:absolute; top:-50%; left:-50%; width:200%; height:200%;
                        background: radial-gradient(circle at 30% 30%, rgba(255,255,255,.1) 0%, transparent 50%), radial-gradient(circle at 70% 70%, rgba(245,87,108,.1) 0%, transparent 50%); animation: float 6s ease-in-out infinite; z-index:1; }
        .hero-content{ text-align:center; max-width:800px; margin:0 auto; animation: slideInUp 1s ease-out; position:relative; z-index:2; }
        .hero-badge{ display:inline-block; background: rgba(255,255,255,.2); backdrop-filter: var(--blur); border:1px solid rgba(255,255,255,.3); padding:12px 24px; border-radius: var(--radius-xl); color:var(--white); font-weight:600; margin-bottom:24px; font-size:14px; letter-spacing:1px; text-transform:uppercase; }
        .hero-title{ font-family: var(--font-heading); font-size: clamp(48px, 8vw, 96px); font-weight:900; margin-bottom:24px; line-height:1.1; background: linear-gradient(135deg, #fff 0%, #f1f5f9 100%); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; text-shadow:0 4px 20px rgba(0,0,0,.1); }
        .hero-subtitle{ font-size:20px; color: rgba(255,255,255,.9); margin-bottom:40px; font-weight:400; }
        .hero-cta{ display:inline-flex; align-items:center; gap:12px; background: var(--white); color:var(--dark); padding:18px 36px; border-radius: var(--radius-xl); font-weight:700; text-decoration:none; font-size:16px; transition: var(--transition); box-shadow: var(--shadow-float); position:relative; overflow:hidden; }
        .hero-cta::before{ content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background: linear-gradient(90deg,transparent,rgba(255,255,255,.3),transparent); transition: var(--transition); }
        .hero-cta:hover{ transform: translateY(-6px) scale(1.05); box-shadow: var(--shadow-hover); }
        .hero-cta:hover::before{ left:100%; }

        /* Category Section */
        .category-section{ padding:80px 0; position:relative; }
        .section-header{ text-align:center; margin-bottom:60px; }
        .section-title{ font-family: var(--font-heading); font-size:48px; font-weight:800; margin-bottom:16px; background: var(--primary); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; position:relative; }
        .section-subtitle{ font-size:18px; color: var(--text-light); max-width:600px; margin:0 auto; }
        .category-grid{ display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:32px; }
        .category-card{ background: rgba(255,255,255,.95); backdrop-filter: var(--blur); border-radius: var(--radius-xl); padding:32px; box-shadow: var(--shadow-glass); border:1px solid rgba(255,255,255,.3); transition: var(--transition); cursor:pointer; position:relative; overflow:hidden; animation: slideInUp .8s ease-out; animation-fill-mode: both; }
        .category-card:nth-child(2){ animation-delay:.1s } .category-card:nth-child(3){ animation-delay:.2s } .category-card:nth-child(4){ animation-delay:.3s }
        .category-card::before{ content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background: linear-gradient(90deg,transparent,rgba(102,126,234,.1),transparent); }
        .category-card:hover{ transform: translateY(-12px) scale(1.02); box-shadow: var(--shadow-hover); border-color: rgba(102,126,234,.3); }
        .category-card:hover::before{ left:100%; }
        .category-header{ display:flex; align-items:center; gap:20px; margin-bottom:20px; }
        .category-icon{ width:64px; height:64px; background: var(--primary); border-radius: var(--radius-lg); display:flex; align-items:center; justify-content:center; color:var(--white); font-size:28px; box-shadow: var(--shadow-glass); transition: var(--transition); }
        .category-card:hover .category-icon{ transform: rotateY(180deg) scale(1.1); animation: glow 1s ease-in-out; }
        .category-info h3{ font-family: var(--font-heading); font-size:22px; font-weight:700; margin-bottom:8px; color:var(--dark); }
        .category-count{ color: var(--text-light); font-size:16px; font-weight:500; }
        .category-link{ color: var(--primary-solid); font-weight:700; text-decoration:none; font-size:16px; position:relative; }
        .category-link::after{ content:'→'; margin-left:8px; transition: var(--transition); }
        .category-card:hover .category-link::after{ transform: translateX(8px); }

        /* Main Content */
        .main-content{ background: var(--white); margin:40px 0; border-radius: var(--radius-xl); overflow:hidden; box-shadow: var(--shadow-float); min-height:100vh; }
        .content-layout{ display:grid; grid-template-columns:320px 1fr; min-height:100vh; }

        /* Sidebar */
        .sidebar{ background: linear-gradient(180deg, var(--gray-50) 0%, var(--gray-100) 100%); padding:40px 32px; border-right:1px solid var(--gray-200); position:relative; }
        .sidebar::before{ content:''; position:absolute; top:0; left:0; right:0; height:4px; background: var(--primary); }
        .sidebar-title{ font-family: var(--font-heading); font-size:24px; font-weight:800; margin-bottom:32px; color:var(--dark); text-transform:uppercase; letter-spacing:1px; }
        .category-list{ list-style:none; gap:8px; display:flex; flex-direction:column; }
        .category-item{ transition: var(--transition); }
        .category-link-sidebar{ display:flex; align-items:center; justify-content:space-between; padding:16px 20px; background: var(--white); border-radius: var(--radius-lg); text-decoration:none; color:var(--text); transition: var(--transition); border:1px solid var(--gray-200); position:relative; overflow:hidden; font-weight:500; }
        .category-link-sidebar::before{ content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background: var(--primary); transition: var(--transition); z-index:-1; }
        .category-link-sidebar:hover{ color: var(--white); transform: translateX(8px); box-shadow: var(--shadow-glass); }
        .category-link-sidebar:hover::before{ left:0; }
        .category-badge{ background: var(--gray-200); color:var(--text); padding:6px 12px; border-radius: var(--radius-lg); font-size:12px; font-weight:700; transition: var(--transition); }
        .category-link-sidebar:hover .category-badge{ background: rgba(255,255,255,.3); color: var(--white); }

        /* Product Content */
        .product-content{ padding:40px; background: var(--white); }

        /* Horizontal Product Sections */
        .horizontal-section{ margin-bottom:60px; }
        .horizontal-grid{ display:grid; grid-template-columns: repeat(3, 1fr); gap:32px; }
        .horizontal-products{ background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%); border-radius: var(--radius-xl); padding:32px; box-shadow: var(--shadow-glass); border:1px solid var(--gray-200); position:relative; overflow:hidden; transition: var(--transition); }
        .horizontal-products::before{ content:''; position:absolute; top:0; left:0; right:0; height:4px; background: var(--accent); border-radius: var(--radius-xl) var(--radius-xl) 0 0; }
        .horizontal-products:hover{ transform: translateY(-8px); box-shadow: var(--shadow-hover); }
        .horizontal-title{ font-family: var(--font-heading); font-size:20px; font-weight:700; margin-bottom:24px; color:var(--dark); text-transform:uppercase; letter-spacing:.5px; }
        .horizontal-product{ display:flex; gap:16px; padding:16px 0; border-bottom:1px solid var(--gray-200); transition: var(--transition); border-radius: var(--radius); }
        .horizontal-product:last-child{ border-bottom:none; }
        .horizontal-product:hover{ background: var(--white); margin:0 -16px; padding:16px; box-shadow: var(--shadow-glass); transform: scale(1.02); }
        .horizontal-image{ width:64px; height:64px; border-radius: var(--radius-lg); object-fit:cover; background: var(--gray-100); box-shadow: var(--shadow-glass); transition: var(--transition); }
        .horizontal-product:hover .horizontal-image{ transform: scale(1.1) rotate(5deg); }
        .horizontal-info{ flex:1; }
        .horizontal-name{ font-size:15px; font-weight:600; margin-bottom:4px; color:var(--dark); line-height:1.3; }
        .horizontal-category{ font-size:12px; color: var(--text-light); margin-bottom:8px; text-transform:uppercase; letter-spacing:.5px; }
        .horizontal-price{ font-size:16px; font-weight:700; background: var(--primary); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }

        /* Main Product Grid */
        .product-section{ margin:60px 0; }
        .products-header{ display:flex; justify-content:space-between; align-items:center; margin-bottom:40px; }
        .products-title{ font-family: var(--font-heading); font-size:36px; font-weight:800; background: var(--primary); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; position:relative; }
        .products-title::after{ content:''; position:absolute; bottom:-8px; left:0; width:80px; height:4px; background: var(--accent); border-radius:2px; animation: shimmer 2s infinite; }
        .view-all-btn{ background: rgba(102,126,234,.1); color: var(--primary-solid); border:1px solid rgba(102,126,234,.3); padding:12px 24px; border-radius: var(--radius-lg); font-weight:600; text-decoration:none; transition: var(--transition); backdrop-filter: var(--blur); }
        .view-all-btn:hover{ background: var(--primary); color: var(--white); transform: translateY(-2px); box-shadow: var(--shadow-glass); }

        .product-grid{ display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:32px; }
        .product-card{ background: var(--white); border-radius: var(--radius-xl); overflow:hidden; box-shadow: var(--shadow-glass); border:1px solid var(--gray-200); transition: var(--transition); position:relative; cursor:pointer; animation: slideInUp .6s ease-out; animation-fill-mode: both; }
        .product-card:nth-child(odd){ animation-delay:.1s } .product-card:nth-child(even){ animation-delay:.2s }
        .product-card::before{ content:''; position:absolute; top:0; left:0; right:0; bottom:0; background: var(--primary); opacity:0; transition: var(--transition); z-index:-1; }
        .product-card:hover{ transform: translateY(-12px) rotateX(5deg); box-shadow: var(--shadow-hover); border-color: rgba(102,126,234,.3); }
        .product-card:hover::before{ opacity:.05; }
        .product-image-container{ position:relative; height:280px; background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%); overflow:hidden; border-radius: var(--radius-xl) var(--radius-xl) 0 0; }
        .product-image{ width:100%; height:100%; object-fit:cover; transition: var(--transition); filter: brightness(1.05) saturate(1.1); }
        .product-card:hover .product-image{ transform: scale(1.15) rotate(2deg); filter: brightness(1.1) saturate(1.2); }
        .sale-badge{ position:absolute; top:16px; left:16px; background: var(--secondary); color:var(--white); padding:8px 16px; border-radius: var(--radius-lg); font-size:12px; font-weight:800; z-index:2; box-shadow: var(--shadow-glass); text-transform:uppercase; letter-spacing:.5px; }
        .product-actions{ position:absolute; top:16px; right:16px; display:flex; flex-direction:column; gap:12px; opacity:0; transform: translateX(30px) scale(.8); transition: var(--transition); }
        .product-card:hover .product-actions{ opacity:1; transform: translateX(0) scale(1); }
        .action-icon{ width:48px; height:48px; background: rgba(255,255,255,.95); border:none; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; transition: var(--transition); backdrop-filter: var(--blur); box-shadow: var(--shadow-glass); color:var(--text); font-size:18px; }
        .action-icon:hover{ background: var(--primary); color: var(--white); transform: scale(1.2) rotate(360deg); box-shadow: var(--shadow-hover); }
        .product-info{ padding:28px; background: var(--white); }
        .product-category{ color: var(--text-light); font-size:12px; text-transform:uppercase; letter-spacing:1px; margin-bottom:8px; font-weight:600; }
        .product-title{ font-family: var(--font-heading); font-size:18px; font-weight:700; margin-bottom:12px; line-height:1.4; color:var(--dark); min-height:50px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .product-rating{ display:flex; align-items:center; gap:4px; margin-bottom:16px; }
        .star{ color:#fbbf24; font-size:16px; filter: drop-shadow(0 2px 4px rgba(251,191,36,.3)); }
        .star.outline{ color: var(--gray-300); }
        .rating-text{ margin-left:8px; font-size:13px; color: var(--text-light); font-weight:500; }
        .price-container{ display:flex; align-items:center; gap:12px; margin-bottom:20px; }
        .current-price{ font-family: var(--font-heading); font-size:24px; font-weight:800; background: var(--primary); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
        .original-price{ font-size:16px; color: var(--text-light); text-decoration: line-through; font-weight:500; }
        .add-to-cart{ width:100%; background: var(--dark); color: var(--white); border:none; padding:16px; border-radius: var(--radius-lg); font-weight:700; cursor:pointer; transition: var(--transition); font-size:14px; text-transform:uppercase; letter-spacing:.5px; position:relative; overflow:hidden; display:flex; align-items:center; justify-content:center; gap:8px; }
        .add-to-cart::before{ content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background: var(--primary); transition: var(--transition); }
        .add-to-cart:hover{ transform: translateY(-4px); box-shadow: var(--shadow-hover); }
        .add-to-cart:hover::before{ left:0; }
        .add-to-cart:active{ transform: translateY(-2px) scale(.98); }
        .add-to-cart:disabled{ opacity:.7; cursor:not-allowed; }

        /* Cart Modal */
        .cart-modal{ display:none; position:fixed; top:0; left:0; right:0; bottom:0; background: rgba(0,0,0,.5); backdrop-filter: var(--blur); z-index:2000; opacity:0; transition: var(--transition); }
        .cart-modal.show{ display:flex; opacity:1; animation: slideInRight .5s var(--bounce); }
        .cart-content{ position:absolute; right:0; top:0; bottom:0; width:100%; max-width:500px; background: var(--white); box-shadow: var(--shadow-float); display:flex; flex-direction:column; transform: translateX(100%); transition: var(--transition); }
        .cart-modal.show .cart-content{ transform: translateX(0); }
        .cart-header{ padding:24px; border-bottom:1px solid var(--gray-200); display:flex; justify-content: space-between; align-items:center; background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%); }
        .cart-title{ font-family: var(--font-heading); font-size:24px; font-weight:800; color:var(--dark); flex:1; }
        .cart-close{ background:none; border:none; font-size:24px; cursor:pointer; padding:8px; color: var(--text-light); transition: var(--transition); border-radius: var(--radius); }
        .cart-close:hover{ background: var(--gray-100); color: var(--dark); transform: scale(1.1); }
        .cart-items{ flex:1; overflow-y:auto; padding:24px; }
        .cart-item{ display:flex; gap:16px; padding:20px 0; border-bottom:1px solid var(--gray-200); transition: var(--transition); }
        .cart-item:last-child{ border-bottom:none; }
        .cart-item:hover{ background: var(--gray-50); margin:0 -24px; padding:20px 24px; border-radius: var(--radius-lg); }
        .cart-item-image{ width:80px; height:80px; border-radius: var(--radius-lg); object-fit:cover; box-shadow: var(--shadow-glass); }
        .cart-item-info{ flex:1; }
        .cart-item-name{ font-weight:700; color:var(--dark); margin-bottom:4px; font-size:16px; }
        .cart-item-category{ color: var(--text-light); font-size:12px; text-transform:uppercase; letter-spacing:.5px; margin-bottom:12px; }
        .cart-item-controls{ display:flex; align-items:center; gap:12px; }
        .quantity-btn{ width:32px; height:32px; border:1px solid var(--gray-300); background: var(--white); border-radius: var(--radius); display:flex; align-items:center; justify-content:center; cursor:pointer; transition: var(--transition); font-weight:700; color:var(--text); }
        .quantity-btn:hover{ background: var(--primary); color: var(--white); border-color: var(--primary-solid); transform: scale(1.1); }
        .quantity-display{ font-weight:700; min-width:40px; text-align:center; font-size:16px; color:var(--dark); }
        .cart-item-price{ text-align:right; }
        .item-price{ font-weight:700; color: var(--primary-solid); font-size:18px; margin-bottom:4px; }
        .item-subtotal{ font-size:14px; color: var(--text-light); }
        .remove-item{ background:none; border:none; color: var(--danger); cursor:pointer; padding:4px; border-radius: var(--radius); transition: var(--transition); font-size:16px; }
        .remove-item:hover{ background: rgba(239,68,68,.1); transform: scale(1.2); }
        .cart-empty{ text-align:center; padding:60px 20px; color: var(--text-light); }
        .cart-empty ion-icon{ font-size:64px; margin-bottom:16px; opacity:.5; }
        .cart-summary{ padding:24px; border-top:1px solid var(--gray-200); background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%); }
        .cart-total{ display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; font-size:20px; font-weight:800; color:var(--dark); }
        .total-amount{ background: var(--primary); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; font-size:24px; }
        .checkout-btn{ width:100%; background: var(--primary); color: var(--white); border:none; padding:18px; border-radius: var(--radius-lg); font-weight:700; font-size:16px; cursor:pointer; transition: var(--transition); text-transform:uppercase; letter-spacing:.5px; position:relative; overflow:hidden; }
        .checkout-btn::before{ content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background: var(--secondary); transition: var(--transition); }
        .checkout-btn:hover{ transform: translateY(-2px); box-shadow: var(--shadow-hover); }
        .checkout-btn:hover::before{ left:0; }
        .checkout-btn:disabled{ opacity:.6; cursor:not-allowed; }

        /* Checkout Modal */
        .checkout-modal{ display:none; position:fixed; top:0; left:0; right:0; bottom:0; background: rgba(0,0,0,.6); backdrop-filter: var(--blur); z-index:3000; align-items:center; justify-content:center; opacity:0; transition: var(--transition); }
        .checkout-modal.show{ display:flex; opacity:1; }
        .checkout-form-container{ background: var(--white); border-radius: var(--radius-xl); padding:40px; max-width:500px; width:90%; max-height:80vh; overflow-y:auto; box-shadow: var(--shadow-float); transform: scale(.8); transition: var(--transition); position:relative; }
        .checkout-modal.show .checkout-form-container{ transform: scale(1); }
        .checkout-header{ text-align:center; margin-bottom:32px; }
        .checkout-title{ font-family: var(--font-heading); font-size:28px; font-weight:800; background: var(--primary); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; margin-bottom:8px; }
        .checkout-subtitle{ color: var(--text-light); font-size:16px; }
        .checkout-form{ display:flex; flex-direction:column; gap:20px; }
        .form-group{ display:flex; flex-direction:column; gap:8px; }
        .form-label{ font-weight:600; color: var(--dark); font-size:14px; text-transform:uppercase; letter-spacing:.5px; }
        .form-input{ padding:16px; border:2px solid var(--gray-200); border-radius: var(--radius-lg); font-size:16px; transition: var(--transition); background: var(--white); color:var(--dark); }
        .form-input:focus{ outline:none; border-color: var(--primary-solid); box-shadow:0 0 0 4px rgba(102,126,234,.1); }
        .form-textarea{ min-height:100px; resize:vertical; font-family:inherit; }
        .checkout-actions{ display:flex; gap:16px; margin-top:24px; }
        .cancel-btn{ flex:1; background: var(--gray-200); color:var(--text); border:none; padding:16px; border-radius: var(--radius-lg); font-weight:600; cursor:pointer; transition: var(--transition); }
        .cancel-btn:hover{ background: var(--gray-300); transform: translateY(-2px); }
        .place-order-btn{ flex:2; background: var(--success); color: var(--white); border:none; padding:16px; border-radius: var(--radius-lg); font-weight:700; cursor:pointer; transition: var(--transition); text-transform:uppercase; letter-spacing:.5px; }
        .place-order-btn:hover{ background:#059669; transform: translateY(-2px); box-shadow: var(--shadow-hover); }
        .place-order-btn:disabled{ opacity:.6; cursor:not-allowed; }

        /* Search Results */
        .search-results-section{ margin:40px 0; display:none; }
        .search-results-header{ display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; padding:20px 24px; background: var(--gray-50); border-radius: var(--radius-lg); border:1px solid var(--gray-200); }
        .search-results-title{ font-family: var(--font-heading); font-size:24px; font-weight:700; color: var(--dark); }
        .search-results-count{ color: var(--text-light); font-weight:500; }
        .clear-search{ background: var(--danger); color: var(--white); border:none; padding:8px 16px; border-radius: var(--radius); font-weight:600; cursor:pointer; transition: var(--transition); }
        .clear-search:hover{ transform: scale(1.05); box-shadow: var(--shadow-glass); }

        /* Loading spinner */
        .spinner{ width:16px; height:16px; border:2px solid rgba(255,255,255,.3); border-top:2px solid var(--white); border-radius:50%; animation: spin 1s linear infinite; }

        /* Toast */
        .toast{ position:fixed; bottom:32px; left:32px; background: rgba(255,255,255,.95); backdrop-filter: var(--blur); border-radius: var(--radius-xl); padding:20px; box-shadow: var(--shadow-float); border:1px solid rgba(255,255,255,.3); display:flex; align-items:center; gap:16px; z-index:1000; animation: slideInLeft .6s var(--bounce); max-width:360px; min-width:300px; }
        .toast-image{ width:56px; height:56px; border-radius: var(--radius-lg); object-fit:cover; box-shadow: var(--shadow-glass); }
        .toast-content{ flex:1; }
        .toast-message{ font-size:12px; color: var(--text-light); margin-bottom:4px; text-transform:uppercase; letter-spacing:.5px; }
        .toast-title{ font-size:16px; font-weight:700; margin-bottom:4px; color:var(--dark); }
        .toast-time{ font-size:12px; color: var(--text-lighter); font-weight:500; }
        .toast-close{ background:none; border:none; cursor:pointer; color: var(--text-light); font-size:18px; padding:8px; border-radius:50%; transition: var(--transition); }
        .toast-close:hover{ background: var(--gray-100); color: var(--dark); transform: scale(1.1); }

        /* Scroll to top */
        .scroll-to-top{ position:fixed; bottom:32px; right:32px; width:56px; height:56px; background: var(--primary); border:none; border-radius:50%; color:var(--white); cursor:pointer; transition: var(--transition); box-shadow: var(--shadow-float); z-index:999; opacity:0; transform: scale(0); }
        .scroll-to-top.visible{ opacity:1; transform: scale(1); }
        .scroll-to-top:hover{ transform: scale(1.1) translateY(-4px); box-shadow: var(--shadow-hover); animation: glow 1s ease-in-out; }

        /* Particles */
        .particles{ position:fixed; top:0; left:0; width:100%; height:100%; pointer-events:none; z-index:-1; }
        .particle{ position:absolute; width:4px; height:4px; background: rgba(102,126,234,.4); border-radius:50%; animation: floatParticle 6s infinite ease-in-out; }
        @keyframes floatParticle{ 0%,100%{ transform: translateY(0) translateX(0) scale(1); opacity:.7; } 50%{ transform: translateY(-20px) translateX(10px) scale(1.2); opacity:1; } }

        .active{ background: var(--primary)!important; color:var(--white)!important; transform: translateX(8px); }
        .active .category-badge{ background: rgba(255,255,255,.3)!important; color: var(--white)!important; }
        .active::before{ left:0!important; }

        /* Order Success Modal */
        .order-success-modal{ display:none; position:fixed; top:0; left:0; right:0; bottom:0; background: rgba(0,0,0,.6); backdrop-filter: var(--blur); z-index:4000; align-items:center; justify-content:center; opacity:0; transition: var(--transition); }
        .order-success-modal.show{ display:flex; opacity:1; }
        .order-success-content{ background: var(--white); border-radius: var(--radius-xl); padding:40px; max-width:400px; width:90%; text-align:center; box-shadow: var(--shadow-float); transform: scale(.8); transition: var(--transition); }
        .order-success-modal.show .order-success-content{ transform: scale(1); }
        .success-icon{ width:80px; height:80px; background: var(--success); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 24px; color:var(--white); font-size:40px; animation: float 2s ease-in-out infinite; }
        .success-title{ font-family: var(--font-heading); font-size:24px; font-weight:800; color: var(--dark); margin-bottom:12px; }
        .success-message{ color: var(--text-light); margin-bottom:8px; }
        .order-number{ font-weight:700; color: var(--primary-solid); font-size:18px; margin-bottom:24px; }
        .success-close-btn{ background: var(--primary); color: var(--white); border:none; padding:16px 32px; border-radius: var(--radius-lg); font-weight:700; cursor:pointer; transition: var(--transition); }
        .success-close-btn:hover{ transform: translateY(-2px); box-shadow: var(--shadow-hover); }

        /* Responsive */
        @media (max-width:1200px){ .content-layout{ grid-template-columns:280px 1fr; } .horizontal-grid{ grid-template-columns:1fr; gap:24px; } }
        @media (max-width:1024px){
            .content-layout{ grid-template-columns:1fr; }
            .sidebar{ border-right:none; border-bottom:1px solid var(--gray-200); }
            .nav-list{ gap:24px; flex-wrap:wrap; }
            .cart-content{ max-width:100%; }
        }
        @media (max-width:768px){
            .header-container{ flex-direction:column; gap:20px; }
            .hero{ margin-top:200px; padding:60px 0; }
            .hero-title{ font-size:48px; }
            .product-grid{ grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:24px; }
            .category-grid{ grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:24px; }
            .nav-list{ gap:16px; justify-content:flex-start; overflow-x:auto; padding-bottom:8px; }
            .nav-link{ white-space:nowrap; font-size:14px; }
            .checkout-form-container{ width:95%; padding:24px; }
            .checkout-actions{ flex-direction:column; }
        }
        @media (max-width:480px){
            .container{ padding:0 16px; }
            .product-content{ padding:24px; }
            .product-grid{ grid-template-columns:1fr; }
            .category-grid{ grid-template-columns:1fr; }
            .hero-title{ font-size:36px; }
            .section-title{ font-size:32px; }
            .toast{ left:16px; right:16px; max-width:none; min-width:auto; }
        }
    </style>
</head>
<body>
    <!-- Particle Background -->
    <div class="particles" id="particles"></div>

    <!-- Header -->
    <header class="header">
        <div class="header-top">
            <div class="container">✨ FREE SHIPPING THIS WEEK ORDER OVER - $55 ✨</div>
        </div>

        <div class="header-main">
            <div class="container">
                <div class="header-container">
                    <a href="#" class="header-logo">Anon</a>

                    <div class="header-search">
                        <div class="search-container">
                            <input type="search" name="search" class="search-field" placeholder="Enter your product name..." autocomplete="off">
                            <button class="search-btn" type="button">
                                <ion-icon name="search-outline"></ion-icon>
                            </button>
                        </div>
                    </div>

                    <div class="header-actions">
                        <button class="action-btn" title="Profile">
                            <ion-icon name="person-outline"></ion-icon>
                        </button>
                        <button class="action-btn" title="Wishlist">
                            <ion-icon name="heart-outline"></ion-icon>
                            <span class="count" id="wishlistCount">0</span>
                        </button>
                        <button class="action-btn" title="Shopping Cart" id="cartBtn">
                            <ion-icon name="bag-handle-outline"></ion-icon>
                            <span class="count" id="cartCount"><?= $cartCount ?></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <nav class="navigation">
            <div class="container">
                <ul class="nav-list">
                    <li><a href="#" class="nav-link" data-category="Jewelry">Jewelry</a></li>
                    <li><a href="#" class="nav-link" data-category="Perfume">Perfume</a></li>
                    <li><a href="#" class="nav-link">Blog</a></li>
                    <li><a href="#" class="nav-link">Hot Offers</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <div class="hero-content">
                    <div class="hero-badge">Trending Item</div>
                    <h1 class="hero-title">WOMEN'S LATEST<br>FASHION SALE</h1>
                    <p class="hero-subtitle">Discover premium quality fashion at unbeatable prices<br>starting at <strong>RWF 20.00</strong></p>
                    <a href="#products" class="hero-cta">
                        <ion-icon name="bag-outline"></ion-icon>
                        SHOP NOW
                    </a>
                </div>
            </div>
        </section>

        <!-- Category Section -->
        <section class="category-section">
            <div class="container">
                <div class="section-header fade-in">
                    <h2 class="section-title">Shop by Category</h2>
                    <p class="section-subtitle">Explore our curated collections designed for every style and occasion</p>
                </div>

                <div class="category-grid">
                    <div class="category-card" data-category="Dress">
                        <div class="category-header">
                            <div class="category-icon"><ion-icon name="shirt-outline"></ion-icon></div>
                            <div class="category-info">
                                <h3>DRESS & FROCK</h3>
                                <p class="category-count">(53 items)</p>
                            </div>
                        </div>
                        <a href="#" class="category-link">Explore Collection</a>
                    </div>

                    <div class="category-card" data-category="Winter">
                        <div class="category-header">
                            <div class="category-icon"><ion-icon name="snow-outline"></ion-icon></div>
                            <div class="category-info">
                                <h3>WINTER WEAR</h3>
                                <p class="category-count">(58 items)</p>
                            </div>
                        </div>
                        <a href="#" class="category-link">Explore Collection</a>
                    </div>

                    <div class="category-card" data-category="Accessories">
                        <div class="category-header">
                            <div class="category-icon"><ion-icon name="glasses-outline"></ion-icon></div>
                            <div class="category-info">
                                <h3>GLASSES & LENS</h3>
                                <p class="category-count">(68 items)</p>
                            </div>
                        </div>
                        <a href="#" class="category-link">Explore Collection</a>
                    </div>

                    <div class="category-card" data-category="Jeans">
                        <div class="category-header">
                            <div class="category-icon"><ion-icon name="bag-outline"></ion-icon></div>
                            <div class="category-info">
                                <h3>SHORTS & JEANS</h3>
                                <p class="category-count">(84 items)</p>
                            </div>
                        </div>
                        <a href="#" class="category-link">Explore Collection</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Content Layout -->
        <div class="main-content">
            <div class="container">
                <div class="content-layout">
                    <!-- Sidebar -->
                    <aside class="sidebar">
                        <h3 class="sidebar-title">Categories</h3>
                        <ul class="category-list">
                            <li class="category-item">
                                <a href="#" class="category-link-sidebar active" data-category="">
                                    <span>All Products</span>
                                    <span class="category-badge">All</span>
                                </a>
                            </li>
                            <?php foreach ($categories as $cat): ?>
                                <li class="category-item">
                                    <a href="#" class="category-link-sidebar" data-category="<?= h($cat['category']) ?>">
                                        <span><?= h($cat['category']) ?></span>
                                        <span class="category-badge"><?= (int)$cat['cnt'] ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </aside>

                    <!-- Product Content -->
                    <div class="product-content">
                        <!-- Search Results Section -->
                        <section class="search-results-section" id="searchResultsSection">
                            <div class="search-results-header">
                                <div>
                                    <h2 class="search-results-title">Search Results</h2>
                                    <p class="search-results-count" id="searchResultsCount">0 products found</p>
                                </div>
                                <button class="clear-search" onclick="clearSearch()">Clear Search</button>
                            </div>
                            <div class="product-grid" id="searchResultsGrid"></div>
                        </section>

                        <!-- Horizontal Product Sections -->
                        <section class="horizontal-section fade-in" id="horizontalSection">
                            <div class="horizontal-grid">
                                <!-- New Arrivals -->
                                <div class="horizontal-products">
                                    <h3 class="horizontal-title">🆕 New Arrivals</h3>
                                    <?php foreach (array_slice($newArrivals, 0, 4) as $p): ?>
                                        <div class="horizontal-product">
                                            <img src="<?= h(img_or_placeholder($p['product_image'], 64, 64)) ?>" alt="<?= h($p['product_name']) ?>" class="horizontal-image">
                                            <div class="horizontal-info">
                                                <h4 class="horizontal-name"><?= h($p['product_name']) ?></h4>
                                                <p class="horizontal-category"><?= h($p['category']) ?></p>
                                                <div class="horizontal-price">
                                                    <?php if (!empty($p['sale_price']) && $p['sale_price'] < $p['price']): ?>
                                                        <?= h(money($p['sale_price'])) ?>
                                                        <span style="text-decoration: line-through; color: var(--text-light); margin-left: 8px;">
                                                            <?= h(money($p['price'])) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <?= h(money($p['price'])) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Trending -->
                                <div class="horizontal-products">
                                    <h3 class="horizontal-title">🔥 Trending</h3>
                                    <?php foreach (array_slice($trending, 0, 4) as $p): ?>
                                        <div class="horizontal-product">
                                            <img src="<?= h(img_or_placeholder($p['product_image'], 64, 64)) ?>" alt="<?= h($p['product_name']) ?>" class="horizontal-image">
                                            <div class="horizontal-info">
                                                <h4 class="horizontal-name"><?= h($p['product_name']) ?></h4>
                                                <p class="horizontal-category"><?= h($p['category']) ?></p>
                                                <div class="horizontal-price">
                                                    <?php if (!empty($p['sale_price']) && $p['sale_price'] < $p['price']): ?>
                                                        <?= h(money($p['sale_price'])) ?>
                                                        <span style="text-decoration: line-through; color: var(--text-light); margin-left: 8px;">
                                                            <?= h(money($p['price'])) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <?= h(money($p['price'])) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Top Rated -->
                                <div class="horizontal-products">
                                    <h3 class="horizontal-title">⭐ Top Rated</h3>
                                    <?php foreach (array_slice($topRated, 0, 4) as $p): ?>
                                        <div class="horizontal-product">
                                            <img src="<?= h(img_or_placeholder($p['product_image'], 64, 64)) ?>" alt="<?= h($p['product_name']) ?>" class="horizontal-image">
                                            <div class="horizontal-info">
                                                <h4 class="horizontal-name"><?= h($p['product_name']) ?></h4>
                                                <p class="horizontal-category"><?= h($p['category']) ?></p>
                                                <div class="horizontal-price">
                                                    <?php if (!empty($p['sale_price']) && $p['sale_price'] < $p['price']): ?>
                                                        <?= h(money($p['sale_price'])) ?>
                                                        <span style="text-decoration: line-through; color: var(--text-light); margin-left: 8px;">
                                                            <?= h(money($p['price'])) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <?= h(money($p['price'])) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </section>

                        <!-- Main Product Grid -->
                        <section class="product-section fade-in" id="products">
                            <div class="products-header">
                                <h2 class="products-title" id="mainTitle">Latest Products</h2>
                                <a href="#" class="view-all-btn">View All Products</a>
                            </div>
                            <div class="product-grid" id="mainProductGrid">
                                <?php foreach ($newProducts as $index => $p): ?>
                                    <div class="product-card magnetic" data-product-id="<?= h($p['id']) ?>" style="animation-delay: <?= ($index * 0.1) ?>s;">
                                        <div class="product-image-container">
                                            <?= sale_badge((float)$p['price'], $p['sale_price'] !== null ? (float)$p['sale_price'] : null) ?>

                                            <div class="product-actions">
                                                <button class="action-icon" title="Add to Wishlist" data-action="wishlist">
                                                    <ion-icon name="heart-outline"></ion-icon>
                                                </button>
                                                <button class="action-icon" title="Quick View" data-action="quickview">
                                                    <ion-icon name="eye-outline"></ion-icon>
                                                </button>
                                                <button class="action-icon" title="Compare" data-action="compare">
                                                    <ion-icon name="git-compare-outline"></ion-icon>
                                                </button>
                                            </div>

                                            <img src="<?= h(img_or_placeholder($p['product_image'], 320, 280)) ?>" 
                                                 alt="<?= h($p['product_name']) ?>" 
                                                 class="product-image"
                                                 loading="lazy">
                                        </div>

                                        <div class="product-info">
                                            <p class="product-category"><?= h($p['category']) ?></p>
                                            <h3 class="product-title"><?= h($p['product_name']) ?></h3>

                                            <div class="product-rating">
                                                <ion-icon name="star" class="star"></ion-icon>
                                                <ion-icon name="star" class="star"></ion-icon>
                                                <ion-icon name="star" class="star"></ion-icon>
                                                <ion-icon name="star" class="star"></ion-icon>
                                                <ion-icon name="star-outline" class="star outline"></ion-icon>
                                                <span class="rating-text">(4.2)</span>
                                            </div>

                                            <div class="price-container">
                                                <?php if (!empty($p['sale_price']) && $p['sale_price'] < $p['price']): ?>
                                                    <span class="current-price"><?= h(money($p['sale_price'])) ?></span>
                                                    <span class="original-price"><?= h(money($p['price'])) ?></span>
                                                <?php else: ?>
                                                    <span class="current-price"><?= h(money($p['price'])) ?></span>
                                                <?php endif; ?>
                                            </div>

                                            <button class="add-to-cart" data-product-id="<?= h($p['id']) ?>" data-product="<?= h($p['product_name']) ?>">
                                                <ion-icon name="bag-add-outline"></ion-icon>
                                                Add to Cart
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Cart Modal -->
    <div class="cart-modal" id="cartModal">
        <div class="cart-content">
            <div class="cart-header">
                <h2 class="cart-title">Shopping Cart</h2>
                <button class="cart-close" id="cartCloseBtn">
                    <ion-icon name="close-outline"></ion-icon>
                </button>
            </div>
            <div class="cart-items" id="cartItems">
                <div class="cart-empty">
                    <ion-icon name="bag-outline"></ion-icon>
                    <h3>Your cart is empty</h3>
                    <p>Add some products to get started</p>
                </div>
            </div>
            <div class="cart-summary" id="cartSummary" style="display: none;">
                <div class="cart-total">
                    <span>Total:</span>
                    <span class="total-amount" id="cartTotalAmount">$0.00</span>
                </div>
                <button class="checkout-btn" id="checkoutBtn">
                    <ion-icon name="card-outline"></ion-icon>
                    Proceed to Checkout
                </button>
            </div>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div class="checkout-modal" id="checkoutModal">
        <div class="checkout-form-container">
            <div class="checkout-header">
                <h2 class="checkout-title">Complete Your Order</h2>
                <p class="checkout-subtitle">Please fill in your details to complete the purchase</p>
            </div>
            <form class="checkout-form" id="checkoutForm">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" class="form-input" name="customer_name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" class="form-input" name="customer_email" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" class="form-input" name="customer_phone">
                </div>
                <div class="form-group">
                    <label class="form-label">Shipping Address *</label>
                    <textarea class="form-input form-textarea" name="shipping_address" required 
                              placeholder="Enter your complete shipping address..."></textarea>
                </div>
                <div class="checkout-actions">
                    <button type="button" class="cancel-btn" id="cancelCheckout">Cancel</button>
                    <button type="submit" class="place-order-btn" id="placeOrderBtn">
                        <ion-icon name="checkmark-circle-outline"></ion-icon>
                        Place Order
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Order Success Modal -->
    <div class="order-success-modal" id="orderSuccessModal">
        <div class="order-success-content">
            <div class="success-icon">
                <ion-icon name="checkmark-outline"></ion-icon>
            </div>
            <h2 class="success-title">Order Placed Successfully!</h2>
            <p class="success-message">Thank you for your purchase.</p>
            <p class="order-number" id="successOrderNumber"></p>
            <button class="success-close-btn" id="successCloseBtn">Continue Shopping</button>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast" style="display: none;">
        <img src="https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?w=56&h=56&fit=crop&crop=center" alt="Product" class="toast-image">
        <div class="toast-content">
            <p class="toast-message">Notification</p>
            <p class="toast-title">Added to cart</p>
            <p class="toast-time">Just now</p>
        </div>
        <button class="toast-close" onclick="hideToast()">
            <ion-icon name="close-outline"></ion-icon>
        </button>
    </div>

    <!-- Scroll to Top -->
    <button class="scroll-to-top" id="scrollToTop">
        <ion-icon name="chevron-up-outline"></ion-icon>
    </button>

    <script>
        // Global variables
        let searchTimeout;
        let currentCategory = '';
        let wishlistCount = 0;

        // Particle system
        function createParticles() {
            const particles = document.getElementById('particles');
            const particleCount = 20;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 6 + 's';
                particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
                particles.appendChild(particle);
            }
        }

        // Enhanced search functionality
        function initAdvancedSearch() {
            const searchField = document.querySelector('.search-field');
            const searchBtn = document.querySelector('.search-btn');

            function performSearch(query = null) {
                const searchQuery = query || searchField.value.trim();

                if (searchQuery.length < 2 && !currentCategory) {
                    showMainProducts();
                    return;
                }

                // Add loading state
                searchBtn.innerHTML = '<div class="spinner"></div>';
                searchBtn.disabled = true;

                const formData = new FormData();
                formData.append('action', 'search');
                formData.append('query', searchQuery);
                formData.append('category', currentCategory);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    searchBtn.innerHTML = '<ion-icon name="search-outline"></ion-icon>';
                    searchBtn.disabled = false;

                    if (data.success) {
                        displaySearchResults(data.products, searchQuery);
                        hideMainProducts();
                    } else {
                        console.error('Search failed:', data.message);
                        showMainProducts();
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                    searchBtn.innerHTML = '<ion-icon name="search-outline"></ion-icon>';
                    searchBtn.disabled = false;
                    showMainProducts();
                });
            }

            // Real-time search
            searchField.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();

                if (query.length >= 2) {
                    searchTimeout = setTimeout(() => performSearch(query), 300);
                } else if (query.length === 0) {
                    showMainProducts();
                }
            });

            searchBtn.addEventListener('click', () => performSearch());
            searchField.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    performSearch();
                }
            });
        }

        function displaySearchResults(products, query) {
            const searchResultsSection = document.getElementById('searchResultsSection');
            const searchResultsGrid = document.getElementById('searchResultsGrid');
            const searchResultsCount = document.getElementById('searchResultsCount');

            searchResultsCount.textContent = `${products.length} product${products.length !== 1 ? 's' : ''} found for "${query}"`;

            if (products.length === 0) {
                searchResultsGrid.innerHTML = `
                    <div class="cart-empty">
                        <ion-icon name="search-outline"></ion-icon>
                        <h3>No products found</h3>
                        <p>Try adjusting your search terms or browse our categories</p>
                    </div>
                `;
            } else {
                searchResultsGrid.innerHTML = products.map((product, index) => `
                    <div class="product-card magnetic" data-product-id="${product.id}" style="animation-delay: ${index * 0.1}s;">
                        <div class="product-image-container">
                            ${product.sale_price && product.sale_price < product.price ? 
                                `<div class="sale-badge animate-pulse">${Math.round(100 - (product.sale_price / product.price) * 100)}% OFF</div>` : ''}

                            <div class="product-actions">
                                <button class="action-icon" title="Add to Wishlist" data-action="wishlist">
                                    <ion-icon name="heart-outline"></ion-icon>
                                </button>
                                <button class="action-icon" title="Quick View" data-action="quickview">
                                    <ion-icon name="eye-outline"></ion-icon>
                                </button>
                                <button class="action-icon" title="Compare" data-action="compare">
                                    <ion-icon name="git-compare-outline"></ion-icon>
                                </button>
                            </div>

                            <img src="${product.product_image || getProductImage()}" 
                                 alt="${product.product_name}" 
                                 class="product-image"
                                 loading="lazy">
                        </div>

                        <div class="product-info">
                            <p class="product-category">${product.category ?? ''}</p>
                            <h3 class="product-title">${product.product_name}</h3>

                            <div class="product-rating">
                                <ion-icon name="star" class="star"></ion-icon>
                                <ion-icon name="star" class="star"></ion-icon>
                                <ion-icon name="star" class="star"></ion-icon>
                                <ion-icon name="star" class="star"></ion-icon>
                                <ion-icon name="star-outline" class="star outline"></ion-icon>
                                <span class="rating-text">(4.2)</span>
                            </div>

                            <div class="price-container">
                                ${product.sale_price && product.sale_price < product.price ? 
                                    `<span class="current-price">$${parseFloat(product.sale_price).toFixed(2)}</span>
                                     <span class="original-price">$${parseFloat(product.price).toFixed(2)}</span>` :
                                    `<span class="current-price">$${parseFloat(product.price).toFixed(2)}</span>`}
                            </div>

                            <button class="add-to-cart" data-product-id="${product.id}" data-product="${product.product_name}">
                                <ion-icon name="bag-add-outline"></ion-icon>
                                Add to Cart
                            </button>
                        </div>
                    </div>
                `).join('');

                // Re-initialize interactions for new products
                initProductInteractions();
                initMagneticEffect();
            }

            searchResultsSection.style.display = 'block';
        }

        function getProductImage() {
            const fashionImages = [
                'https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?w=400&h=400&fit=crop&crop=center',
                'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=400&h=400&fit=crop&crop=center',
                'https://images.unsplash.com/photo-1506629905607-d81034956c7e?w=400&h=400&fit=crop&crop=center',
                'https://images.unsplash.com/photo-1583743089695-4b816a340f82?w=400&h=400&fit=crop&crop=center',
                'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=400&h=400&fit=crop&crop=center',
                'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=400&h=400&fit=crop&crop=center',
                'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=400&h=400&fit=crop&crop=center',
                'https://images.unsplash.com/photo-1472851294608-062f824d29cc?w=400&h=400&fit=crop&crop=center',
                'https://images.unsplash.com/photo-1544441893-675973e31985?w=400&h=400&fit=crop&crop=center',
                'https://images.unsplash.com/photo-1558769132-cb1aea458c5e?w=400&h=400&fit=crop&crop=center',
                'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400&h=400&fit=crop&crop=center',
                'https://images.unsplash.com/photo-1564584217132-2271feaeb3c5?w=400&h=400&fit=crop&crop=center'
            ];
            return fashionImages[Math.floor(Math.random() * fashionImages.length)];
        }

        function showMainProducts() {
            document.getElementById('searchResultsSection').style.display = 'none';
            document.getElementById('horizontalSection').style.display = 'block';
            document.getElementById('products').style.display = 'block';
            document.getElementById('mainTitle').textContent = currentCategory ? 
                `${currentCategory} Products` : 'Latest Products';
        }

        function hideMainProducts() {
            document.getElementById('horizontalSection').style.display = 'none';
            document.getElementById('products').style.display = 'none';
        }

        function clearSearch() {
            document.querySelector('.search-field').value = '';
            currentCategory = '';
            showMainProducts();

            // Remove active state from category links
            document.querySelectorAll('.category-link-sidebar').forEach(link => {
                link.classList.remove('active');
            });
            document.querySelector('.category-link-sidebar[data-category=""]').classList.add('active');
        }

        // Cart functionality
        function initCartSystem() {
            const cartBtn = document.getElementById('cartBtn');
            const cartModal = document.getElementById('cartModal');
            const cartCloseBtn = document.getElementById('cartCloseBtn');

            cartBtn.addEventListener('click', () => {
                loadCart();
                cartModal.classList.add('show');
            });

            cartCloseBtn.addEventListener('click', () => {
                cartModal.classList.remove('show');
            });

            // Close cart when clicking outside
            cartModal.addEventListener('click', (e) => {
                if (e.target === cartModal) {
                    cartModal.classList.remove('show');
                }
            });
        }

        function loadCart() {
            const formData = new FormData();
            formData.append('action', 'get_cart');

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayCartItems(data.items, data.total);
                } else {
                    console.error('Failed to load cart');
                }
            })
            .catch(error => {
                console.error('Cart error:', error);
            });
        }

        function displayCartItems(items, total) {
            const cartItems = document.getElementById('cartItems');
            const cartSummary = document.getElementById('cartSummary');
            const cartTotalAmount = document.getElementById('cartTotalAmount');

            if (items.length === 0) {
                cartItems.innerHTML = `
                    <div class="cart-empty">
                        <ion-icon name="bag-outline"></ion-icon>
                        <h3>Your cart is empty</h3>
                        <p>Add some products to get started</p>
                    </div>
                `;
                cartSummary.style.display = 'none';
                return;
            }

            cartItems.innerHTML = items.map(item => `
                <div class="cart-item" data-cart-id="${item.id}">
                    <img src="${item.product_image}" alt="${item.product_name}" class="cart-item-image">
                    <div class="cart-item-info">
                        <h4 class="cart-item-name">${item.product_name}</h4>
                        <p class="cart-item-category">${item.category ?? ''}</p>
                        <div class="cart-item-controls">
                            <button class="quantity-btn" data-action="decrease">-</button>
                            <span class="quantity-display">${item.quantity}</span>
                            <button class="quantity-btn" data-action="increase">+</button>
                            <button class="remove-item" title="Remove item">
                                <ion-icon name="trash-outline"></ion-icon>
                            </button>
                        </div>
                    </div>
                    <div class="cart-item-price">
                        <div class="item-price">$${(item.subtotal).toFixed(2)}</div>
                        <div class="item-subtotal">$${Number(item.price).toFixed(2)} each</div>
                    </div>
                </div>
            `).join('');

            cartTotalAmount.textContent = 'RWF' + total.toFixed(2);
            cartSummary.style.display = 'block';

            // Add event listeners for cart controls
            initCartControls();
        }

        function initCartControls() {
            document.querySelectorAll('.quantity-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const cartItem = e.target.closest('.cart-item');
                    const cartId = cartItem.dataset.cartId;
                    const action = e.target.dataset.action;
                    const currentQty = parseInt(cartItem.querySelector('.quantity-display').textContent);

                    let newQty = currentQty;
                    if (action === 'increase') {
                        newQty = currentQty + 1;
                    } else if (action === 'decrease') {
                        newQty = Math.max(0, currentQty - 1);
                    }

                    updateCartItem(cartId, newQty);
                });
            });

            document.querySelectorAll('.remove-item').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const cartItem = e.target.closest('.cart-item');
                    const cartId = cartItem.dataset.cartId;
                    updateCartItem(cartId, 0);
                });
            });
        }

