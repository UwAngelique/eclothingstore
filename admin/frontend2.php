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
        return "<div class=\"sale-badge animate-pulse\">{$pct}% OFF</div>";
    }
    return '';
}

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
        
        /* Advanced hover effects */
        .magnetic {
            transition: var(--transition);
        }
        
        /* Intersection Observer animations */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: var(--transition);
        }
        
        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Custom cursor effects */
        .interactive {
            cursor: pointer;
        }
        
        .interactive:hover {
            cursor: none;
        }
        
        /* Glitch effect for special elements */
        .glitch {
            position: relative;
        }
        
        .glitch:hover::before,
        .glitch:hover::after {
            content: attr(data-text);
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--white);
        }
        
        .glitch:hover::before {
            animation: glitch-1 0.3s ease-in-out;
            color: #ff0040;
            z-index: -1;
        }
        
        .glitch:hover::after {
            animation: glitch-2 0.3s ease-in-out;
            color: #00ff40;
            z-index: -2;
        }
        
        @keyframes glitch-1 {
            0%, 100% { transform: translate(0); }
            20% { transform: translate(-2px, 2px); }
            40% { transform: translate(-2px, -2px); }
            60% { transform: translate(2px, 2px); }
            80% { transform: translate(2px, -2px); }
        }
        
        @keyframes glitch-2 {
            0%, 100% { transform: translate(0); }
            20% { transform: translate(2px, -2px); }
            40% { transform: translate(2px, 2px); }
            60% { transform: translate(-2px, -2px); }
            80% { transform: translate(-2px, 2px); }
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
                            <input type="search" name="search" class="search-field" placeholder="Enter your product name...">
                            <button class="search-btn">
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
                            <span class="count">0</span>
                        </button>
                        <button class="action-btn" title="Shopping Cart">
                            <ion-icon name="bag-handle-outline"></ion-icon>
                            <span class="count">0</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <nav class="navigation">
            <div class="container">
                <ul class="nav-list">
                    <li><a href="#" class="nav-link">Home</a></li>
                    <li><a href="#" class="nav-link">Categories</a></li>
                    <li><a href="#" class="nav-link">Men's</a></li>
                    <li><a href="#" class="nav-link">Women's</a></li>
                    <li><a href="#" class="nav-link">Jewelry</a></li>
                    <li><a href="#" class="nav-link">Perfume</a></li>
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
                    <div class="category-card">
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
                    
                    <div class="category-card">
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
                    
                    <div class="category-card">
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
                    
                    <div class="category-card">
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
                        <section class="horizontal-section fade-in">
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
                                <h2 class="products-title">Latest Products</h2>
                                <a href="#" class="view-all-btn">View All Products</a>
                            </div>
                            <div class="product-grid">
                                <?php foreach ($newProducts as $p): ?>
                                    <div class="product-card magnetic" data-product-id="<?= h($p['id']) ?>">
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
                                            
                                            <button class="add-to-cart" data-product="<?= h($p['product_name']) ?>">
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
        <img src="https://via.placeholder.com/56x56.png?text=💍" alt="Product" class="toast-image">
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
        
        // Magnetic effect for cards
        function initMagneticEffect() {
            const magneticElements = document.querySelectorAll('.magnetic');
            
            magneticElements.forEach(el => {
                el.addEventListener('mousemove', (e) => {
                    const rect = el.getBoundingClientRect();
                    const x = e.clientX - rect.left - rect.width / 2;
                    const y = e.clientY - rect.top - rect.height / 2;
                    
                    const intensity = 0.3;
                    el.style.transform = `translate(${x * intensity}px, ${y * intensity}px) scale(1.02)`;
                });
                
                el.addEventListener('mouseleave', () => {
                    el.style.transform = 'translate(0, 0) scale(1)';
                });
            });
        }
        
        // Intersection Observer for animations
        function initScrollAnimations() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, { threshold: 0.1 });
            
            document.querySelectorAll('.fade-in').forEach(el => {
                observer.observe(el);
            });
        }
        
        // Toast notification system
        const toastMessages = [
            { name: 'Rose Gold Earrings', time: '2 minutes' },
            { name: 'Leather Handbag', time: '5 minutes' },
            { name: 'Designer Sunglasses', time: '8 minutes' },
            { name: 'Silk Scarf', time: '12 minutes' }
        ];
        
        let toastIndex = 0;
        
        function showToast() {
            const toast = document.getElementById('toast');
            const message = toastMessages[toastIndex % toastMessages.length];
            
            toast.querySelector('.toast-title').textContent = message.name;
            toast.querySelector('.toast-time').textContent = message.time + ' ago';
            
            toast.style.display = 'flex';
            
            setTimeout(() => {
                hideToast();
                toastIndex++;
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
        
        // Scroll to top functionality
        function initScrollToTop() {
            const scrollBtn = document.getElementById('scrollToTop');
            
            window.addEventListener('scroll', () => {
                if (window.scrollY > 500) {
                    scrollBtn.classList.add('visible');
                } else {
                    scrollBtn.classList.remove('visible');
                }
            });
            
            scrollBtn.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
        
        // Enhanced product interactions
        function initProductInteractions() {
            // Add to cart with premium feedback
            const addToCartBtns = document.querySelectorAll('.add-to-cart');
            addToCartBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const productName = this.dataset.product;
                    const original = this.innerHTML;
                    
                    // Success animation
                    this.innerHTML = '<ion-icon name="checkmark-circle-outline"></ion-icon> Added!';
                    this.style.background = 'var(--success)';
                    this.style.transform = 'scale(1.05)';
                    
                    // Create floating success indicator
                    const success = document.createElement('div');
                    success.textContent = '✨ Added to cart!';
                    success.style.cssText = `
                        position: absolute;
                        top: -40px;
                        left: 50%;
                        transform: translateX(-50%);
                        background: var(--success);
                        color: white;
                        padding: 8px 16px;
                        border-radius: 20px;
                        font-size: 12px;
                        font-weight: 600;
                        pointer-events: none;
                        animation: slideInUp 0.3s ease-out;
                        z-index: 1000;
                    `;
                    
                    this.parentElement.style.position = 'relative';
                    this.parentElement.appendChild(success);
                    
                    setTimeout(() => {
                        this.innerHTML = original;
                        this.style.background = 'var(--dark)';
                        this.style.transform = '';
                        success.remove();
                    }, 2500);
                    
                    // Update cart count
                    updateCartCount();
                });
            });
            
            // Wishlist toggle with heart animation
            const actionBtns = document.querySelectorAll('.action-icon[data-action="wishlist"]');
            actionBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const icon = this.querySelector('ion-icon');
                    
                    if (icon.name === 'heart-outline') {
                        icon.name = 'heart';
                        this.style.background = 'var(--secondary)';
                        this.style.color = 'var(--white)';
                        this.style.animation = 'float 0.6s ease-out';
                        
                        // Heart particles effect
                        createHeartParticles(this);
                    } else {
                        icon.name = 'heart-outline';
                        this.style.background = 'rgba(255, 255, 255, 0.95)';
                        this.style.color = 'var(--text)';
                        this.style.animation = '';
                    }
                });
            });
        }
        
        function createHeartParticles(element) {
            const rect = element.getBoundingClientRect();
            
            for (let i = 0; i < 6; i++) {
                const heart = document.createElement('div');
                heart.innerHTML = '💖';
                heart.style.cssText = `
                    position: fixed;
                    left: ${rect.left + rect.width/2}px;
                    top: ${rect.top + rect.height/2}px;
                    pointer-events: none;
                    z-index: 1000;
                    animation: heartFloat 1s ease-out forwards;
                    animation-delay: ${i * 0.1}s;
                `;
                document.body.appendChild(heart);
                
                setTimeout(() => heart.remove(), 1000);
            }
        }
        
        const heartFloatKeyframes = `
            @keyframes heartFloat {
                0% {
                    opacity: 1;
                    transform: translate(0, 0) scale(1);
                }
                100% {
                    opacity: 0;
                    transform: translate(${Math.random() * 200 - 100}px, -150px) scale(0.3);
                }
            }
        `;
        
        const style = document.createElement('style');
        style.textContent = heartFloatKeyframes;
        document.head.appendChild(style);
        
        function updateCartCount() {
            const cartCounts = document.querySelectorAll('.header-actions .count');
            cartCounts.forEach(count => {
                let current = parseInt(count.textContent) || 0;
                count.textContent = current + 1;
                count.style.animation = 'float 0.6s ease-out';
            });
        }
        
        // Enhanced search functionality
        function initAdvancedSearch() {
            const searchField = document.querySelector('.search-field');
            const searchBtn = document.querySelector('.search-btn');
            let searchTimeout;
            
            // Real-time search suggestions
            searchField.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                
                if (query.length > 2) {
                    searchTimeout = setTimeout(() => {
                        // Simulate search suggestions
                        console.log('Searching for:', query);
                    }, 300);
                }
            });
            
            function performSearch() {
                const query = searchField.value.trim();
                if (query) {
                    // Add search animation
                    searchBtn.style.animation = 'spin 1s ease-in-out';
                    setTimeout(() => {
                        searchBtn.style.animation = '';
                        alert(`🔍 Searching for: ${query}`);
                    }, 1000);
                }
            }
            
            searchBtn.addEventListener('click', performSearch);
            searchField.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
        }
        
        // Advanced header effects
        function initHeaderEffects() {
            let lastScrollY = window.scrollY;
            const header = document.querySelector('.header');
            
            window.addEventListener('scroll', () => {
                const currentScrollY = window.scrollY;
                
                if (currentScrollY > 100) {
                    if (currentScrollY > lastScrollY) {
                        // Scrolling down
                        header.style.transform = 'translateY(-100%)';
                    } else {
                        // Scrolling up
                        header.style.transform = 'translateY(0)';
                        header.style.background = 'rgba(255, 255, 255, 0.98)';
                        header.style.backdropFilter = 'blur(30px)';
                        header.style.boxShadow = 'var(--shadow-float)';
                    }
                } else {
                    header.style.transform = 'translateY(0)';
                    header.style.background = '';
                    header.style.backdropFilter = '';
                    header.style.boxShadow = '';
                }
                
                lastScrollY = currentScrollY;
            });
        }
        
        // Category filtering with smooth transitions
        function initCategoryFiltering() {
            const categoryLinks = document.querySelectorAll('.category-link-sidebar');
            const productCards = document.querySelectorAll('.product-card');
            
            categoryLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active from all
                    categoryLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    const category = this.querySelector('span').textContent.toLowerCase();
                    
                    // Animate out all cards
                    productCards.forEach((card, index) => {
                        card.style.animation = 'slideOutDown 0.3s ease-in forwards';
                        card.style.animationDelay = (index * 0.05) + 's';
                    });
                    
                    // After animation, filter and animate back in
                    setTimeout(() => {
                        productCards.forEach((card, index) => {
                            // Here you would filter by actual category data
                            card.style.animation = 'slideInUp 0.4s ease-out forwards';
                            card.style.animationDelay = (index * 0.05) + 's';
                        });
                    }, 400);
                });
            });
        }
        
        // Dynamic loading animation
        function showLoadingState() {
            const productGrid = document.querySelector('.product-grid');
            productGrid.style.opacity = '0.5';
            productGrid.style.pointerEvents = 'none';
            
            // Add loading overlay
            const loader = document.createElement('div');
            loader.innerHTML = `
                <div style="
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background: var(--white);
                    padding: 24px;
                    border-radius: var(--radius-xl);
                    box-shadow: var(--shadow-float);
                    text-align: center;
                ">
                    <div style="
                        width: 40px;
                        height: 40px;
                        border: 4px solid var(--gray-200);
                        border-top: 4px solid var(--primary-solid);
                        border-radius: 50%;
                        animation: spin 1s linear infinite;
                        margin: 0 auto 12px;
                    "></div>
                    <p style="color: var(--text); font-weight: 600;">Loading amazing products...</p>
                </div>
            `;
            loader.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.8);
                backdrop-filter: blur(10px);
                z-index: 100;
            `;
            
            productGrid.parentElement.style.position = 'relative';
            productGrid.parentElement.appendChild(loader);
            
            setTimeout(() => {
                loader.remove();
                productGrid.style.opacity = '1';
                productGrid.style.pointerEvents = 'auto';
            }, 1500);
        }
        
        // Add spin animation
        const spinKeyframes = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
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
        `;
        
        const additionalStyles = document.createElement('style');
        additionalStyles.textContent = spinKeyframes;
        document.head.appendChild(additionalStyles);
        
        // Initialize all effects
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            initMagneticEffect();
            initScrollAnimations();
            initAdvancedSearch();
            initHeaderEffects();
            initScrollToTop();
            initProductInteractions();
            initCategoryFiltering();
            
            // Show initial toast
            setTimeout(showToast, 2000);
            
            // Show periodic toasts
            setInterval(showToast, 15000);
            
            // Add premium loading states to category cards
            setTimeout(() => {
                document.querySelectorAll('.category-card').forEach((card, index) => {
                    card.style.animationDelay = (index * 0.15) + 's';
                });
            }, 100);
        });
        
        // Advanced cursor following effect
        document.addEventListener('mousemove', (e) => {
            const cursor = document.querySelector('.custom-cursor');
            if (!cursor) {
                const newCursor = document.createElement('div');
                newCursor.className = 'custom-cursor';
                newCursor.style.cssText = `
                    position: fixed;
                    width: 20px;
                    height: 20px;
                    background: var(--primary);
                    border-radius: 50%;
                    pointer-events: none;
                    z-index: 9999;
                    mix-blend-mode: difference;
                    transition: transform 0.1s ease;
                `;
                document.body.appendChild(newCursor);
            }
            
            const customCursor = document.querySelector('.custom-cursor');
            customCursor.style.left = e.clientX - 10 + 'px';
            customCursor.style.top = e.clientY - 10 + 'px';
        });
        
        // Interactive hover effects for links
        document.querySelectorAll('a, button').forEach(el => {
            el.addEventListener('mouseenter', () => {
                const cursor = document.querySelector('.custom-cursor');
                if (cursor) {
                    cursor.style.transform = 'scale(2)';
                    cursor.style.background = 'var(--accent-solid)';
                }
            });
            
            el.addEventListener('mouseleave', () => {
                const cursor = document.querySelector('.custom-cursor');
                if (cursor) {
                    cursor.style.transform = 'scale(1)';
                    cursor.style.background = 'var(--primary-solid)';
                }
            });
        });
        
        // Smooth reveal animation for product grid
        function revealProducts() {
            const products = document.querySelectorAll('.product-card');
            products.forEach((product, index) => {
                setTimeout(() => {
                    product.style.opacity = '1';
                    product.style.transform = 'translateY(0)';
                }, index * 100);
            });
        }
        
        // Set initial state for products
        document.querySelectorAll('.product-card').forEach(product => {
            product.style.opacity = '0';
            product.style.transform = 'translateY(30px)';
        });
        
        // Reveal products after a short delay
        setTimeout(revealProducts, 500);
        
        // Add rating text styles
        const ratingStyles = `
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
        `;
        
        const ratingStyle = document.createElement('style');
        ratingStyle.textContent = ratingStyles;
        document.head.appendChild(ratingStyle);
    </script>
</body>
</html>