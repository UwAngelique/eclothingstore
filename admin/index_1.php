<?php
// Database configuration
$host = 'localhost';
$dbname = 'agmsdb';
$username = 'root';
$password = 'yego';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get date range from URL parameters
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // Default to start of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Default to today

// Auto-detect available tables
function getAvailableTables($pdo) {
    $stmt = $pdo->query("SHOW TABLES");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$available_tables = getAvailableTables($pdo);

// Function to get dynamic stats based on available tables
function getDynamicStats($pdo, $available_tables, $date_from, $date_to) {
    $stats = [];
    
    // Products/Inventory stats
    $product_tables = ['products', 'product', 'items', 'inventory'];
    $product_table = null;
    foreach ($product_tables as $table) {
        if (in_array($table, $available_tables)) {
            $product_table = $table;
            break;
        }
    }
    
    if ($product_table) {
        // Total products
        $stmt = $pdo->query("SELECT COUNT(*) FROM $product_table");
        $stats['total_products'] = $stmt->fetchColumn();
        
        // Low stock items (assuming quantity < 10 is low stock)
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $product_table WHERE quantity < 10");
            $stats['low_stock'] = $stmt->fetchColumn();
        } catch (Exception $e) {
            $stats['low_stock'] = 0;
        }
        
        // Products added in date range (if created_at exists)
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $product_table WHERE DATE(created_at) BETWEEN ? AND ?");
            $stmt->execute([$date_from, $date_to]);
            $stats['products_added'] = $stmt->fetchColumn();
        } catch (Exception $e) {
            $stats['products_added'] = 0;
        }
    }
    
    // Orders stats
    $order_tables = ['orders', 'order', 'sales'];
    $order_table = null;
    foreach ($order_tables as $table) {
        if (in_array($table, $available_tables)) {
            $order_table = $table;
            break;
        }
    }
    
    if ($order_table) {
        // Get table structure to understand columns
        $stmt = $pdo->prepare("DESCRIBE $order_table");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $date_column = 'created_at';
        if (in_array('order_date', $columns)) $date_column = 'order_date';
        if (in_array('date', $columns)) $date_column = 'date';
        
        $amount_column = 'total_amount';
        if (in_array('amount', $columns)) $amount_column = 'amount';
        if (in_array('total', $columns)) $amount_column = 'total';
        
        $status_column = 'status';
        
        // Orders in date range
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $order_table WHERE DATE($date_column) BETWEEN ? AND ?");
            $stmt->execute([$date_from, $date_to]);
            $stats['total_orders'] = $stmt->fetchColumn();
        } catch (Exception $e) {
            $stats['total_orders'] = 0;
        }
        
        // Revenue in date range
        try {
            $stmt = $pdo->prepare("SELECT COALESCE(SUM($amount_column), 0) FROM $order_table WHERE DATE($date_column) BETWEEN ? AND ?");
            $stmt->execute([$date_from, $date_to]);
            $stats['total_revenue'] = $stmt->fetchColumn();
        } catch (Exception $e) {
            $stats['total_revenue'] = 0;
        }
        
        // Pending orders
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $order_table WHERE $status_column = 'PENDING'");
            $stmt->execute();
            $stats['pending_orders'] = $stmt->fetchColumn();
        } catch (Exception $e) {
            $stats['pending_orders'] = 0;
        }
        
        // Completed orders in date range
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $order_table WHERE $status_column IN ('COMPLETED', 'DELIVERED', 'INVOICE') AND DATE($date_column) BETWEEN ? AND ?");
            $stmt->execute([$date_from, $date_to]);
            $stats['completed_orders'] = $stmt->fetchColumn();
        } catch (Exception $e) {
            $stats['completed_orders'] = 0;
        }
        
        // Today's revenue
        try {
            $stmt = $pdo->prepare("SELECT COALESCE(SUM($amount_column), 0) FROM $order_table WHERE DATE($date_column) = CURDATE()");
            $stmt->execute();
            $stats['today_revenue'] = $stmt->fetchColumn();
        } catch (Exception $e) {
            $stats['today_revenue'] = 0;
        }
    }
    
    // Customer stats
    $customer_tables = ['customers', 'customer', 'users', 'clients'];
    $customer_table = null;
    foreach ($customer_tables as $table) {
        if (in_array($table, $available_tables)) {
            $customer_table = $table;
            break;
        }
    }
    
    if ($customer_table) {
        // Total customers
        $stmt = $pdo->query("SELECT COUNT(*) FROM $customer_table");
        $stats['total_customers'] = $stmt->fetchColumn();
        
        // New customers in date range
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $customer_table WHERE DATE(created_at) BETWEEN ? AND ?");
            $stmt->execute([$date_from, $date_to]);
            $stats['new_customers'] = $stmt->fetchColumn();
        } catch (Exception $e) {
            $stats['new_customers'] = 0;
        }
    }
    
    return $stats;
}

$stats = getDynamicStats($pdo, $available_tables, $date_from, $date_to);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Shades Beauty Admin Dashboard</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --secondary-color: #6366f1;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --light-bg: #f8fafc;
            --sidebar-bg: #ffffff;
            --card-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --hover-bg: #f1f5f9;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #ffffff;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-primary);
            line-height: 1.6;
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Date Range Filter Section */
        .date-filter-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            border-radius: 0.75rem;
            box-shadow: var(--shadow);
        }

        .date-filter-form {
            display: flex;
            align-items: end;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .date-input-group {
            display: flex;
            flex-direction: column;
            min-width: 150px;
        }

        .date-input-group label {
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.25rem;
            opacity: 0.9;
        }

        .date-input-group input {
            padding: 0.5rem 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.375rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 0.875rem;
        }

        .date-input-group input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .filter-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.5rem 1.5rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }

        .filter-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .current-range {
            font-size: 0.875rem;
            opacity: 0.9;
            margin-top: 0.5rem;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 1000;
            overflow-y: auto;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sidebar-header .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--text-primary);
        }

        .sidebar-header .logo i {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-right: 0.75rem;
        }

        .sidebar-header .logo span {
            font-weight: 700;
            font-size: 1.1rem;
            white-space: nowrap;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }

        .sidebar-toggle:hover {
            background-color: var(--hover-bg);
            color: var(--text-primary);
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.25rem 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s ease;
            border-radius: 0;
            font-weight: 500;
            position: relative;
        }

        .nav-link:hover {
            background-color: var(--hover-bg);
            color: var(--primary-color);
        }

        .nav-link.active {
            background-color: rgba(79, 70, 229, 0.1);
            color: var(--primary-color);
            border-right: 3px solid var(--primary-color);
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
            font-size: 1rem;
            text-align: center;
        }

        .nav-link span {
            white-space: nowrap;
        }

        .sidebar.collapsed .nav-link span,
        .sidebar.collapsed .sidebar-header .logo span {
            display: none;
        }

        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 0.75rem;
        }

        .sidebar.collapsed .nav-link i {
            margin-right: 0;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            flex: 1;
            transition: all 0.3s ease;
            background-color: #ffffff;
        }

        .main-content.expanded {
            margin-left: 80px;
        }

        .topbar {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: var(--shadow);
        }

        .topbar-left h4 {
            margin: 0;
            font-weight: 600;
            color: var(--text-primary);
        }

        .topbar-left small {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .topbar-right select {
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            background: var(--card-bg);
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .user-dropdown .dropdown-toggle {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
        }

        .user-dropdown .dropdown-toggle:hover {
            background-color: var(--hover-bg);
        }

        /* Content Area */
        .content {
            padding: 2rem;
        }

        .page-content {
            display: none;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }

        .page-content.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        /* Stats Cards */
        .stats-row {
            margin-bottom: 2rem;
        }

        .stats-card {
            background: var(--card-bg);
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
            height: 100%;
        }

        .stats-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .stats-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }

        .stats-icon.primary {
            background-color: var(--primary-color);
        }

        .stats-icon.success {
            background-color: var(--success-color);
        }

        .stats-icon.warning {
            background-color: var(--warning-color);
        }

        .stats-icon.danger {
            background-color: var(--danger-color);
        }

        .stats-icon.info {
            background-color: var(--info-color);
        }

        .stats-icon.purple {
            background-color: #8b5cf6;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .stats-title {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stats-link {
            font-size: 0.75rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .stats-link:hover {
            background-color: rgba(79, 70, 229, 0.1);
            color: var(--primary-hover);
        }

        /* Content sections */
        .content-section {
            background: var(--card-bg);
            border-radius: 0.75rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        /* Loading Spinner */
        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 3px solid rgba(79, 70, 229, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .main-content.expanded {
                margin-left: 0;
            }

            .topbar {
                padding: 1rem;
            }

            .content {
                padding: 1rem;
            }

            .mobile-toggle {
                display: block;
                background: none;
                border: none;
                color: var(--text-secondary);
                font-size: 1.2rem;
                cursor: pointer;
                padding: 0.5rem;
                border-radius: 0.375rem;
            }

            .date-filter-form {
                flex-direction: column;
                align-items: stretch;
            }

            .date-input-group {
                min-width: 100%;
            }
        }

        .mobile-toggle {
            display: none;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="#" class="logo" onclick="showPage('dashboard')">
                    <i class="fas fa-tshirt"></i>
                     <!-- <img src="images/logo.png" alt="Logo" style="width:100%;"> -->
                    <span>Shades Beauty</span>
                </a>
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <ul class="sidebar-nav">
                <li class="nav-item">
                    <a href="#" class="nav-link active" onclick="showPage('dashboard')" data-page="dashboard">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="showPage('inventory')" data-page="inventory">
                        <i class="fas fa-boxes"></i>
                        <span>Inventory</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="showPage('orders')" data-page="orders">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Orders</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="showPage('customers')" data-page="customers">
                        <i class="fas fa-users"></i>
                        <span>Customers</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="showPage('enquiries')" data-page="enquiries">
                        <i class="fas fa-envelope"></i>
                        <span>Enquiries</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="showPage('reviews')" data-page="reviews">
                        <i class="fas fa-star"></i>
                        <span>Reviews</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="showPage('coupons')" data-page="coupons">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Coupons</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="showPage('reports')" data-page="reports">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <!-- Top Bar -->
            <div class="topbar">
                <div class="topbar-left">
                    <button class="mobile-toggle" onclick="toggleMobileSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h4 id="pageTitle">Shades Beauty Dashboard</h4>
                        <small id="currentDate"></small>
                    </div>
                </div>
                <div class="topbar-right">
                    <select class="form-select form-select-sm">
                        <option>Select Currency</option>
                        <option>USD ($)</option>
                        <option>EUR (€)</option>
                        <option>GBP (£)</option>
                    </select>
                    <div class="dropdown user-dropdown">
                        <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-2"></i>Admin
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content">
                <!-- Dashboard Page -->
                <div class="page-content active" id="dashboard-content">
                    
                    <!-- Date Range Filter -->
                    <div class="date-filter-section">
                        <form method="GET" class="date-filter-form">
                            <div class="date-input-group">
                                <label>From Date</label>
                                <input type="date" name="date_from" value="<?php echo $date_from; ?>" required>
                            </div>
                            <div class="date-input-group">
                                <label>To Date</label>
                                <input type="date" name="date_to" value="<?php echo $date_to; ?>" required>
                            </div>
                            <div>
                                <button type="submit" class="filter-btn">
                                    <i class="fas fa-search me-2"></i>
                                </button>
                            </div>
                        </form>
                        <div class="current-range">
                            Showing data from <strong><?php echo date('M j, Y', strtotime($date_from)); ?></strong> 
                            to <strong><?php echo date('M j, Y', strtotime($date_to)); ?></strong>
                        </div>
                    </div>

                    <!-- Dynamic Stats Cards -->
                    <div class="row stats-row">
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="stats-card">
                                <div class="stats-card-header">
                                    <div class="stats-icon success">
                                        <i class="fas fa-tshirt"></i>
                                    </div>
                                    <a href="#" class="stats-link" onclick="showPage('inventory')">Manage</a>
                                </div>
                                <div class="stats-number"><?php echo number_format($stats['total_products'] ?? 0); ?></div>
                                <div class="stats-title">Total Products</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="stats-card">
                                <div class="stats-card-header">
                                    <div class="stats-icon purple">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <a href="#" class="stats-link" onclick="showPage('customers')">View All</a>
                                </div>
                                <div class="stats-number"><?php echo number_format($stats['total_customers'] ?? 0); ?></div>
                                <div class="stats-title">Total Customers</div>
                            </div>
                        </div>
                        <!-- <div class="col-lg-3 col-md-6 mb-4">
                            <div class="stats-card">
                                <div class="stats-card-header">
                                    <div class="stats-icon warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <a href="#" class="stats-link" onclick="showPage('inventory')">Restock</a>
                                </div>
                                <div class="stats-number"><?php echo number_format($stats['low_stock'] ?? 0); ?></div>
                                <div class="stats-title">Low Stock Items</div>
                            </div>
                        </div> -->
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="stats-card">
                                <div class="stats-card-header">
                                    <div class="stats-icon danger">
                                        <i class="fas fa-shopping-bag"></i>
                                    </div>
                                    <a href="#" class="stats-link" onclick="showPage('orders')">Process</a>
                                </div>
                                <div class="stats-number"><?php echo number_format($stats['total_orders'] ?? 0); ?></div>
                                <div class="stats-title">Orders</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="stats-card">
                                <div class="stats-card-header">
                                    <div class="stats-icon info">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                </div>
                                <div class="stats-number"><?php echo number_format($stats['pending_orders'] ?? 0); ?></div>
                                <div class="stats-title">Pending Orders</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="stats-card">
                                <div class="stats-card-header">
                                    <div class="stats-icon success">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                                <div class="stats-number"><?php echo number_format($stats['completed_orders'] ?? 0); ?></div>
                                <div class="stats-title">Completed Orders</div>
                            </div>
                        </div>
                        <!-- <div class="col-lg-3 col-md-6 mb-4">
                            <div class="stats-card">
                                <div class="stats-card-header">
                                    <div class="stats-icon success">
                                        <i class="fas fa-dollar-sign"></i>
                                    </div>
                                </div>
                                <div class="stats-number">$<?php echo number_format($stats['today_revenue'] ?? 0, 2); ?></div>
                                <div class="stats-title">Today's Revenue</div>
                            </div>
                        </div> -->
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="stats-card">
                                <div class="stats-card-header">
                                    <div class="stats-icon primary">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                </div>
                                <div class="stats-number">Rwf<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
                                <div class="stats-title">Revenue</div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Insights Row -->
                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="content-section">
                                <h5 class="mb-3">
                                    <i class="fas fa-chart-bar me-2 text-primary"></i>
                                    Quick Insights
                                </h5>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="text-center p-3">
                                            <div class="stats-number text-info" style="font-size: 1.5rem;">
                                                <?php echo number_format($stats['new_customers'] ?? 0); ?>
                                            </div>
                                            <div class="stats-title">New Customers</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center p-3">
                                            <div class="stats-number text-success" style="font-size: 1.5rem;">
                                                <?php echo number_format($stats['products_added'] ?? 0); ?>
                                            </div>
                                            <div class="stats-title">Products Added</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4">
                            <div class="content-section">
                                <h5 class="mb-3">
                                    <i class="fas fa-database me-2 text-primary"></i>
                                    Available Tables
                                </h5>
                                <div class="table-badges">
                                    <?php foreach ($available_tables as $table): ?>
                                    <span class="badge bg-light text-dark me-1 mb-1"><?php echo $table; ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    Dashboard automatically detects and uses available database tables
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- All other pages with dynamic content containers -->
                <div class="page-content" id="inventory-content">
                    <div class="content-section" style="padding: 0;">
                        <div id="inventory-dynamic-content">
                            <div class="text-center p-5">
                                <div class="loading-spinner mb-3"></div>
                                <p>Loading inventory data...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="page-content" id="orders-content">
                    <div class="content-section" style="padding: 0;">
                        <div id="orders-dynamic-content">
                            <div class="text-center p-5">
                                <div class="loading-spinner mb-3"></div>
                                <p>Loading orders data...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="page-content" id="customers-content">
                    <div class="content-section" style="padding: 0;">
                        <div id="customers-dynamic-content">
                            <div class="text-center p-5">
                                <div class="loading-spinner mb-3"></div>
                                <p>Loading customers data...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="page-content" id="enquiries-content">
                    <div class="content-section" style="padding: 0;">
                        <div id="enquiries-dynamic-content">
                            <div class="text-center p-5">
                                <div class="loading-spinner mb-3"></div>
                                <p>Loading enquiries data...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="page-content" id="reviews-content">
                    <div class="content-section" style="padding: 0;">
                        <div id="reviews-dynamic-content">
                            <div class="text-center p-5">
                                <div class="loading-spinner mb-3"></div>
                                <p>Loading reviews data...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="page-content" id="coupons-content">
                    <div class="content-section" style="padding: 0;">
                        <div id="coupons-dynamic-content">
                            <div class="text-center p-5">
                                <div class="loading-spinner mb-3"></div>
                                <p>Loading coupons data...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="page-content" id="reports-content">
                    <div class="content-section" style="padding: 0;">
                        <div id="reports-dynamic-content">
                            <div class="text-center p-5">
                                <div class="loading-spinner mb-3"></div>
                                <p>Loading reports data...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>

    <script>
        // State management for pages
        let pageStates = {};
        let contentCache = {};

        // Initialize date and update every minute
        function updateDateTime() {
            document.getElementById('currentDate').textContent = new Date().toLocaleString('en-US', {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        updateDateTime();
        setInterval(updateDateTime, 60000);

        // Get current page from URL
        function getCurrentPageFromUrl() {
            const urlParams = new URLSearchParams(window.location.search);
            const page = urlParams.get('page');
            return page || 'dashboard';
        }

        // Update URL without page reload
        function updateURL(pageName) {
            // Preserve date filter parameters when changing pages
            const urlParams = new URLSearchParams(window.location.search);
            if (pageName === 'dashboard') {
                // Keep date filters for dashboard
                const params = [];
                if (urlParams.get('date_from')) params.push(`date_from=${urlParams.get('date_from')}`);
                if (urlParams.get('date_to')) params.push(`date_to=${urlParams.get('date_to')}`);
                const queryString = params.length > 0 ? '?' + params.join('&') : '';
                const newUrl = window.location.pathname + queryString;
                window.history.pushState({ page: pageName }, '', newUrl);
            } else {
                urlParams.set('page', pageName);
                window.history.pushState({ page: pageName }, '', '?' + urlParams.toString());
            }
        }

        function loadPageContent(pageName, phpFileName = null) {
            const contentDiv = document.getElementById(`${pageName}-dynamic-content`);
            if (!contentDiv) return;

            const fileName = phpFileName || `${pageName}.php`;

            // If we have cached content, restore it
            if (contentCache[pageName]) {
                contentDiv.innerHTML = contentCache[pageName];
                restorePageState(pageName);
                initializePageListeners(pageName);
                return;
            }

            // Show loading
            const loadingHTML = `
        <div class="text-center p-5">
            <div class="loading-spinner mb-3"></div>
            <h5 class="text-muted">Loading ${pageName.charAt(0).toUpperCase() + pageName.slice(1)} Management</h5>
            <p class="text-muted">Please wait while we fetch your ${pageName} data...</p>
        </div>
    `;

            contentDiv.innerHTML = loadingHTML;

            // Fetch content
            fetch(fileName)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.text();
                })
                .then(html => {
                    // Parse the HTML
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    // Extract and inject CSS styles
                    const styles = doc.querySelectorAll('style, link[rel="stylesheet"]');
                    styles.forEach(styleElement => {
                        const existingStyle = document.head.querySelector(`[data-loaded-from="${fileName}"]`);
                        if (!existingStyle || styleElement.tagName === 'LINK') {
                            const newStyleElement = styleElement.cloneNode(true);
                            newStyleElement.setAttribute('data-loaded-from', fileName);
                            document.head.appendChild(newStyleElement);
                        }
                    });

                    // Extract body content
                    const bodyContent = doc.body.innerHTML;

                    // Cache the content
                    contentCache[pageName] = bodyContent;
                    contentDiv.innerHTML = bodyContent;

                    // Initialize event listeners
                    initializePageListeners(pageName);

                    // Restore previous state
                    restorePageState(pageName);

                    // Re-initialize scripts
                    const scripts = doc.querySelectorAll('script');
                    scripts.forEach((script, index) => {
                        // Skip common libraries to avoid conflicts
                        if (script.src && (
                                script.src.includes('bootstrap') ||
                                script.src.includes('jquery') ||
                                script.src.includes('cdnjs.cloudflare.com')
                            )) {
                            return;
                        }

                        // Create unique identifier
                        const scriptId = `script-${fileName}-${index}`;
                        const existingScript = document.head.querySelector(`#${scriptId}`);

                        if (!existingScript) {
                            const newScript = document.createElement('script');
                            newScript.id = scriptId;
                            newScript.setAttribute('data-loaded-from', fileName);

                            if (script.src) {
                                newScript.src = script.src;
                            } else {
                                newScript.textContent = script.textContent;
                            }

                            // Append to body instead of head for better execution
                            document.body.appendChild(newScript);
                        }
                    });
                })
                .catch(error => {
                    console.error(`Error loading ${pageName}:`, error);
                    const errorHTML = `
                <div class="text-center p-5">
                    <div class="alert alert-danger d-inline-block" role="alert">
                        <div class="text-center mb-3">
                            <i class="fas fa-exclamation-triangle fs-1 text-danger"></i>
                        </div>
                        <h5 class="text-center">Unable to Load ${pageName.charAt(0).toUpperCase() + pageName.slice(1)}</h5>
                        <hr>
                        <p><strong>Error:</strong> ${error.message}</p>
                        <p><strong>Possible Solutions:</strong></p>
                        <ul class="text-start">
                            <li>Ensure '${fileName}' file exists in the same directory</li>
                            <li>Check file permissions and server configuration</li>
                            <li>Verify database connection</li>
                        </ul>
                        <button class="btn btn-outline-danger btn-sm mt-2" onclick="loadPageContent('${pageName}', '${fileName}')">
                            <i class="fas fa-redo me-2"></i>Try Again
                        </button>
                    </div>
                </div>
            `;
                    contentDiv.innerHTML = errorHTML;
                });
        }

        // Initialize page listeners
        function initializePageListeners(pageName) {
            const contentDiv = document.getElementById(`${pageName}-dynamic-content`);
            if (!contentDiv) return;

            contentDiv.addEventListener('input', () => saveCurrentPageState());
            contentDiv.addEventListener('change', () => saveCurrentPageState());
            contentDiv.addEventListener('click', () => saveCurrentPageState());
        }

        // Enhanced page navigation with dynamic content loading
        function showPage(pageName, updateUrl = true) {
            saveCurrentPageState();

            if (updateUrl) {
                updateURL(pageName);
            }

            document.querySelectorAll('.page-content').forEach(content => {
                content.classList.remove('active');
            });

            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });

            const targetPage = document.getElementById(pageName + '-content');
            if (targetPage) {
                targetPage.classList.add('active');
            }

            const navLink = document.querySelector(`[data-page="${pageName}"]`);
            if (navLink) {
                navLink.classList.add('active');
            }

            const pageTitles = {
                'dashboard': 'Shades Beauty Dashboard',
                'inventory': 'Inventory Management',
                'orders': 'Orders Management',
                'customers': 'Customer Management',
                'enquiries': 'Customer Enquiries',
                'reviews': 'Customer Reviews',
                'coupons': 'Coupons Management',
                'reports': 'Sales Reports'
            };

            document.getElementById('pageTitle').textContent = pageTitles[pageName] || 'Shades Beauty Admin';

            const pagesWithDynamicContent = ['inventory', 'orders', 'customers', 'enquiries', 'reviews', 'coupons', 'reports'];

            if (pagesWithDynamicContent.includes(pageName)) {
                if (pageName === 'inventory') {
                    loadPageContent('inventory', 'product_content.php');
                } else {
                    loadPageContent(pageName);
                }
            }

            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Save current page state
        function saveCurrentPageState() {
            const currentPage = document.querySelector('.page-content.active');
            if (!currentPage) return;

            const pageId = currentPage.id.replace('-content', '');

            const state = {
                scrollPosition: window.pageYOffset,
                inputs: {},
                selects: {},
                checkboxes: {},
                activeTab: null
            };

            currentPage.querySelectorAll('input').forEach(input => {
                if (input.id || input.name) {
                    const key = input.id || input.name;
                    if (input.type === 'checkbox') {
                        state.checkboxes[key] = input.checked;
                    } else {
                        state.inputs[key] = input.value;
                    }
                }
            });

            currentPage.querySelectorAll('select').forEach(select => {
                if (select.id || select.name) {
                    const key = select.id || select.name;
                    state.selects[key] = select.value;
                }
            });

            const activeTab = currentPage.querySelector('.nav-link.active, .tab-pane.active');
            if (activeTab) {
                state.activeTab = activeTab.id || activeTab.getAttribute('data-bs-target');
            }

            pageStates[pageId] = state;
        }

        // Restore page state
        function restorePageState(pageName) {
            const state = pageStates[pageName];
            if (!state) return;

            const page = document.getElementById(pageName + '-content');
            if (!page) return;

            setTimeout(() => {
                Object.entries(state.inputs).forEach(([key, value]) => {
                    const element = page.querySelector(`#${key}, [name="${key}"]`);
                    if (element) {
                        element.value = value;
                        element.dispatchEvent(new Event('input', {
                            bubbles: true
                        }));
                    }
                });

                Object.entries(state.selects).forEach(([key, value]) => {
                    const element = page.querySelector(`#${key}, [name="${key}"]`);
                    if (element) {
                        element.value = value;
                        element.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                    }
                });

                Object.entries(state.checkboxes).forEach(([key, checked]) => {
                    const element = page.querySelector(`#${key}, [name="${key}"]`);
                    if (element) {
                        element.checked = checked;
                        element.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                    }
                });

                if (state.activeTab) {
                    const tabElement = page.querySelector(`[data-bs-target="${state.activeTab}"], #${state.activeTab}`);
                    if (tabElement && tabElement.click) {
                        tabElement.click();
                    }
                }

                if (state.scrollPosition) {
                    window.scrollTo({
                        top: state.scrollPosition,
                        behavior: 'smooth'
                    });
                }
            }, 100);
        }

        // Handle browser back/forward buttons
        window.addEventListener('popstate', function(event) {
            const page = event.state ? event.state.page : getCurrentPageFromUrl();
            showPage(page, false);
        });

        // Sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');

            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');

            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }

        // Mobile sidebar toggle
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }

        // Close mobile sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const mobileToggle = document.querySelector('.mobile-toggle');

            if (window.innerWidth <= 768 &&
                !sidebar.contains(event.target) &&
                !mobileToggle.contains(event.target)) {
                sidebar.classList.remove('show');
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('show');
            }
        });

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (sidebarCollapsed) {
                document.getElementById('sidebar').classList.add('collapsed');
                document.getElementById('mainContent').classList.add('expanded');
            }

            const currentPage = getCurrentPageFromUrl();
            showPage(currentPage, false);
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

            if ((e.ctrlKey || e.metaKey) && e.shiftKey) {
                switch (e.key.toLowerCase()) {
                    case 'd':
                        e.preventDefault();
                        showPage('dashboard');
                        break;
                    case 'i':
                        e.preventDefault();
                        showPage('inventory');
                        break;
                    case 'o':
                        e.preventDefault();
                        showPage('orders');
                        break;
                    case 'c':
                        e.preventDefault();
                        showPage('customers');
                        break;
                }
            }

            if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                e.preventDefault();
                toggleSidebar();
            }
        });

        console.log('Dynamic Dashboard initialized successfully');
        console.log('Available tables: <?php echo json_encode($available_tables); ?>');
        console.log('Date range: <?php echo $date_from; ?> to <?php echo $date_to; ?>');
    </script>
</body>

</html>