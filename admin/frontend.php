<?php
// ---- DEBUG (remove in production) ----
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
// -------------------------------------

require __DIR__ . '/db_connect.php';

/**
 * If your admin panel stores product images as "uploads/products/xyz.jpg"
 * and THIS file lives in a different folder, set a URL prefix here.
 */
const PUBLIC_UPLOADS_PREFIX = ''; // change to 'admin/' if needed

// ----- helpers -----
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($n) { return '$' . number_format((float)$n, 2); }
function img_or_placeholder(?string $relPath, int $w = 300, int $h = 300): string {
    $relPath = trim((string)$relPath);
    if ($relPath !== '') {
        $try1 = __DIR__ . '/' . $relPath;
        $try2 = __DIR__ . '/' . PUBLIC_UPLOADS_PREFIX . $relPath;
        if (is_file($try1)) return $relPath;
        if (is_file($try2)) return PUBLIC_UPLOADS_PREFIX . $relPath;
    }
    return "https://via.placeholder.com/{$w}x{$h}.png?text=No+Image";
}
function sale_badge(?float $price, ?float $sale): string {
    if ($sale !== null && $sale > 0 && $price > 0 && $sale < $price) {
        $pct = round(100 - ($sale / $price) * 100);
        return "<div class=\"sale-badge\">{$pct}% OFF</div>";
    }
    return '';
}

// ----- data fetch -----
$limitGrid = 12;

// categories + counts (sidebar)
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

// "New Arrivals" (small cards) – recent 8
$stmt = $conn->prepare("SELECT id, product_name, category, price, sale_price, product_image
                        FROM products
                        WHERE status='active'
                        ORDER BY created_at DESC
                        LIMIT 8");
$stmt->execute();
$newArrivals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// "Trending" – by stock qty (fallback to recent if many zeros)
$stmt = $conn->prepare("SELECT id, product_name, category, price, sale_price, product_image, quantity
                        FROM products
                        WHERE status='active'
                        ORDER BY quantity DESC, created_at DESC
                        LIMIT 8");
$stmt->execute();
$trending = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// "Top Rated" – use featured flag as a proxy
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
    <title>Anon - Modern eCommerce</title>
    <link rel="shortcut icon" href="./assets/images/logo/favicon.ico" type="image/x-icon">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
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
            --primary-color: #ff6b9d;
            --secondary-color: #4ecdc4;
            --accent-color: #45b7d1;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --border-light: #e2e8f0;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
            --radius: 12px;
            --radius-lg: 16px;
            --font-primary: 'Inter', sans-serif;
        }
        
        body {
            font-family: var(--font-primary);
            background: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header */
        .header {
            background: var(--white);
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-top {
            background: var(--text-dark);
            color: var(--white);
            padding: 8px 0;
            font-size: 14px;
            text-align: center;
        }
        
        .header-main {
            padding: 16px 0;
        }
        
        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        
        .header-logo {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-dark);
            text-decoration: none;
        }
        
        .header-search {
            flex: 1;
            max-width: 500px;
            position: relative;
        }
        
        .search-field {
            width: 100%;
            padding: 12px 50px 12px 20px;
            border: 2px solid var(--border-light);
            border-radius: var(--radius-lg);
            font-size: 14px;
            background: var(--bg-light);
            transition: all 0.3s ease;
        }
        
        .search-field:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--white);
        }
        
        .search-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary-color);
            border: none;
            padding: 8px;
            border-radius: var(--radius);
            color: var(--white);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .search-btn:hover {
            background: #e55a87;
            transform: translateY(-50%) scale(1.05);
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .action-btn {
            position: relative;
            background: none;
            border: none;
            padding: 8px;
            cursor: pointer;
            color: var(--text-dark);
            font-size: 24px;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            color: var(--primary-color);
            transform: scale(1.1);
        }
        
        .count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--primary-color);
            color: var(--white);
            font-size: 12px;
            padding: 4px 6px;
            border-radius: 50%;
            min-width: 20px;
            text-align: center;
        }
        
        /* Navigation */
        .desktop-navigation-menu {
            background: var(--white);
            border-top: 1px solid var(--border-light);
            padding: 16px 0;
        }
        
        .desktop-menu-category-list {
            display: flex;
            justify-content: center;
            gap: 40px;
            list-style: none;
        }
        
        .menu-category {
            position: relative;
        }
        
        .menu-title {
            font-weight: 600;
            color: var(--text-dark);
            text-decoration: none;
            padding: 8px 0;
            transition: all 0.3s ease;
        }
        
        .menu-title:hover {
            color: var(--primary-color);
        }
        
        /* Hero Banner */
        .banner {
            margin: 20px 0;
        }
        
        .banner-slider {
            position: relative;
            border-radius: var(--radius-lg);
            overflow: hidden;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 400px;
            display: flex;
            align-items: center;
        }
        
        .banner-content {
            padding: 60px;
            color: var(--white);
            max-width: 50%;
        }
        
        .banner-subtitle {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 8px;
            opacity: 0.9;
        }
        
        .banner-title {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 16px;
            line-height: 1.2;
        }
        
        .banner-text {
            font-size: 18px;
            margin-bottom: 32px;
            opacity: 0.95;
        }
        
        .banner-btn {
            display: inline-block;
            background: var(--white);
            color: var(--text-dark);
            padding: 14px 32px;
            border-radius: var(--radius-lg);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
        }
        
        .banner-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        /* Category Icons */
        .category-section {
            margin: 40px 0;
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .category-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .category-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-color);
        }
        
        .category-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 12px;
        }
        
        .category-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 24px;
        }
        
        .category-info h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .category-count {
            color: var(--text-light);
            font-size: 14px;
        }
        
        .category-link {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            font-size: 14px;
        }
        
        /* Main Content Layout */
        .main-content {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 32px;
            margin: 40px 0;
        }
        
        /* Sidebar */
        .sidebar {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        
        .sidebar-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text-dark);
        }
        
        .category-list {
            list-style: none;
        }
        
        .category-item {
            margin-bottom: 12px;
        }
        
        .category-link-sidebar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: var(--bg-light);
            border-radius: var(--radius);
            text-decoration: none;
            color: var(--text-dark);
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        
        .category-link-sidebar:hover {
            background: var(--white);
            border-color: var(--primary-color);
            transform: translateX(4px);
        }
        
        .category-badge {
            background: var(--primary-color);
            color: var(--white);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Product Sections */
        .product-section {
            margin-bottom: 48px;
        }
        
        .section-title {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 24px;
            color: var(--text-dark);
            position: relative;
            display: inline-block;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            border-radius: 2px;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }
        
        .product-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }
        
        .product-image-container {
            position: relative;
            height: 240px;
            background: var(--bg-light);
            overflow: hidden;
        }
        
        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.4s ease;
        }
        
        .product-card:hover .product-image {
            transform: scale(1.05);
        }
        
        .sale-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: var(--primary-color);
            color: var(--white);
            padding: 6px 12px;
            border-radius: var(--radius);
            font-size: 12px;
            font-weight: 700;
            z-index: 2;
        }
        
        .product-actions {
            position: absolute;
            top: 12px;
            right: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            opacity: 0;
            transform: translateX(20px);
            transition: all 0.3s ease;
        }
        
        .product-card:hover .product-actions {
            opacity: 1;
            transform: translateX(0);
        }
        
        .action-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .action-icon:hover {
            background: var(--primary-color);
            color: var(--white);
            transform: scale(1.1);
        }
        
        .product-info {
            padding: 20px;
        }
        
        .product-category {
            color: var(--text-light);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        
        .product-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            line-height: 1.4;
            color: var(--text-dark);
        }
        
        .product-rating {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 12px;
        }
        
        .star {
            color: #fbbf24;
            font-size: 14px;
        }
        
        .star.outline {
            color: var(--border-light);
        }
        
        .price-container {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }
        
        .current-price {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .original-price {
            font-size: 14px;
            color: var(--text-light);
            text-decoration: line-through;
        }
        
        .add-to-cart {
            width: 100%;
            background: var(--text-dark);
            color: var(--white);
            border: none;
            padding: 12px;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .add-to-cart:hover {
            background: var(--primary-color);
            transform: translateY(-2px);
        }
        
        /* Horizontal Product Lists */
        .horizontal-section {
            margin: 48px 0;
        }
        
        .horizontal-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
        }
        
        .horizontal-products {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
        }
        
        .horizontal-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text-dark);
        }
        
        .horizontal-product {
            display: flex;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid var(--border-light);
            transition: all 0.3s ease;
        }
        
        .horizontal-product:last-child {
            border-bottom: none;
        }
        
        .horizontal-product:hover {
            background: var(--bg-light);
            margin: 0 -16px;
            padding: 16px;
            border-radius: var(--radius);
        }
        
        .horizontal-image {
            width: 60px;
            height: 60px;
            border-radius: var(--radius);
            object-fit: cover;
            background: var(--bg-light);
        }
        
        .horizontal-info {
            flex: 1;
        }
        
        .horizontal-name {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
            color: var(--text-dark);
        }
        
        .horizontal-category {
            font-size: 12px;
            color: var(--text-light);
            margin-bottom: 8px;
        }
        
        .horizontal-price {
            font-size: 14px;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 16px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 1000;
            animation: slideIn 0.5s ease;
            max-width: 300px;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .toast-image {
            width: 48px;
            height: 48px;
            border-radius: var(--radius);
            object-fit: cover;
        }
        
        .toast-content {
            flex: 1;
        }
        
        .toast-message {
            font-size: 12px;
            color: var(--text-light);
            margin-bottom: 2px;
        }
        
        .toast-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .toast-time {
            font-size: 12px;
            color: var(--text-light);
        }
        
        .toast-close {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-light);
            font-size: 16px;
        }
        
        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: static;
            }
            
            .horizontal-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 16px;
            }
            
            .desktop-menu-category-list {
                flex-wrap: wrap;
                gap: 20px;
            }
            
            .banner-content {
                padding: 40px 20px;
                max-width: 100%;
            }
            
            .banner-title {
                font-size: 36px;
            }
            
            .product-grid {
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            }
            
            .category-grid {
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            }
        }
        
        @media (max-width: 480px) {
            .product-grid {
                grid-template-columns: 1fr;
            }
            
            .category-grid {
                grid-template-columns: 1fr;
            }
            
            .banner-title {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-top">
            <div class="container">
                FREE SHIPPING THIS WEEK ORDER OVER - $55
            </div>
        </div>
        
        <div class="header-main">
            <div class="container">
                <div class="header-container">
                    <a href="#" class="header-logo">Anon</a>
                    
                    <div class="header-search">
                        <input type="search" name="search" class="search-field" placeholder="Enter your product name...">
                        <button class="search-btn">
                            <ion-icon name="search-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="header-actions">
                        <button class="action-btn">
                            <ion-icon name="person-outline"></ion-icon>
                        </button>
                        <button class="action-btn">
                            <ion-icon name="heart-outline"></ion-icon>
                            <span class="count">0</span>
                        </button>
                        <button class="action-btn">
                            <ion-icon name="bag-handle-outline"></ion-icon>
                            <span class="count">0</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <nav class="desktop-navigation-menu">
            <div class="container">
                <ul class="desktop-menu-category-list">
                    <li class="menu-category">
                        <a href="#" class="menu-title">HOME</a>
                    </li>
                    <li class="menu-category">
                        <a href="#" class="menu-title">CATEGORIES</a>
                    </li>
                    <li class="menu-category">
                        <a href="#" class="menu-title">MEN'S</a>
                    </li>
                    <li class="menu-category">
                        <a href="#" class="menu-title">WOMEN'S</a>
                    </li>
                    <li class="menu-category">
                        <a href="#" class="menu-title">JEWELRY</a>
                    </li>
                    <li class="menu-category">
                        <a href="#" class="menu-title">PERFUME</a>
                    </li>
                    <li class="menu-category">
                        <a href="#" class="menu-title">BLOG</a>
                    </li>
                    <li class="menu-category">
                        <a href="#" class="menu-title">HOT OFFERS</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        <!-- Hero Banner -->
        <section class="banner">
            <div class="container">
                <div class="banner-slider">
                    <div class="banner-content">
                        <p class="banner-subtitle">Trending Item</p>
                        <h1 class="banner-title">WOMEN'S LATEST FASHION SALE</h1>
                        <p class="banner-text">starting at $ <strong>20.00</strong></p>
                        <a href="#" class="banner-btn">SHOP NOW</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Category Icons Section -->
        <section class="category-section">
            <div class="container">
                <div class="category-grid">
                    <div class="category-card">
                        <div class="category-header">
                            <div class="category-icon">
                                <ion-icon name="shirt-outline"></ion-icon>
                            </div>
                            <div class="category-info">
                                <h3>DRESS & FROCK</h3>
                                <p class="category-count">(53)</p>
                            </div>
                        </div>
                        <a href="#" class="category-link">Show All</a>
                    </div>
                    
                    <div class="category-card">
                        <div class="category-header">
                            <div class="category-icon">
                                <ion-icon name="snow-outline"></ion-icon>
                            </div>
                            <div class="category-info">
                                <h3>WINTER WEAR</h3>
                                <p class="category-count">(58)</p>
                            </div>
                        </div>
                        <a href="#" class="category-link">Show All</a>
                    </div>
                    
                    <div class="category-card">
                        <div class="category-header">
                            <div class="category-icon">
                                <ion-icon name="glasses-outline"></ion-icon>
                            </div>
                            <div class="category-info">
                                <h3>GLASSES & LENS</h3>
                                <p class="category-count">(68)</p>
                            </div>
                        </div>
                        <a href="#" class="category-link">Show All</a>
                    </div>
                    
                    <div class="category-card">
                        <div class="category-header">
                            <div class="category-icon">
                                <ion-icon name="bag-outline"></ion-icon>
                            </div>
                            <div class="category-info">
                                <h3>SHORTS & JEANS</h3>
                                <p class="category-count">(84)</p>
                            </div>
                        </div>
                        <a href="#" class="category-link">Show All</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Product Content -->
        <div class="container">
            <div class="main-content">
                <!-- Sidebar -->
                <aside class="sidebar">
                    <h3 class="sidebar-title">CATEGORY</h3>
                    <ul class="category-list">
                        <?php foreach ($categories as $cat): ?>
                            <li class="category-item">
                                <a href="#" class="category-link-sidebar">
                                    <span><?= h($cat['category']) ?></span>
                                    <span class="category-badge"><?= (int)$cat['cnt'] ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </aside>

                <!-- Product Content -->
                <div class="product-content">
                    <!-- Horizontal Product Sections -->
                    <section class="horizontal-section">
                        <div class="horizontal-grid">
                            <!-- New Arrivals -->
                            <div class="horizontal-products">
                                <h3 class="horizontal-title">New Arrivals</h3>
                                <?php foreach (array_slice($newArrivals, 0, 4) as $p): ?>
                                    <div class="horizontal-product">
                                        <img src="<?= h(img_or_placeholder($p['product_image'], 60, 60)) ?>" 
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
                                <h3 class="horizontal-title">Trending</h3>
                                <?php foreach (array_slice($trending, 0, 4) as $p): ?>
                                    <div class="horizontal-product">
                                        <img src="<?= h(img_or_placeholder($p['product_image'], 60, 60)) ?>" 
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
                                <h3 class="horizontal-title">Top Rated</h3>
                                <?php foreach (array_slice($topRated, 0, 4) as $p): ?>
                                    <div class="horizontal-product">
                                        <img src="<?= h(img_or_placeholder($p['product_image'], 60, 60)) ?>" 
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
                    <section class="product-section">
                        <h2 class="section-title">New Products</h2>
                        <div class="product-grid">
                            <?php foreach ($newProducts as $p): ?>
                                <div class="product-card">
                                    <div class="product-image-container">
                                        <?= sale_badge((float)$p['price'], $p['sale_price'] !== null ? (float)$p['sale_price'] : null) ?>
                                        
                                        <div class="product-actions">
                                            <button class="action-icon" title="Add to Wishlist">
                                                <ion-icon name="heart-outline"></ion-icon>
                                            </button>
                                            <button class="action-icon" title="Quick View">
                                                <ion-icon name="eye-outline"></ion-icon>
                                            </button>
                                            <button class="action-icon" title="Compare">
                                                <ion-icon name="git-compare-outline"></ion-icon>
                                            </button>
                                        </div>
                                        
                                        <img src="<?= h(img_or_placeholder($p['product_image'], 300, 240)) ?>" 
                                             alt="<?= h($p['product_name']) ?>" 
                                             class="product-image">
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
                                        </div>
                                        
                                        <div class="price-container">
                                            <?php if (!empty($p['sale_price']) && $p['sale_price'] < $p['price']): ?>
                                                <span class="current-price"><?= h(money($p['sale_price'])) ?></span>
                                                <span class="original-price"><?= h(money($p['price'])) ?></span>
                                            <?php else: ?>
                                                <span class="current-price"><?= h(money($p['price'])) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <button class="add-to-cart">
                                            <ion-icon name="bag-add-outline" style="margin-right: 8px;"></ion-icon>
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
    </main>

    <!-- Toast Notification -->
    <div class="toast" id="toast" style="display: none;">
        <img src="https://via.placeholder.com/48x48.png?text=Product" alt="Product" class="toast-image">
        <div class="toast-content">
            <p class="toast-message">Someone in new just bought</p>
            <p class="toast-title">Rose Gold Earrings</p>
            <p class="toast-time">2 Minutes ago</p>
        </div>
        <button class="toast-close" onclick="hideToast()">
            <ion-icon name="close-outline"></ion-icon>
        </button>
    </div>

    <script>
        // Toast notification functionality
        function showToast() {
            const toast = document.getElementById('toast');
            toast.style.display = 'flex';
            setTimeout(() => {
                hideToast();
            }, 5000);
        }
        
        function hideToast() {
            const toast = document.getElementById('toast');
            toast.style.display = 'none';
        }
        
        // Show toast on page load
        setTimeout(showToast, 2000);
        
        // Product card interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add to cart functionality
            const addToCartBtns = document.querySelectorAll('.add-to-cart');
            addToCartBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Add visual feedback
                    const original = this.innerHTML;
                    this.innerHTML = '<ion-icon name="checkmark-outline" style="margin-right: 8px;"></ion-icon>Added!';
                    this.style.background = 'var(--secondary-color)';
                    
                    setTimeout(() => {
                        this.innerHTML = original;
                        this.style.background = 'var(--text-dark)';
                    }, 2000);
                });
            });
            
            // Wishlist functionality
            const wishlistBtns = document.querySelectorAll('.product-actions .action-icon');
            wishlistBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const icon = this.querySelector('ion-icon');
                    if (icon.name === 'heart-outline') {
                        icon.name = 'heart';
                        this.style.background = 'var(--primary-color)';
                        this.style.color = 'var(--white)';
                    } else if (icon.name === 'heart') {
                        icon.name = 'heart-outline';
                        this.style.background = 'rgba(255, 255, 255, 0.9)';
                        this.style.color = 'var(--text-dark)';
                    }
                });
            });
            
            // Search functionality
            const searchField = document.querySelector('.search-field');
            const searchBtn = document.querySelector('.search-btn');
            
            function performSearch() {
                const query = searchField.value.trim();
                if (query) {
                    console.log('Searching for:', query);
                    // Here you would implement actual search functionality
                    // For now, just show an alert
                    alert(`Searching for: ${query}`);
                }
            }
            
            searchBtn.addEventListener('click', performSearch);
            searchField.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
            
            // Category filtering
            const categoryLinks = document.querySelectorAll('.category-link-sidebar');
            categoryLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all links
                    categoryLinks.forEach(l => l.classList.remove('active'));
                    
                    // Add active class to clicked link
                    this.classList.add('active');
                    
                    const category = this.querySelector('span').textContent;
                    console.log('Filtering by category:', category);
                    // Here you would implement actual filtering
                });
            });
        });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Header scroll effect
        let lastScrollY = window.scrollY;
        window.addEventListener('scroll', () => {
            const header = document.querySelector('.header');
            if (window.scrollY > lastScrollY && window.scrollY > 100) {
                header.style.transform = 'translateY(-100%)';
            } else {
                header.style.transform = 'translateY(0)';
            }
            lastScrollY = window.scrollY;
        });
    </script>
    
    <style>
        /* Additional styles for active states and interactions */
        .category-link-sidebar.active {
            background: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }
        
        .category-link-sidebar.active .category-badge {
            background: var(--white);
            color: var(--primary-color);
        }
        
        .header {
            transition: transform 0.3s ease;
        }
        
        /* Loading animation for images */
        .product-image {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        .product-image[src] {
            animation: none;
            background: none;
        }
        
        @keyframes loading {
            0% {
                background-position: 200% 0;
            }
            100% {
                background-position: -200% 0;
            }
        }
        
        /* Hover effects for better UX */
        .menu-title {
            position: relative;
            overflow: hidden;
        }
        
        .menu-title::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: -100%;
            width: 100%;
            height: 2px;
            background: var(--primary-color);
            transition: left 0.3s ease;
        }
        
        .menu-title:hover::before {
            left: 0;
        }
        
        /* Mobile menu toggle (you can add mobile menu later) */
        @media (max-width: 768px) {
            .desktop-navigation-menu {
                display: none;
            }
            
            .horizontal-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .horizontal-product {
                padding: 12px 0;
            }
            
            .banner-content {
                text-align: center;
            }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--bg-light);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #e55a87;
        }
    </style>
</body>
</html>