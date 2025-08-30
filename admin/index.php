<!DOCTYPE html>
<html lang="en">

<?php session_start(); ?>

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title><?php echo isset($_SESSION['system']['name']) ? $_SESSION['system']['name'] : 'Fashion Store Admin' ?></title>

    <?php
    if (!isset($_SESSION['login_id']))
        header('location:login.php');
    // include('./header.php');
    // include('./auth.php'); 
    ?>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-gold: #d4af37;
            --secondary-gold: #f4e4bc;
            --dark-bg: #1a1a1a;
            --light-dark: #2d2d2d;
            --card-bg: #ffffff;
            --text-dark: #1a1a1a;
            --text-muted: #666;
            --border-light: #e8e8e8;
            --card-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            --card-hover-shadow: 0 12px 40px rgba(212, 175, 55, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1b2e 25%, #1a1a1a 50%, #2e2420 75%, #1a1a1a 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
        }

        .container-fluid {
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            background: linear-gradient(180deg, var(--dark-bg) 0%, var(--light-dark) 50%, var(--dark-bg) 100%);
            min-height: 100vh;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.3);
            border-right: 1px solid rgba(212, 175, 55, 0.1);
            position: relative;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 2px;
            height: 100%;
            background: linear-gradient(180deg, transparent, var(--primary-gold), transparent);
        }

        .logo {
            padding: 2rem 1.5rem;
            text-align: center;
            border-bottom: 2px solid rgba(212, 175, 55, 0.2);
            margin-bottom: 1.5rem;
            position: relative;
        }

        .logo h4 {
            font-family: 'Playfair Display', serif;
            color: var(--primary-gold);
            margin: 0;
            font-weight: 700;
            font-size: 1.4rem;
            letter-spacing: 2px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .logo i {
            margin-right: 12px;
            font-size: 1.6rem;
            animation: iconGlow 3s ease-in-out infinite;
        }

        @keyframes iconGlow {
            0%, 100% { filter: drop-shadow(0 0 8px rgba(212, 175, 55, 0.3)); }
            50% { filter: drop-shadow(0 0 16px rgba(212, 175, 55, 0.6)); }
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 1rem 1.5rem;
            margin: 0.3rem 1rem;
            border-radius: 12px;
            transition: all 0.4s ease;
            font-size: 0.95rem;
            font-weight: 400;
            letter-spacing: 0.5px;
            border: 1px solid transparent;
        }

        .sidebar .nav-link:hover {
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.15), rgba(244, 228, 188, 0.1));
            color: var(--primary-gold);
            transform: translateX(8px);
            border-color: rgba(212, 175, 55, 0.3);
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.2);
        }

        .sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--primary-gold), var(--secondary-gold));
            color: var(--dark-bg);
            font-weight: 600;
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.4);
            border-color: var(--primary-gold);
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 12px;
            font-size: 1rem;
        }

        /* Main Content */
        .main-content {
            padding: 2rem;
            background: transparent;
            min-height: 100vh;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1.8rem 2rem;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            border: 2px solid rgba(212, 175, 55, 0.1);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-gold), var(--secondary-gold), var(--primary-gold));
            background-size: 200% 100%;
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { background-position: -100% 0; }
            50% { background-position: 100% 0; }
        }

        .header h4 {
            font-family: 'Playfair Display', serif;
            color: var(--text-dark);
            font-weight: 700;
            margin: 0;
            font-size: 1.8rem;
            letter-spacing: 1px;
        }

        /* Stats Cards */
        .stats-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 18px;
            padding: 1.8rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: all 0.4s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--card-gradient, linear-gradient(90deg, var(--primary-gold), var(--secondary-gold)));
        }

        .stats-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--card-hover-shadow);
            border-color: rgba(212, 175, 55, 0.2);
        }

        .stats-card .card-icon {
            font-size: 2.2rem;
            margin-bottom: 1rem;
            color: var(--card-color, var(--primary-gold));
            animation: iconFloat 4s ease-in-out infinite;
        }

        @keyframes iconFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .stats-card .card-number {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--card-color, var(--primary-gold));
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(212, 175, 55, 0.2);
        }

        .stats-card .card-title {
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0;
        }

        .stats-card .card-link {
            position: absolute;
            top: 1rem;
            right: 1rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.4rem 0.8rem;
            background: rgba(212, 175, 55, 0.1);
            border-radius: 12px;
            transition: all 0.3s ease;
            border: 1px solid rgba(212, 175, 55, 0.2);
        }

        .stats-card .card-link:hover {
            background: var(--primary-gold);
            color: white;
            transform: scale(1.05);
        }

        /* Color variations with gold accent */
        .card-success {
            --card-color: #10b981;
            --card-gradient: linear-gradient(90deg, #10b981, #34d399);
        }

        .card-info {
            --card-color: #3b82f6;
            --card-gradient: linear-gradient(90deg, #3b82f6, #60a5fa);
        }

        .card-warning {
            --card-color: #f59e0b;
            --card-gradient: linear-gradient(90deg, #f59e0b, #fbbf24);
        }

        .card-danger {
            --card-color: #ef4444;
            --card-gradient: linear-gradient(90deg, #ef4444, #f87171);
        }

        .card-primary {
            --card-color: var(--primary-gold);
            --card-gradient: linear-gradient(90deg, var(--primary-gold), var(--secondary-gold));
        }

        .card-purple {
            --card-color: #8b5cf6;
            --card-gradient: linear-gradient(90deg, #8b5cf6, #a78bfa);
        }

        .card-orange {
            --card-color: #f97316;
            --card-gradient: linear-gradient(90deg, #f97316, #fb923c);
        }

        .card-teal {
            --card-color: #14b8a6;
            --card-gradient: linear-gradient(90deg, #14b8a6, #2dd4bf);
        }

        /* Dropdown styling */
        .dropdown-toggle {
            background: rgba(255, 255, 255, 0.9) !important;
            border: 2px solid rgba(212, 175, 55, 0.3) !important;
            color: var(--text-dark) !important;
            border-radius: 12px;
            padding: 0.6rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .dropdown-toggle:hover {
            background: var(--primary-gold) !important;
            color: white !important;
            border-color: var(--primary-gold) !important;
        }

        .form-select {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(212, 175, 55, 0.3);
            border-radius: 12px;
            font-weight: 500;
            font-size: 0.85rem;
            color: var(--text-dark);
        }

        .form-select:focus {
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
            border-color: var(--primary-gold);
        }

        /* Stats row spacing */
        .stats-row {
            margin: 0 -10px;
        }

        .stats-row > [class*="col"] {
            padding: 0 10px;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
            }

            .main-content {
                padding: 1rem;
            }

            .header {
                padding: 1.5rem;
            }

            .stats-card {
                text-align: center;
                padding: 1.5rem;
            }

            .stats-card .card-link {
                position: static;
                display: inline-block;
                margin-top: 0.8rem;
            }

            .logo h4 {
                font-size: 1.2rem;
            }
        }

        /* Fashion background elements */
        .fashion-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .fashion-element {
            position: absolute;
            opacity: 0.02;
            animation: fashionFloat 30s ease-in-out infinite;
            color: var(--primary-gold);
        }

        .fashion-element:nth-child(1) { 
            font-size: 15rem; 
            left: -10%; 
            top: 10%; 
            animation-delay: 0s; 
        }
        .fashion-element:nth-child(2) { 
            font-size: 12rem; 
            right: -5%; 
            top: 50%; 
            animation-delay: 10s; 
        }
        .fashion-element:nth-child(3) { 
            font-size: 18rem; 
            left: 20%; 
            bottom: -15%; 
            animation-delay: 20s; 
        }

        @keyframes fashionFloat {
            0%, 100% { transform: translateY(0) rotate(0deg) scale(1); opacity: 0.02; }
            25% { transform: translateY(-100px) rotate(15deg) scale(1.1); opacity: 0.04; }
            50% { transform: translateY(0) rotate(-10deg) scale(0.9); opacity: 0.03; }
            75% { transform: translateY(50px) rotate(12deg) scale(1.05); opacity: 0.05; }
        }

        /* Add luxury details */
        .luxury-accent {
            position: absolute;
            width: 100px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary-gold), transparent);
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
        }

        /* Enhanced card animations */
        .stats-card {
            animation: cardFadeIn 0.6s ease-out forwards;
            animation-delay: calc(var(--animation-order) * 0.1s);
        }

        @keyframes cardFadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Status indicators */
        .status-indicator {
            position: absolute;
            top: 1rem;
            left: 1rem;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--card-color, var(--primary-gold));
            animation: statusPulse 2s ease-in-out infinite;
        }

        @keyframes statusPulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.2); }
        }
    </style>
</head>

<body>
    <!-- Fashion background elements -->
    <div class="fashion-bg">
        <div class="fashion-element"><i class="fas fa-gem"></i></div>
        <div class="fashion-element"><i class="fas fa-crown"></i></div>
        <div class="fashion-element"><i class="fas fa-star"></i></div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="logo">
                        <h4><i class="fas fa-tshirt"></i> FASHION STORE</h4>
                        <div class="luxury-accent"></div>
                    </div>
                    <nav class="nav flex-column px-2">
                        <a class="nav-link active" href="#" onclick="loadPage('dashboard')">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                        <!-- <a class="nav-link" href="#" onclick="loadPage('inventory')">
                            <i class="fas fa-boxes"></i> Inventory
                        </a> -->
                         <a class="nav-link" href="#" onclick="loadPage('inventory', event)">
                            <i class="fas fa-boxes"></i> Inventory
                        </a>
                        <a class="nav-link" href="#" onclick="loadPage('sizes-colors')">
                            <i class="fas fa-palette"></i> Sizes & Colors
                        </a>
                        <a class="nav-link" href="#" onclick="loadPage('orders')">
                            <i class="fas fa-shopping-cart"></i> Orders
                        </a>
                        <a class="nav-link" href="#" onclick="loadPage('customers')">
                            <i class="fas fa-users"></i> Customers
                        </a>
                        <a class="nav-link" href="#" onclick="loadPage('enquiry')">
                            <i class="fas fa-envelope"></i> Enquiries
                        </a>
                        <a class="nav-link" href="#" onclick="loadPage('reviews')">
                            <i class="fas fa-star"></i> Reviews
                        </a>
                        <a class="nav-link" href="#" onclick="loadPage('coupons')">
                            <i class="fas fa-ticket-alt"></i> Coupons
                        </a>
                        <a class="nav-link" href="#" onclick="loadPage('reports')">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <!-- Header -->
                    <div class="header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4>Fashion Store Dashboard</h4>
                                <small class="text-muted" id="current-date"></small>
                                 <small class="text-muted" id="current-date"></small>
                            </div>
                            <div class="col-auto">
                                <select class="form-select form-select-sm">
                                    <option>Select Currency</option>
                                    <option>USD ($)</option>
                                    <option>EUR (€)</option>
                                    <option>GBP (£)</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <div class="dropdown">
                                    <?php
                                    $username = isset($_SESSION['login_username']) ? htmlspecialchars($_SESSION['login_username']) : 'Admin';
                                    ?>
                                    <button class="btn btn-sm dropdown-toggle" 
                                        type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-user me-2"></i><strong><?php echo $username; ?></strong>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuButton">
                                        <?php if (isset($_SESSION['login_username'])): ?>
                                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                                        <?php else: ?>
                                            <li><a class="dropdown-item" href="login.php">Login</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
<div id="content">
    <!-- This is where productss.php will load -->
    <h2>Welcome!</h2>
</div>
                    <!-- Stats Cards -->
                    <div class="row stats-row">
                        <!-- Total Products -->
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="stats-card card-success" style="--animation-order: 0">
                                <div class="status-indicator"></div>
                                <a href="#" class="card-link">Add Product</a>
                                <div class="card-icon">
                                    <i class="fas fa-tshirt"></i>
                                </div>
                                <div class="card-number" id="total-products">245</div>
                                <div class="card-title">Total Products</div>
                            </div>
                        </div>

                        <!-- Total Customers -->
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="stats-card card-purple" style="--animation-order: 1">
                                <div class="status-indicator"></div>
                                <a href="#" class="card-link">View All</a>
                                <div class="card-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="card-number" id="total-customers">1,247</div>
                                <div class="card-title">Total Customers</div>
                            </div>
                        </div>

                        <!-- Low Stock Items -->
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="stats-card card-warning" style="--animation-order: 2">
                                <div class="status-indicator"></div>
                                <a href="#" class="card-link">Restock</a>
                                <div class="card-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="card-number" id="low-stock-items">8</div>
                                <div class="card-title">Low Stock Items</div>
                            </div>
                        </div>

                        <!-- New Orders -->
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="stats-card card-danger" style="--animation-order: 3">
                                <div class="status-indicator"></div>
                                <a href="#" class="card-link">Process</a>
                                <div class="card-icon">
                                    <i class="fas fa-shopping-bag"></i>
                                </div>
                                <div class="card-number" id="new-orders">23</div>
                                <div class="card-title">New Orders</div>
                            </div>
                        </div>

                        <!-- Pending Orders -->
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="stats-card card-info" style="--animation-order: 4">
                                <div class="status-indicator"></div>
                                <div class="card-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="card-number" id="pending-orders">15</div>
                                <div class="card-title">Pending Orders</div>
                            </div>
                        </div>

                        <!-- Delivered Orders -->
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="stats-card card-teal" style="--animation-order: 5">
                                <div class="status-indicator"></div>
                                <div class="card-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="card-number" id="delivered-orders">156</div>
                                <div class="card-title">Completed Orders</div>
                            </div>
                        </div>

                        <!-- Today's Revenue -->
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="stats-card card-success" style="--animation-order: 6">
                                <div class="status-indicator"></div>
                                <div class="card-icon">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="card-number" id="today-revenue">$2,456</div>
                                <div class="card-title">Today's Revenue</div>
                            </div>
                        </div>

                        <!-- Monthly Revenue -->
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="stats-card card-primary" style="--animation-order: 7">
                                <div class="status-indicator"></div>
                                <div class="card-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="card-number" id="monthly-revenue">$48,925</div>
                                <div class="card-title">Monthly Revenue</div>
                            </div>
                        </div>

                        <!-- Customer Reviews -->
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="stats-card card-warning" style="--animation-order: 8">
                                <div class="status-indicator"></div>
                                <div class="card-icon">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="card-number" id="new-reviews">34</div>
                                <div class="card-title">New Reviews</div>
                            </div>
                        </div>

                        <!-- Active Coupons -->
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="stats-card card-purple" style="--animation-order: 9">
                                <div class="status-indicator"></div>
                                <div class="card-icon">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                                <div class="card-number" id="active-coupons">7</div>
                                <div class="card-title">Active Coupons</div>
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
        // Set current date
        document.getElementById('current-date').textContent = new Date().toLocaleString('en-US', {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });

        // Load page function
        // function loadPage(page) {
        //     // Remove active class from all nav links
        //     document.querySelectorAll('.nav-link').forEach(link => {
        //         link.classList.remove('active');
        //     });

        //     // Add active class to clicked link
        //     event.target.classList.add('active');

        //     console.log('Loading page:', page);

        //     // Update header title based on page
        //     const headerTitles = {
        //         'dashboard': 'Fashion Store Dashboard',
        //         'clothing-category': 'Categories Management',
        //         'products': 'Products Management',
        //         'inventory': 'Inventory Management',
        //         'sizes-colors': 'Sizes & Colors',
        //         'orders': 'Orders Management',
        //         'customers': 'Customer Management',
        //         'enquiry': 'Customer Enquiries',
        //         'reviews': 'Customer Reviews',
        //         'coupons': 'Coupons Management',
        //         'reports': 'Sales Reports'
        //     };

        //     const headerElement = document.querySelector('.header h4');
        //     if (headerTitles[page]) {
        //         headerElement.textContent = headerTitles[page];
        //     }
        // }

        // Function to update stats (integrate with your backend)
        function updateStats(data) {
            const elements = {
                'total-products': data.totalProducts,
                'total-customers': data.totalCustomers,
                'low-stock-items': data.lowStockItems,
                'new-orders': data.newOrders,
                'pending-orders': data.pendingOrders,
                'delivered-orders': data.deliveredOrders,
                'today-revenue': '$' + (data.todayRevenue || 0).toLocaleString(),
                'monthly-revenue': '$' + (data.monthlyRevenue || 0).toLocaleString(),
                'new-reviews': data.newReviews,
                'active-coupons': data.activeCoupons
            };

            Object.entries(elements).forEach(([id, value]) => {
                const element = document.getElementById(id);
                if (element && value !== undefined) {
                    element.textContent = value;
                }
            });
        }
//         function loadPage(page) {
//     let contentDiv = document.getElementById('content');

//     if(page === 'inventory') {
//         fetch('productss.php')
//         .then(response => {
//             if (!response.ok) throw new Error('Network response was not ok');
//             return response.text();
//         })
//         .then(html => {
//             contentDiv.innerHTML = html;
//         })
//         .catch(error => {
//             contentDiv.innerHTML = `<p style="color:red;">Error loading page: ${error}</p>`;
//         });
//     }  // Set current date
        document.getElementById('current-date').textContent = new Date().toLocaleString('en-US', {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });

        // Load page function
        // function loadPage(page) {
        //     // Remove active class from all nav links
        //     document.querySelectorAll('.nav-link').forEach(link => {
        //         link.classList.remove('active');
        //     });

        //     // Add active class to clicked link
        //     event.target.classList.add('active');

        //     console.log('Loading page:', page);

        //     // Update header title based on page
        //     const headerTitles = {
        //         'dashboard': 'Fashion Store Dashboard',
        //         'clothing-category': 'Clothing Categories Management',
        //         'products': 'Products Management',
        //         'inventory': 'Inventory Management',
        //         'sizes-colors': 'Sizes & Colors Management',
        //         'orders': 'Orders Management',
        //         'customers': 'Customer Management',
        //         'enquiry': 'Customer Enquiries',
        //         'reviews': 'Customer Reviews',
        //         'coupons': 'Coupons Management',
        //         'reports': 'Sales Reports',
        //         'search-order': 'Search Orders'
        //     };

        //     const headerElement = document.querySelector('.header h4');
        //     if (headerTitles[page]) {
        //         headerElement.textContent = headerTitles[page];
        //     }
        // }
window.loadPage = function(page, event) {
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });

    if(event) event.currentTarget.classList.add('active');

    console.log('Loading page:', page);

    const headerTitles = {
        'dashboard': 'Fashion Store Dashboard',
        'clothing-category': 'Clothing Categories Management',
        'inventory': 'Inventory Management',
    };

    const headerElement = document.querySelector('.header h4');
    if(headerTitles[page]) headerElement.textContent = headerTitles[page];

    // Load dynamic content
    const contentDiv = document.getElementById('content');
    if(contentDiv) {
        fetch(page === 'inventory' ? 'productss.php' : page + '.php')
            .then(response => response.text())
            .then(html => contentDiv.innerHTML = html)
            .catch(err => contentDiv.innerHTML = `<p style="color:red;">Error loading page: ${err}</p>`);
    }
};

        // Function to update stats
        function updateStats(data) {
            if (data.totalProducts !== undefined) {
                document.getElementById('total-products').textContent = data.totalProducts;
            }
            if (data.totalCustomers !== undefined) {
                document.getElementById('total-customers').textContent = data.totalCustomers.toLocaleString();
            }
            if (data.lowStockItems !== undefined) {
                document.getElementById('low-stock-items').textContent = data.lowStockItems;
            }
            if (data.newOrders !== undefined) {
                document.getElementById('new-orders').textContent = data.newOrders;
            }
            if (data.pendingOrders !== undefined) {
                document.getElementById('pending-orders').textContent = data.pendingOrders;
            }
            if (data.deliveredOrders !== undefined) {
                document.getElementById('delivered-orders').textContent = data.deliveredOrders;
            }
            if (data.todayRevenue !== undefined) {
                document.getElementById('today-revenue').textContent = '$' + data.todayRevenue.toLocaleString();
            }
            if (data.monthlyRevenue !== undefined) {
                document.getElementById('monthly-revenue').textContent = '$' + data.monthlyRevenue.toLocaleString();
            }
            if (data.newReviews !== undefined) {
                document.getElementById('new-reviews').textContent = data.newReviews;
            }
            if (data.activeCoupons !== undefined) {
                document.getElementById('active-coupons').textContent = data.activeCoupons;
            }
        }

        // Update stats with sample data
        setTimeout(() => {
            updateStats({
                totalProducts: 245,
                totalCustomers: 1247,
                lowStockItems: 8,
                newOrders: 23,
                pendingOrders: 15,
                deliveredOrders: 156,
                todayRevenue: 2456,
                monthlyRevenue: 48925,
                newReviews: 34,
                activeCoupons: 7
            });
        }, 100);
// }
