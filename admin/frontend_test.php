<?php
// ---- DEBUG (remove in production) ----
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
// -------------------------------------

require __DIR__ . '/db_connect.php';

const PUBLIC_UPLOADS_PREFIX = '';

// ----- helpers -----
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($n) { return '$' . number_format((float)$n, 2); }

// Enhanced image function with hardcoded images
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
        $try2 = __DIR__ . '/' . PUBLIC_UPLOADS_PREFIX . $relPath;
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

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_to_cart') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        $sessionId = session_id() ?: 'anonymous_' . uniqid();
        
        if ($productId > 0) {
            // Check if product exists
            $stmt = $conn->prepare("SELECT id, product_name, price, sale_price FROM products WHERE id = ? AND status = 'active'");
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
                    $newQty = $existing['quantity'] + $quantity;
                    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                    $stmt->bind_param("ii", $newQty, $existing['id']);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    // Insert new cart item
                    $stmt = $conn->prepare("INSERT INTO cart (product_id, session_id, quantity, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->bind_param("isi", $productId, $sessionId, $quantity);
                    $stmt->execute();
                    $stmt->close();
                }
                
                // Get cart count
                $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE session_id = ?");
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
}

// Start session for cart
session_start();
$sessionId = session_id();

// Get cart count
$cartCount = 0;
$stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE session_id = ?");
$stmt->bind_param("s", $sessionId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$cartCount = (int)($result['total'] ?? 0);
$stmt->close();

// ----- data fetch -----
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
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
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(120, 119, 198, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 119, 198, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 60%, rgba(79, 172, 254, 0.1) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
        }
        
        /* Glassmorphism utility */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: var(--blur);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .glass-dark {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: var(--blur);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Floating animations */
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-10px) rotate(1deg); }
            66% { transform: translateY(5px) rotate(-1deg); }
        }
        
        @keyframes glow {
            0%, 100% { box-shadow: 0 0 20px rgba(102, 126, 234, 0.3); }
            50% { box-shadow: 0 0 40px rgba(102, 126, 234, 0.6), 0 0 60px rgba(102, 126, 234, 0.3); }
        }
        
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            transition: var(--transition);
        }
        
        .header-top {
            background: var(--dark);
            color: var(--white);
            padding: 12px 0;
            text-align: center;
            font-weight: 500;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }
        
        .header-top::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shimmer 3s infinite;
        }
        
        .header-main {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: var(--blur);
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 32px;
        }
        
        .header-logo {
            font-family: var(--font-heading);
            font-size: 36px;
            font-weight: 900;
            background: var(--primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            position: relative;
            animation: glow 3s ease-in-out infinite;
        }
        
        .header-search {
            flex: 1;
            max-width: 600px;
            position: relative;
        }
        
        .search-container {
            position: relative;
            background: rgba(255, 255, 255, 0.9);
            border-radius: var(--radius-xl);
            padding: 4px;
            box-shadow: var(--shadow-glass);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .search-field {
            width: 100%;
            padding: 16px 60px 16px 24px;
            border: none;
            border-radius: var(--radius-lg);
            font-size: 16px;
            background: transparent;
            color: var(--dark);
            font-weight: 500;
        }
        
        .search-field::placeholder {
            color: var(--text-light);
        }
        
        .search-field:focus {
            outline: none;
        }
        
        .search-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary);
            border: none;
            padding: 12px;
            border-radius: var(--radius-lg);
            color: var(--white);
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow-glass);
        }
        
        .search-btn:hover {
            transform: translateY(-50%) scale(1.1);
            box-shadow: var(--shadow-hover);
        }
        
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-float);
            border: 1px solid var(--gray-200);
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .search-result-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-bottom: 1px solid var(--gray-100);
            cursor: pointer;
            transition: var(--transition);
        }
        
        .search-result-item:hover {
            background: var(--gray-50);
        }
        
        .search-result-image {
            width: 40px;
            height: 40px;
            border-radius: var(--radius);
            object-fit: cover;
        }
        
        .search-result-info {
            flex: 1;
        }
        
        .search-result-name {
            font-weight: 600;
            margin-bottom: 4px;
            color: var(--dark);
        }
        
        .search-result-category {
            font-size: 12px;
            color: var(--text-light);
        }
        
        .search-result-price {
            font-weight: 700;
            color: var(--primary-solid);
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .action-btn {
            position: relative;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 12px;
            border-radius: var(--radius-lg);
            cursor: pointer;
            color: var(--dark);
            font-size: 20px;
            transition: var(--transition);
            backdrop-filter: var(--blur);
            box-shadow: var(--shadow-glass);
        }
        
        .action-btn:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-4px) scale(1.05);
            box-shadow: var(--shadow-hover);
        }
        
        .count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--secondary);
            color: var(--white);
            font-size: 12px;
            padding: 6px 8px;
            border-radius: 50%;
            min-width: 24px;
            text-align: center;
            font-weight: 700;
            animation: float 3s ease-in-out infinite;
        }
        
        /* Navigation */
        .navigation {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: var(--blur);
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding: 16px 0;
        }
        
        .nav-list {
            display: flex;
            justify-content: center;
            gap: 48px;
            list-style: none;
        }
        
        .nav-link {
            font-family: var(--font-heading);
            font-weight: 600;
            color: var(--dark);
            text-decoration: none;
            padding: 12px 0;
            position: relative;
            transition: var(--transition);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background: var(--primary);
            transition: var(--transition);
            border-radius: 2px;
            transform: translateX(-50%);
        }
        
        .nav-link:hover {
            color: var(--primary-solid);
            transform: translateY(-2px);
        }
        
        .nav-link:hover::before {
            width: 100%;
        }
        
        /* Hero Section */
        .hero {
            margin-top: 140px;
            padding: 80px 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(circle at 30% 30%, rgba(102, 126, 234, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 70% 70%, rgba(245, 87, 108, 0.15) 0%, transparent 50%);
            animation: float 6s ease-in-out infinite;
            z-index: -1;
        }
        
        .hero-content {
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
            animation: slideInUp 1s ease-out;
        }
        
        .hero-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: var(--blur);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 12px 24px;
            border-radius: var(--radius-xl);
            color: var(--white);
            font-weight: 600;
            margin-bottom: 24px;
            font-size: 14px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        
        .hero-title {
            font-family: var(--font-heading);
            font-size: clamp(48px, 8vw, 96px);
            font-weight: 900;
            margin-bottom: 24px;
            line-height: 1.1;
            background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .hero-subtitle {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 40px;
            font-weight: 400;
        }
        
        .hero-cta {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: var(--white);
            color: var(--dark);
            padding: 18px 36px;
            border-radius: var(--radius-xl);
            font-weight: 700;
            text-decoration: none;
            font-size: 16px;
            transition: var(--transition);
            box-shadow: var(--shadow-float);
            position: relative;
            overflow: hidden;
        }
        
        .hero-cta::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: var(--transition);
        }
        
        .hero-cta:hover {
            transform: translateY(-6px) scale(1.05);
            box-shadow: var(--shadow-hover);
        }
        
        .hero-cta:hover::before {
            left: 100%;
        }
        
        /* Category Section */
        .category-section {
            padding: 80px 0;
            position: relative;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-title {
            font-family: var(--font-heading);
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 16px;
            background: var(--primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
        }
        
        .section-subtitle {
            font-size: 18px;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 32px;
        }
        
        .category-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: var(--blur);
            border-radius: var(--radius-xl);
            padding: 32px;
            box-shadow: var(--shadow-glass);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: var(--transition);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            animation: slideInUp 0.8s ease-out;
            animation-fill-mode: both;
        }
        
        .category-card:nth-child(2) { animation-delay: 0.1s; }
        .category-card:nth-child(3) { animation-delay: 0.2s; }
        .category-card:nth-child(4) { animation-delay: 0.3s; }
        
        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transition: var(--transition);
        }
        
        .category-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: var(--shadow-hover);
            border-color: rgba(102, 126, 234, 0.3);
        }
        
        .category-card:hover::before {
            left: 100%;
        }
        
        .category-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .category-icon {
            width: 64px;
            height: 64px;
            background: var(--primary);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 28px;
            box-shadow: var(--shadow-glass);
            transition: var(--transition);
        }
        
        .category-card:hover .category-icon {
            transform: rotateY(180deg) scale(1.1);
            animation: glow 1s ease-in-out;
        }
        
        .category-info h3 {
            font-family: var(--font-heading);
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--dark);
        }
        
        .category-count {
            color: var(--text-light);
            font-size: 16px;
            font-weight: 500;
        }
        
        .category-link {
            color: var(--primary-solid);
            font-weight: 700;
            text-decoration: none;
            font-size: 16px;
            position: relative;
        }
        
        .category-link::after {
            content: '→';
            margin-left: 8px;
            transition: var(--transition);
        }
        
        .category-card:hover .category-link::after {
            transform: translateX(8px);
        }
        
        /* Main Content */
        .main-content {
            background: var(--white);
            margin: 40px 0;
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-float);
            min-height: 100vh;
        }
        
        .content-layout {
            display: grid;
            grid-template-columns: 320px 1fr;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            background: linear-gradient(180deg, var(--gray-50) 0%, var(--gray-100) 100%);
            padding: 40px 32px;
            border-right: 1px solid var(--gray-200);
            position: relative;
        }
        
        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary);
        }
        
        .sidebar-title {
            font-family: var(--font-heading);
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 32px;
            color: var(--dark);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .category-list {
            list-style: none;
            gap: 8px;
            display: flex;
            flex-direction: column;
        }
        
        .category-item {
            transition: var(--transition);
        }
        
        .category-link-sidebar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: var(--white);
            border-radius: var(--radius-lg);
            text-decoration: none;
            color: var(--text);
            transition: var(--transition);
            border: 1px solid var(--gray-200);
            position: relative;
            overflow: hidden;
            font-weight: 500;
        }
        
        .category-link-sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--primary);
            transition: var(--transition);
            z-index: -1;
        }
        
        .category-link-sidebar:hover {
            color: var(--white);
            transform: translateX(8px);
            box-shadow: var(--shadow-glass);
        }
        
        .category-link-sidebar:hover::before {
            left: 0;
        }
        
        .category-badge {
            background: var(--gray-200);
            color: var(--text);
            padding: 6px 12px;
            border-radius: var(--radius-lg);
            font-size: 12px;
            font-weight: 700;
            transition: var(--transition);
        }
        
        .category-link-sidebar:hover .category-badge {
            background: rgba(255, 255, 255, 0.3);
            color: var(--white);
        }
        
        /* Product Content Area */
        .product-content {
            padding: 40px;
            background: var(--white);
        }
        
        /* Horizontal Product Sections */
        .horizontal-section {
            margin-bottom: 60px;
        }
        
        .horizontal-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
        }
        
        .horizontal-products {
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%);
            border-radius: var(--radius-xl);
            padding: 32px;
            box-shadow: var(--shadow-glass);
            border: 1px solid var(--gray-200);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }
        
        .horizontal-products::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--accent);
            border-radius: var(--radius-xl) var(--radius-xl) 0 0;
        }
        
        .horizontal-products:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
        }
        
        .horizontal-title {
            font-family: var(--font-heading);
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 24px;
            color: var(--dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .horizontal-product {
            display: flex;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid var(--gray-200);
            transition: var(--transition);
            border-radius: var(--radius);
        }
        
        .horizontal-product:last-child {
            border-bottom: none;
        }
        
        .horizontal-product:hover {
            background: var(--white);
            margin: 0 -16px;
            padding: 16px;
            box-shadow: var(--shadow-glass);
            transform: scale(1.02);
        }
        
        .horizontal-image {
            width: 64px;
            height: 64px;
            border-radius: var(--radius-lg);
            object-fit: cover;
            background: var(--gray-100);
            box-shadow: var(--shadow-glass);
            transition: var(--transition);
        }
        
        .horizontal-product:hover .horizontal-image {
            transform: scale(1.1) rotate(5deg);
        }
        
        .horizontal-info {
            flex: 1;
        }
        
        .horizontal-name {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 4px;
            color: var(--dark);
            line-height: 1.3;
        }
        
        .horizontal-category {
            font-size: 12px;
            color: var(--text-light);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .horizontal-price {
            font-size: 16px;
            font-weight: 700;
            background: var(--primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Main Product Grid */
        .product-section {
            margin: 60px 0;
        }
        
        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }
        
        .products-title {
            font-family: var(--font-heading);
            font-size: 36px;
            font-weight: 800;
            background: var(--primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
        }
        
        .products-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 80px;
            height: 4px;
            background: var(--accent);
            border-radius: 2px;
            animation: shimmer 2s infinite;
        }
        
        .view-all-btn {
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary-solid);
            border: 1px solid rgba(102, 126, 234, 0.3);
            padding: 12px 24px;
            border-radius: var(--radius-lg);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            backdrop-filter: var(--blur);
        }
        
        .view-all-btn:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow-glass);
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 32px;
        }
        
        .product-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-glass);
            border: 1px solid var(--gray-200);
            transition: var(--transition);
            position: relative;
            cursor: pointer;
            animation: slideInUp 0.6s ease-out;
            animation-fill-mode: both;
        }
        
        .product-card:nth-child(odd) { animation-delay: 0.1s; }
        .product-card:nth-child(even) { animation-delay: 0.2s; }
        
        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--primary);
            opacity: 0;
            transition: var(--transition);
            z-index: -1;
        }
        
        .product-card:hover {
            transform: translateY(-12px) rotateX(5deg);
            box-shadow: var(--shadow-hover);
            border-color: rgba(102, 126, 234, 0.3);
        }
        
        .product-card:hover::before {
            opacity: 0.05;
        }
        
        .product-image-container {
            position: relative;
            height: 280px;
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            overflow: hidden;
            border-radius: var(--radius-xl) var(--radius-xl) 0 0;
        }
        
        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
            filter: brightness(1.05) saturate(1.1);
        }
        
        .product-card:hover .product-image {
            transform: scale(1.15) rotate(2deg);
            filter: brightness(1.1) saturate(1.2);
        }
        
        .sale-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            background: var(--secondary);
            color: var(--white);
            padding: 8px 16px;
            border-radius: var(--radius-lg);
            font-size: 12px;
            font-weight: 800;
            z-index: 2;
            box-shadow: var(--shadow-glass);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .product-actions {
            position: absolute;
            top: 16px;
            right: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            opacity: 0;
            transform: translateX(30px) scale(0.8);
            transition: var(--transition);
        }
        
        .product-card:hover .product-actions {
            opacity: 1;
            transform: translateX(0) scale(1);
        }
        
        .action-icon {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            backdrop-filter: var(--blur);
            box-shadow: var(--shadow-glass);
            color: var(--text);
            font-size: 18px;
        }
        
        .action-icon:hover {
            background: var(--primary);
            color: var(--white);
            transform: scale(1.2) rotate(360deg);
            box-shadow: var(--shadow-hover);
        }
        
        .product-info {
            padding: 28px;
            background: var(--white);
        }
        
        .product-category {
            color: var(--text-light);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .product-title {
            font-family: var(--font-heading);
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 12px;
            line-height: 1.4;
            color: var(--dark);
            min-height: 50px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-rating {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 16px;
        }
        
        .star {
            color: #fbbf24;
            font-size: 16px;
            filter: drop-shadow(0 2px 4px rgba(251, 191, 36, 0.3));
        }
        
        .star.outline {
            color: var(--gray-300);
        }
        
        .price-container {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .current-price {
            font-family: var(--font-heading);
            font-size: 24px;
            font-weight: 800;
            background: var(--primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .original-price {
            font-size: 16px;
            color: var(--text-light);
            text-decoration: line-through;
            font-weight: 500;
        }
        
        .add-to-cart {
            width: 100%;
            background: var(--dark);
            color: var(--white);
            border: none;
            padding: 16px;
            border-radius: var(--radius-lg);
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .add-to-cart::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--primary);
            transition: var(--transition);
        }
        
        .add-to-cart:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-hover);
        }
        
        .add-to-cart:hover::before {
            left: 0;
        }
        
        .add-to-cart:active {
            transform: translateY(-2px) scale(0.98);
        }
        
        .add-to-cart:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        /* Loading spinner */
        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid var(--white);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 32px;
            left: 32px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: var(--blur);
            border-radius: var(--radius-xl);
            padding: 20px;
            box-shadow: var(--shadow-float);
            border: 1px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            gap: 16px;
            z-index: 1000;
            animation: slideInLeft 0.6s var(--bounce);
            max-width: 360px;
            min-width: 300px;
        }
        
        @keyframes slideInLeft {
            from {
                transform: translateX(-120%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .toast-image {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-lg);
            object-fit: cover;
            box-shadow: var(--shadow-glass);
        }
        
        .toast-content {
            flex: 1;
        }
        
        .toast-message {
            font-size: 12px;
            color: var(--text-light);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .toast-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 4px;
            color: var(--dark);
        }
        
        .toast-time {
            font-size: 12px;
            color: var(--text-lighter);
            font-weight: 500;
        }
        
        .toast-close {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-light);
            font-size: 18px;
            padding: 8px;
            border-radius: 50%;
            transition: var(--transition);
        }
        
        .toast-close:hover {
            background: var(--gray-100);
            color: var(--dark);
            transform: scale(1.1);
        }
        
        /* Loading States */
        .loading {
            background: linear-gradient(90deg, var(--gray-200) 25%, var(--gray-100) 50%, var(--gray-200) 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }
        
        /* Scroll to top button */
        .scroll-to-top {
            position: fixed;
            bottom: 32px;
            right: 32px;
            width: 56px;
            height: 56px;
            background: var(--primary);
            border: none;
            border-radius: 50%;
            color: var(--white);
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow-float);
            z-index: 999;
            opacity: 0;
            transform: scale(0);
        }
        
        .scroll-to-top.visible {
            opacity: 1;
            transform: scale(1);
        }
        
        .scroll-to-top:hover {
            transform: scale(1.1) translateY(-4px);
            box-shadow: var(--shadow-hover);
            animation: glow 1s ease-in-out;
        }
        
        /* Search Results Grid */
        .search-results-section {
            margin: 40px 0;
            display: none;
        }
        
        .search-results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding: 20px 24px;
            background: var(--gray-50);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-200);
        }
        
        .search-results-title {
            font-family: var(--font-heading);
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
        }
        
        .search-results-count {
            color: var(--text-light);
            font-weight: 500;
        }
        
        .clear-search {
            background: var(--danger);
            color: var(--white);
            border: none;
            padding: 8px 16px;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .clear-search:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-glass);
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-light);
        }
        
        .empty-state ion-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        /* Particle effect */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(102, 126, 234, 0.4);
            border-radius: 50%;
            animation: floatParticle 6s infinite ease-in-out;
        }
        
        @keyframes floatParticle {
            0%, 100% {
                transform: translateY(0px) translateX(0px) scale(1);
                opacity: 0.7;
            }
            50% {
                transform: translateY(-20px) translateX(10px) scale(1.2);
                opacity: 1;
            }
        }
        
        /* Mobile Responsive */
        @media (max-width: 1200px) {
            .content-layout {
                grid-template-columns: 280px 1fr;
            }
            
            .horizontal-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }
        }
        
        @media (max-width: 1024px) {
            .content-layout {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                border-right: none;
                border-bottom: 1px solid var(--gray-200);
            }
            
            .nav-list {
                gap: 24px;
                flex-wrap: wrap;
            }
        }
        
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 20px;
            }
            
            .hero {
                margin-top: 200px;
                padding: 60px 0;
            }
            
            .hero-title {
                font-size: 48px;
            }
            
            .product-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 24px;
            }
            
            .category-grid {
                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
                gap: 24px;
            }
            
            .nav-list {
                gap: 16px;
                justify-content: flex-start;
                overflow-x: auto;
                padding-bottom: 8px;
            }
            
            .nav-link {
                white-space: nowrap;
                font-size: 14px;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 0 16px;
            }
            
            .product-content {
                padding: 24px;
            }
            
            .product-grid {
                grid-template-columns: 1fr;
            }
            
            .category-grid {
                grid-template-columns: 1fr;
            }
            
            .hero-title {
                font-size: 36px;
            }
            
            .section-title {
                font-size: 32px;
            }
        }
        
        /* Rating text styles */
        .rating-text {
            margin-left: 8px;
            font-size: 13px;
            color: var(--text-light);
            font-weight: 500;
        }
        
        .active {
            background: var(--primary) !important;
            color: var(--white) !important;
            transform: translateX(8px);
        }
        
        .active .category-badge {
            background: rgba(255, 255, 255, 0.3) !important;
            color: var(--white) !important;
        }
        
        .active::before {
            left: 0 !important;
        }
        
        /* Heart particles animation */
        @keyframes heartFloat {
            0% {
                opacity: 1;
                transform: translate(0, 0) scale(1);
            }
            100% {
                opacity: 0;
                transform: translate(var(--random-x, 0), -150px) scale(0.3);
            }
        }
        
        @keyframes slideOutDown {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(30px);
            }
        }
    </style>
</head>
<body>
    <!-- Particle Background -->
    <div class="particles" id="particles"></div>
    
    <!-- Header -->
    <header class="header">
        <div class="header-top">
            <div class="container">
                ✨ FREE SHIPPING THIS WEEK ORDER OVER - $55 ✨
            </div>
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
                            <div class="search-results" id="searchResults"></div>
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
                        <button class="action-btn" title="Shopping Cart">
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
                    <p class="hero-subtitle">Discover premium quality fashion at unbeatable prices<br>starting at <strong>$20.00</strong></p>
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
                            <div class="category-icon">
                                <ion-icon name="shirt-outline"></ion-icon>
                            </div>
                            <div class="category-info">
                                <h3>DRESS & FROCK</h3>
                                <p class="category-count">(53 items)</p>
                            </div>
                        </div>
                        <a href="#" class="category-link">Explore Collection</a>
                    </div>
                    
                    <div class="category-card" data-category="Winter">
                        <div class="category-header">
                            <div class="category-icon">
                                <ion-icon name="snow-outline"></ion-icon>
                            </div>
                            <div class="category-info">
                                <h3>WINTER WEAR</h3>
                                <p class="category-count">(58 items)</p>
                            </div>
                        </div>
                        <a href="#" class="category-link">Explore Collection</a>
                    </div>
                    
                    <div class="category-card" data-category="Accessories">
                        <div class="category-header">
                            <div class="category-icon">
                                <ion-icon name="glasses-outline"></ion-icon>
                            </div>
                            <div class="category-info">
                                <h3>GLASSES & LENS</h3>
                                <p class="category-count">(68 items)</p>
                            </div>
                        </div>
                        <a href="#" class="category-link">Explore Collection</a>
                    </div>
                    
                    <div class="category-card" data-category="Jeans">
                        <div class="category-header">
                            <div class="category-icon">
                                <ion-icon name="bag-outline"></ion-icon>
                            </div>
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
                                <a href="#" class="category-link-sidebar" data-category="">
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
                                            <img src="<?= h(img_or_placeholder($p['product_image'], 64, 64)) ?>" 
                                                 alt="<?= h($p['product_name']) ?>" 
                                                 class="horizontal-image">
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
                                            <img src="<?= h(img_or_placeholder($p['product_image'], 64, 64)) ?>" 
                                                 alt="<?= h($p['product_name']) ?>" 
                                                 class="horizontal-image">
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
                                            <img src="<?= h(img_or_placeholder($p['product_image'], 64, 64)) ?>" 
                                                 alt="<?= h($p['product_name']) ?>" 
                                                 class="horizontal-image">
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

    <!-- Toast Notification -->
    <div class="toast" id="toast" style="display: none;">
        <img src="https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?w=56&h=56&fit=crop&crop=center" alt="Product" class="toast-image">
        <div class="toast-content">
            <p class="toast-message">Someone just bought</p>
            <p class="toast-title">Rose Gold Earrings</p>
            <p class="toast-time">2 minutes ago</p>
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
            const searchResults = document.getElementById('searchResults');
            
            function performSearch(query = null) {
                const searchQuery = query || searchField.value.trim();
                
                if (searchQuery.length < 2 && !currentCategory) {
                    searchResults.style.display = 'none';
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
                    body: formData
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
                    searchResults.style.display = 'none';
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
            
            // Hide search results when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.search-container')) {
                    searchResults.style.display = 'none';
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
                    <div class="empty-state">
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
                            
                            <img src="${getProductImage()}" 
                                 alt="${product.product_name}" 
                                 class="product-image"
                                 loading="lazy">
                        </div>
                        
                        <div class="product-info">
                            <p class="product-category">${product.category}</p>
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
                                    `<span class="current-price">${parseFloat(product.sale_price).toFixed(2)}</span>
                                     <span class="original-price">${parseFloat(product.price).toFixed(2)}</span>` :
                                    `<span class="current-price">${parseFloat(product.price).toFixed(2)}</span>`}
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
        }
        
        // Add to cart functionality
        function addToCart(productId, productName, button) {
            // Disable button and show loading
            button.disabled = true;
            const originalContent = button.innerHTML;
            button.innerHTML = '<div class="spinner"></div> Adding...';
            
            const formData = new FormData();
            formData.append('action', 'add_to_cart');
            formData.append('product_id', productId);
            formData.append('quantity', '1');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Success animation
                    button.innerHTML = '<ion-icon name="checkmark-circle-outline"></ion-icon> Added!';
                    button.style.background = 'var(--success)';
                    button.style.transform = 'scale(1.05)';
                    
                    // Update cart count
                    document.getElementById('cartCount').textContent = data.cart_count;
                    document.getElementById('cartCount').style.animation = 'float 0.6s ease-out';
                    
                    // Show success toast
                    showCartToast(productName);
                    
                    // Create floating success indicator
                    createSuccessParticles(button);
                    
                    setTimeout(() => {
                        button.innerHTML = originalContent;
                        button.style.background = 'var(--dark)';
                        button.style.transform = '';
                        button.disabled = false;
                    }, 2500);
                } else {
                    // Error state
                    button.innerHTML = '<ion-icon name="alert-circle-outline"></ion-icon> Error';
                    button.style.background = 'var(--danger)';
                    
                    setTimeout(() => {
                        button.innerHTML = originalContent;
                        button.style.background = 'var(--dark)';
                        button.disabled = false;
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Add to cart error:', error);
                button.innerHTML = '<ion-icon name="alert-circle-outline"></ion-icon> Error';
                button.style.background = 'var(--danger)';
                
                setTimeout(() => {
                    button.innerHTML = originalContent;
                    button.style.background = 'var(--dark)';
                    button.disabled = false;
                }, 2000);
            });
        }
        
        function createSuccessParticles(element) {
            const rect = element.getBoundingClientRect();
            const particles = ['✨', '🛍️', '💫', '⭐'];
            
            for (let i = 0; i < 6; i++) {
                const particle = document.createElement('div');
                particle.textContent = particles[Math.floor(Math.random() * particles.length)];
                particle.style.cssText = `
                    position: fixed;
                    left: ${rect.left + rect.width/2}px;
                    top: ${rect.top + rect.height/2}px;
                    pointer-events: none;
                    z-index: 1000;
                    font-size: 20px;
                    animation: particleFloat 1.2s ease-out forwards;
                    animation-delay: ${i * 0.1}s;
                `;
                document.body.appendChild(particle);
                
                setTimeout(() => particle.remove(), 1200);
            }
        }
        
        // Add particle animation
        const particleStyles = `
            @keyframes particleFloat {
                0% {
                    opacity: 1;
                    transform: translate(0, 0) scale(1) rotate(0deg);
                }
                100% {
                    opacity: 0;
                    transform: translate(${Math.random() * 200 - 100}px, -120px) scale(0.3) rotate(360deg);
                }
            }
        `;
        
        function showCartToast(productName) {
            const toast = document.getElementById('toast');
            toast.querySelector('.toast-message').textContent = 'Added to cart';
            toast.querySelector('.toast-title').textContent = productName;
            toast.querySelector('.toast-time').textContent = 'Just now';
            toast.querySelector('.toast-image').src = getProductImage();
            
            toast.style.display = 'flex';
            
            setTimeout(() => {
                hideToast();
            }, 4000);
        }
        
        function hideToast() {
            const toast = document.getElementById('toast');
            toast.style.animation = 'slideInLeft 0.4s var(--bounce) reverse';
            setTimeout(() => {
                toast.style.display = 'none';
                toast.style.animation = '';
            }, 400);
        }
        
        // // Magnetic effect for cards
        // function initMagneticEffect() {
        //     const magneticElements = document.querySelectorAll('.magnetic');
            
        //     magneticElements.forEach(el => {
        //         el.addEventListener('mousemove', (e) => {
        //             const rect = el.getBoundingClientRect();
        //             const x = e.clientX - rect.left - rect.width / 2;
        //             const y = e.client data-category="">Home</a></li>
        //             <li><a href="#" class="nav-link" data-category="">Categories</a></li>
        //             <li><a href="#" class="nav-link" data-category="Men's">Men's</a></li>
        //             <li><a href="#" class="nav-link" data-category="Women's">Women's</a></li>
        //             <li><a href="#" class="nav-link"        // Magnetic effect for cards
        function initMagneticEffect() {
            const els = document.querySelectorAll('.magnetic');
            els.forEach(el => {
                const strength = 12;
                el.addEventListener('mousemove', (e) => {
                    const rect = el.getBoundingClientRect();
                    const x = e.clientX - rect.left - rect.width / 2;
                    const y = e.clientY - rect.top - rect.height / 2;
                    el.style.transform = `translateY(-8px) rotateX(5deg) translate(${(x/rect.width)*strength}px, ${(y/rect.height)*strength}px)`;
                });
                el.addEventListener('mouseleave', () => {
                    el.style.transform = '';
                });
            });
        }

        // Wire up product card interactions (cart, wishlist, quickview, compare)
        function initProductInteractions() {
            // Add to cart
            document.querySelectorAll('.add-to-cart').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.getAttribute('data-product-id');
                    const name = btn.getAttribute('data-product') ||
                                 btn.closest('.product-card')?.querySelector('.product-title')?.textContent?.trim() ||
                                 'Product';
                    addToCart(id, name, btn);
                });
            });

            // Wishlist / quickview / compare
            document.querySelectorAll('.product-actions .action-icon').forEach(iconBtn => {
                iconBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const action = iconBtn.getAttribute('data-action');

                    if (action === 'wishlist') {
                        wishlistCount += 1;
                        const wc = document.getElementById('wishlistCount');
                        wc.textContent = wishlistCount;
                        wc.style.animation = 'float 0.8s ease-in-out';
                        setTimeout(() => wc.style.animation = '', 900);
                        // cute hearts
                        for (let i = 0; i < 5; i++) {
                            const heart = document.createElement('div');
                            heart.textContent = '❤';
                            heart.style.cssText = `
                              position: fixed; z-index: 1000; font-size: 18px; color:#ef476f;
                              left:${iconBtn.getBoundingClientRect().left + 16}px;
                              top:${iconBtn.getBoundingClientRect().top + 16}px;
                              animation: heartFloat 1.2s ease-out forwards;
                              --random-x:${(Math.random()*120 - 60)}px;
                            `;
                            document.body.appendChild(heart);
                            setTimeout(()=>heart.remove(), 1200);
                        }
                    } else if (action === 'quickview') {
                        showCartToast('Quick view opened');
                    } else if (action === 'compare') {
                        showCartToast('Added to compare');
                    }
                });
            });
        }

        // Filter by category (sidebar, nav, tiles)
        function searchByCategory(category) {
            currentCategory = category || '';
            // Set active states (sidebar)
            document.querySelectorAll('.category-link-sidebar').forEach(a => {
                const isActive = a.getAttribute('data-category') === currentCategory;
                a.classList.toggle('active', isActive);
            });
            // If no category, show main sections
            if (!currentCategory) {
                showMainProducts();
                return;
            }

            // Fetch via backend search handler
            const formData = new FormData();
            formData.append('action', 'search');
            formData.append('query', '');
            formData.append('category', currentCategory);

            fetch(window.location.href, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        displaySearchResults(data.products, currentCategory);
                        hideMainProducts();
                    } else {
                        // empty state
                        displaySearchResults([], currentCategory);
                        hideMainProducts();
                    }
                })
                .catch(() => {
                    // Fallback to main products on error
                    showMainProducts();
                });
        }

        // Bind all category filter sources
        function bindCategoryFilters() {
            // Sidebar items
            document.querySelectorAll('.category-link-sidebar').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    searchByCategory(link.getAttribute('data-category') || '');
                });
            });

            // Top nav items (those with data-category)
            document.querySelectorAll('.nav-link[data-category]').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    searchByCategory(link.getAttribute('data-category') || '');
                });
            });

            // Category cards (tiles)
            document.querySelectorAll('.category-card[data-category]').forEach(card => {
                card.addEventListener('click', (e) => {
                    e.preventDefault();
                    searchByCategory(card.getAttribute('data-category') || '');
                    // scroll down to results
                    document.getElementById('searchResultsSection').scrollIntoView({ behavior: 'smooth' });
                });
            });

            // Default "All Products" active on load
            const allLink = document.querySelector('.category-link-sidebar[data-category=""]');
            if (allLink) allLink.classList.add('active');
        }

        // Scroll to top button
        (function initScrollToTop() {
            const btn = document.getElementById('scrollToTop');
            window.addEventListener('scroll', () => {
                if (window.scrollY > 400) {
                    btn.classList.add('visible');
                } else {
                    btn.classList.remove('visible');
                }
            });
            btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
        })();

        // ===== Boot =====
        document.addEventListener('DOMContentLoaded', () => {
            // inject particle animation keyframes
            const style = document.createElement('style');
            style.textContent = particleStyles;
            document.head.appendChild(style);

            createParticles();
            initAdvancedSearch();
            initProductInteractions();
            initMagneticEffect();
            bindCategoryFilters();
        });
    </script>
</body>
</html>
