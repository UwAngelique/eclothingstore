<?php
// Database configuration
// $host = 'localhost';
// $dbname = 'agmsdb';
// $username = 'root';
// $password = 'yego';
include __DIR__ . '/db.php';
// try {
//     $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
//     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// } catch(PDOException $e) {
//     die("Connection failed: " . $e->getMessage());
// }

// Get current page from URL
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = ['dashboard', 'inventory', 'orders', 'customers', 'enquiries', 'reviews', 'discount', 'reports'];

// Validate page parameter
if (!in_array($current_page, $allowed_pages)) {
    $current_page = 'dashboard';
}

// Dynamic date handling
$date_from = isset($_GET['date_from']) && !empty($_GET['date_from']) 
    ? $_GET['date_from'] 
    : date('Y-m-01');

$date_to = isset($_GET['date_to']) && !empty($_GET['date_to']) 
    ? $_GET['date_to'] 
    : date('Y-m-d');

function getAvailableTables($pdo) {
    $stmt = $pdo->query("SHOW TABLES");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$available_tables = getAvailableTables($pdo);

function getDynamicStats($pdo, $available_tables, $date_from, $date_to) {
    $stats = [];
    
    foreach (['products', 'product', 'items', 'inventory'] as $table) {
        if (in_array($table, $available_tables)) {
            $stats['total_products'] = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE DATE(created_at) BETWEEN ? AND ?");
                $stmt->execute([$date_from, $date_to]);
                $stats['products_added'] = $stmt->fetchColumn();
            } catch (Exception $e) {
                $stats['products_added'] = 0;
            }
            break;
        }
    }
    
    foreach (['orders', 'order', 'sales'] as $table) {
        if (in_array($table, $available_tables)) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE DATE(created_at) BETWEEN ? AND ?");
                $stmt->execute([$date_from, $date_to]);
                $stats['total_orders'] = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE status='awaiting processing' AND DATE(created_at) BETWEEN ? AND ?");
                $stmt->execute([$date_from, $date_to]);
                $stats['pending_orders'] = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE status IN ('COMPLETED','DELIVERED','INVOICE','Delivered') AND DATE(created_at) BETWEEN ? AND ?");
                $stmt->execute([$date_from, $date_to]);
                $stats['completed_orders'] = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM $table WHERE status='DELIVERED' AND  DATE(created_at) BETWEEN ? AND ?");
                $stmt->execute([$date_from, $date_to]);
                $stats['total_revenue'] = $stmt->fetchColumn();
            } catch (Exception $e) {
                $stats['total_orders'] = 0;
                $stats['pending_orders'] = 0;
                $stats['completed_orders'] = 0;
                $stats['total_revenue'] = 0;
            }
            break;
        }
    }
    
    // foreach (['customers', 'customer', 'users', 'clients'] as $table) {
    //     if (in_array($table, $available_tables)) {
    //         $stats['total_customers'] = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    //         try {
    //             $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE DATE(created_at) BETWEEN ? AND ?");
    //             $stmt->execute([$date_from, $date_to]);
    //             $stats['new_customers'] = $stmt->fetchColumn();
    //         } catch (Exception $e) {
    //             $stats['new_customers'] = 0;
    //         }
    //         break;
    //     }
    // }
    if (in_array('orders', $available_tables)) {
    try {
        // Total unique customers who have placed at least one order
        $stmt = $pdo->query("SELECT COUNT(DISTINCT customer_email) FROM orders");
        $stats['total_customers'] = $stmt->fetchColumn();

        // Unique customers within selected date range
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT customer_email)
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$date_from, $date_to]);
        $stats['new_customers'] = $stmt->fetchColumn();
    } catch (Exception $e) {
        $stats['total_customers'] = 0;
        $stats['new_customers'] = 0;
    }
}
    return $stats;
}

$stats = getDynamicStats($pdo, $available_tables, $date_from, $date_to);
$filter_applied = isset($_GET['date_from']) || isset($_GET['date_to']);

// Page titles mapping
$page_titles = [
    'dashboard' => 'Shades Beauty Dashboard',
    'inventory' => 'Inventory Management',
    'orders' => 'Orders Management',
    'customers' => 'Customer Management',
    'enquiries' => 'Customer Enquiries',
    'reviews' => 'Customer Reviews',
    'discount' => 'discount Management',
    'reports' => 'Sales Reports'
];

$page_title = $page_titles[$current_page] ?? 'Shades Beauty Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            background: #f8fafc !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important; 
            color: #1e293b !important;
            min-height: 100vh;
        }

        .sidebar { 
            width: 260px !important; 
            background: #ffffff !important;
            border-right: 1px solid #e2e8f0 !important; 
            position: fixed !important; 
            height: 100vh !important; 
            z-index: 1000 !important; 
            overflow-y: auto !important;
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.02) !important;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.3);
            border-radius: 10px;
        }

        .sidebar-header { 
            padding: 2rem 1.5rem !important; 
            border-bottom: 1px solid #e2e8f0 !important;
            background: #ffffff;
        }

        .sidebar .logo { 
            display: flex !important; 
            align-items: center !important; 
            text-decoration: none !important; 
            color: #0f172a !important; 
            transition: opacity 0.2s ease !important;
        }

        .sidebar .logo:hover {
            opacity: 0.8;
        }

        .sidebar .logo i { 
            font-size: 1.75rem !important; 
            color: #6366f1 !important;
            margin-right: 0.75rem !important; 
        }

        .sidebar .logo span { 
            font-weight: 700 !important; 
            font-size: 1.125rem !important;
            color: #0f172a !important;
            letter-spacing: -0.3px;
        }

        .sidebar-nav { 
            padding: 1.5rem 0 !important; 
        }

        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-link { 
            display: flex !important; 
            align-items: center !important; 
            padding: 0.75rem 1.25rem !important; 
            color: #64748b !important; 
            text-decoration: none !important; 
            transition: all 0.2s ease !important;
            margin: 0.15rem 0.75rem !important;
            border-radius: 0.5rem !important;
            font-weight: 500 !important;
            font-size: 0.875rem;
            position: relative;
        }

        .nav-link:hover { 
            background: #f1f5f9 !important;
            color: #6366f1 !important; 
        }

        .nav-link.active { 
            background: #6366f1 !important;
            color: #fff !important; 
        }

        .nav-link i { 
            width: 20px !important; 
            margin-right: 0.75rem !important;
            font-size: 0.95rem !important;
        }

        .main-content { 
            margin-left: 260px !important; 
            flex: 1 !important;
            background: transparent !important;
        }

        .topbar { 
            background: #ffffff !important;
            border-bottom: 1px solid #e2e8f0 !important; 
            padding: 1.5rem 2rem !important; 
            display: flex !important; 
            justify-content: space-between !important; 
            align-items: center !important; 
            position: sticky !important; 
            top: 0 !important; 
            z-index: 999 !important;
        }

        .topbar h4 { 
            margin: 0 !important; 
            font-weight: 700 !important;
            color: #0f172a !important;
            font-size: 1.25rem;
            letter-spacing: -0.3px;
        }

        .topbar small { 
            color: #64748b !important; 
            font-size: 0.8rem !important; 
            font-weight: 400;
            display: block;
            margin-top: 0.25rem;
        }

        .topbar .btn {
            background: #6366f1 !important;
            border: none !important;
            color: white !important;
            padding: 0.625rem 1.25rem !important;
            border-radius: 0.5rem !important;
            font-weight: 600 !important;
            font-size: 0.875rem;
            transition: all 0.2s ease !important;
        }

        .topbar .btn:hover {
            background: #4f46e5 !important;
            transform: translateY(-1px);
        }

        .content { 
            padding: 2.5rem !important;
            background: transparent !important;
            animation: fadeInUp 0.5s ease !important;
        }

        @keyframes fadeInUp {
            from { 
                opacity: 0; 
                transform: translateY(20px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }

        .date-filter-section { 
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important; 
            padding: 2rem !important; 
            margin-bottom: 2rem !important; 
            border-radius: 0.75rem !important;
        }

        .date-filter-form { 
            display: flex !important; 
            align-items: end !important; 
            gap: 1.25rem !important; 
            flex-wrap: wrap !important; 
        }

        .date-input-group { 
            display: flex !important; 
            flex-direction: column !important; 
            min-width: 200px !important; 
        }

        .date-input-group label { 
            font-size: 0.8rem !important; 
            font-weight: 600 !important; 
            margin-bottom: 0.5rem !important;
            color: #334155 !important;
        }

        .date-input-group input { 
            padding: 0.75rem 1rem !important; 
            border: 1px solid #e2e8f0 !important; 
            border-radius: 0.5rem !important; 
            background: #ffffff !important; 
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            transition: all 0.2s ease !important;
            color: #1e293b;
        }

        .date-input-group input:focus { 
            outline: none !important; 
            border-color: #6366f1 !important; 
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1) !important;
        }

        .filter-btn { 
            background: #6366f1 !important;
            color: white !important; 
            border: none !important; 
            padding: 0.75rem 2rem !important; 
            border-radius: 0.5rem !important; 
            cursor: pointer !important; 
            font-weight: 600 !important;
            transition: all 0.2s ease !important;
            font-size: 0.875rem;
        }

        .filter-btn:hover { 
            background: #4f46e5 !important;
            transform: translateY(-1px);
        }

        .reset-btn {
            background: #ef4444 !important;
            color: white !important;
            border: none !important;
            padding: 0.75rem 1.5rem !important;
            border-radius: 0.5rem !important;
            cursor: pointer !important;
            font-weight: 600 !important;
            transition: all 0.2s ease !important;
            font-size: 0.875rem;
            text-decoration: none !important;
            display: inline-block;
        }

        .reset-btn:hover {
            background: #dc2626 !important;
            transform: translateY(-1px);
            color: white !important;
        }

        .current-range { 
            font-size: 0.875rem !important; 
            color: #64748b !important; 
            margin-top: 1.5rem !important; 
            padding-top: 1.5rem !important; 
            border-top: 1px solid #e2e8f0 !important;
            font-weight: 500 !important;
        }

        .current-range strong {
            color: #0f172a;
            font-weight: 600;
        }

        .filter-badge {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        .row { 
            display: flex !important; 
            flex-wrap: wrap !important; 
            margin: 0 -0.6rem !important; 
            max-width: 100% !important;
        }

        .col-lg-4,
        .col-md-6 { 
            padding: 0 0.6rem !important; 
            margin-bottom: 1.2rem !important; 
        }

        .col-lg-4 { 
            flex: 0 0 33.333333% !important; 
            max-width: 33.333333% !important; 
        }

        .stat-card {
            background: #ffffff !important;
            border-radius: 0.75rem !important;
            padding: 1.5rem !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08) !important;
            border: 1px solid #e2e8f0 !important;
            transition: all 0.2s ease !important;
            position: relative;
            overflow: hidden;
            height: 100%;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--card-gradient);
        }

        .stat-card:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
        }

        .card-header-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
        }

        .stat-icon {
            width: 48px !important;
            height: 48px !important;
            border-radius: 0.625rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 1.25rem !important;
            color: #fff !important;
            background: var(--card-gradient) !important;
            flex-shrink: 0;
        }

        .card-content {
            margin-top: auto;
        }

        .stat-value {
            font-size: 2rem !important;
            font-weight: 700 !important;
            color: #0f172a !important;
            margin-bottom: 0.375rem !important;
            line-height: 1 !important;
            letter-spacing: -0.5px;
        }

        .stat-title {
            color: #64748b !important;
            font-size: 0.8rem !important;
            font-weight: 500 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.3px !important;
        }

        .view-link {
            font-size: 0.75rem !important;
            color: #6366f1 !important;
            text-decoration: none !important;
            font-weight: 600 !important;
            padding: 0.375rem 0.75rem !important;
            border-radius: 0.375rem !important;
            background: #eff6ff !important;
            transition: all 0.2s ease !important;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            white-space: nowrap;
        }

        .view-link:hover {
            background: #dbeafe !important;
        }

        .view-link i {
            font-size: 0.65rem;
            transition: transform 0.3s ease;
        }

        .view-link:hover i {
            transform: translateX(2px);
        }

        @media (max-width: 991px) {
            .col-lg-4 { 
                flex: 0 0 50% !important; 
                max-width: 50% !important; 
            }
        }

        @media (max-width: 767px) {
            .sidebar { 
                transform: translateX(-100%) !important; 
            }
            .main-content { 
                margin-left: 0 !important; 
            }
            .col-md-6 { 
                flex: 0 0 100% !important; 
                max-width: 100% !important; 
            }
            .topbar {
                padding: 1.25rem 1.5rem !important;
            }
            .content {
                padding: 1.5rem !important;
            }
            .stat-value {
                font-size: 1.75rem !important;
            }
            .stat-icon {
                width: 48px !important;
                height: 48px !important;
                font-size: 1.25rem !important;
            }
            .date-filter-form {
                flex-direction: column !important;
                align-items: stretch !important;
            }
            .date-input-group {
                min-width: 100% !important;
            }
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <nav class="sidebar">
            <div class="sidebar-header">
                <a href="index.php?page=dashboard" class="logo">
                    <i class="fas fa-spa"></i><span>Shades Beauty</span>
                </a>
            </div>
            <div class="sidebar-nav">
                <ul>
                    <li><a href="index.php?page=dashboard" class="nav-link <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                    <li><a href="index.php?page=inventory" class="nav-link <?php echo $current_page == 'inventory' ? 'active' : ''; ?>"><i class="fas fa-boxes"></i><span>Inventory</span></a></li>
                    <li><a href="index.php?page=orders" class="nav-link <?php echo $current_page == 'orders' ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i><span>Orders</span></a></li>
                    <li><a href="index.php?page=customers" class="nav-link <?php echo $current_page == 'customers' ? 'active' : ''; ?>"><i class="fas fa-users"></i><span>Customers</span></a></li>
                    <li><a href="index.php?page=enquiries" class="nav-link <?php echo $current_page == 'enquiries' ? 'active' : ''; ?>"><i class="fas fa-envelope"></i><span>Enquiries</span></a></li>
                    <li><a href="index.php?page=reviews" class="nav-link <?php echo $current_page == 'reviews' ? 'active' : ''; ?>"><i class="fas fa-star"></i><span>Reviews</span></a></li>
                    <li><a href="index.php?page=discount" class="nav-link <?php echo $current_page == 'discountss' ? 'active' : ''; ?>"><i class="fas fa-ticket-alt"></i><span>discount</span></a></li>
                    <li><a href="index.php?page=reports" class="nav-link <?php echo $current_page == 'reports' ? 'active' : ''; ?>"><i class="fas fa-chart-bar"></i><span>Reports</span></a></li>
                </ul>
            </div>
        </nav>

        <div class="main-content">
            <div class="topbar">
                <div>
                    <h4>
                        <?php echo $page_title; ?>
                        <?php if($filter_applied && $current_page == 'dashboard'): ?>
                        <span class="filter-badge">
                            <i class="fas fa-filter"></i> Filtered
                        </span>
                        <?php endif; ?>
                    </h4>
                    <small id="currentDate"></small>
                </div>
                <div>
                    <button class="btn">
                        <i class="fas fa-user me-2"></i>Admin
                    </button>
                </div>
            </div>

            <div class="content">
                <?php if ($current_page == 'dashboard'): ?>
                    <!-- Dashboard Content -->
                    <div class="date-filter-section">
                        <form method="GET" action="index.php" class="date-filter-form" id="filterForm">
                            <input type="hidden" name="page" value="dashboard">
                            <div class="date-input-group">
                                <label><i class="fas fa-calendar-alt me-2"></i>From Date</label>
                                <input type="date" name="date_from" id="date_from" value="<?php echo htmlspecialchars($date_from); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="date-input-group">
                                <label><i class="fas fa-calendar-check me-2"></i>To Date</label>
                                <input type="date" name="date_to" id="date_to" value="<?php echo htmlspecialchars($date_to); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div>
                                <button type="submit" class="filter-btn">
                                    <i class="fas fa-search me-2"></i>Filter
                                </button>
                            </div>
                            <?php if($filter_applied): ?>
                            <!-- <div>
                                <a href="index.php?page=dashboard" class="reset-btn">
                                    <i class="fas fa-redo me-2"></i>Reset
                                </a>
                            </div> -->
                            <?php endif; ?>
                        </form>
                        <div class="current-range">
                            <i class="fas fa-info-circle me-2"></i>
                            <!-- Showing data from <strong><?php //echo date('M j, Y', strtotime($date_from)); ?></strong> to 
                             <strong><?php //echo date('M j, Y', strtotime($date_to)); ?></strong> -->
                            <?php //if(!$filter_applied): ?>
                            <!-- <span style="color: #6366f1; font-weight: 600;"> (Default: Current Month)</span> -->
                            <?php //endif; ?>
                        <!-- </div> -->
                    </div>

                    <div class="row">
                        <?php
                        $cards = [
                            ['icon'=>'fa-box', 'gradient'=>'linear-gradient(135deg, #6366f1, #a855f7)', 'num'=>$stats['total_products']??0, 'title'=>'Total Products', 'link'=>'inventory'],
                            ['icon'=>'fa-users', 'gradient'=>'linear-gradient(135deg, #ec4899, #f43f5e)', 'num'=>$stats['total_customers']??0, 'title'=>'Total Customers', 'link'=>'customers'],
                            ['icon'=>'fa-shopping-cart', 'gradient'=>'linear-gradient(135deg, #14b8a6, #06b6d4)', 'num'=>$stats['total_orders']??0, 'title'=>'Total Orders', 'link'=>'orders'],
                            ['icon'=>'fa-clock', 'gradient'=>'linear-gradient(135deg, #f59e0b, #f97316)', 'num'=>$stats['pending_orders']??0, 'title'=>'Pending Orders', 'link'=>'orders'],
                            ['icon'=>'fa-check-double', 'gradient'=>'linear-gradient(135deg, #10b981, #059669)', 'num'=>$stats['completed_orders']??0, 'title'=>'Completed Orders', 'link'=>'orders'],
                            ['icon'=>'fa-money-bill-wave', 'gradient'=>'linear-gradient(135deg, #8b5cf6, #6366f1)', 'num'=>'Rwf '.number_format($stats['total_revenue']??0,2), 'title'=>'Total Revenue', 'link'=>'reports']
                        ];
                        foreach($cards as $c):
                        ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="stat-card" style="--card-gradient: <?php echo $c['gradient']; ?>">
                                <div class="card-header-section">
                                    <div class="stat-icon">
                                        <i class="fas <?php echo $c['icon']; ?>"></i>
                                    </div>
                                    <?php if($c['link']): ?>
                                    <a href="index.php?page=<?php echo $c['link']; ?>" class="view-link">
                                        View <i class="fas fa-arrow-right"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <div class="card-content">
                                    <div class="stat-value"><?php echo $c['num']; ?></div>
                                    <div class="stat-title"><?php echo $c['title']; ?></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                <?php else: ?>
                    <!-- Dynamic Page Content -->
                    <?php
                    $file_name = $current_page === 'inventory' ? 'product_content.php' : $current_page . '.php';
                    
                    if (file_exists($file_name)) {
                        include($file_name);
                    } else {
                        echo '<div class="alert alert-warning m-4">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Page not found:</strong> The file "' . htmlspecialchars($file_name) . '" does not exist.
                                <br><small>Please create this file in the same directory.</small>
                              </div>';
                    }
                    ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateDateTime() {
            const now = new Date();
            const options = {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            };
            document.getElementById('currentDate').textContent = now.toLocaleString('en-US', options);
        }
        updateDateTime();
        setInterval(updateDateTime, 60000);

        // Validate date range
        const filterForm = document.getElementById('filterForm');
        if (filterForm) {
            filterForm.addEventListener('submit', function(e) {
                const fromDate = new Date(document.getElementById('date_from').value);
                const toDate = new Date(document.getElementById('date_to').value);
                
                if (fromDate > toDate) {
                    e.preventDefault();
                    alert('From Date cannot be later than To Date');
                    return false;
                }
            });

            // Update To Date minimum when From Date changes
            const dateFrom = document.getElementById('date_from');
            const dateTo = document.getElementById('date_to');
            
            if (dateFrom && dateTo) {
                dateFrom.addEventListener('change', function() {
                    dateTo.min = this.value;
                });

                dateTo.addEventListener('change', function() {
                    dateFrom.max = this.value;
                });
            }
        }

        // Fade-in animation for stat cards
        const cards = document.querySelectorAll('.stat-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    </script>
</body>
</html>