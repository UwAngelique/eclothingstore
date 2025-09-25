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

        /* Content area styling */
        #content {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            border: 2px solid rgba(212, 175, 55, 0.1);
            min-height: 400px;
        }

        /* Loading spinner */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .loading-spinner.show {
            display: block;
        }

        .spinner-border {
            color: var(--primary-gold);
        }

        /* Page content containers */
        .page-content {
            display: none;
        }

        .page-content.active {
            display: block;
        }

        /* Dashboard specific styles */
        .stats-row {
            margin: 0 -10px;
        }

        .stats-row > [class*="col"] {
            padding: 0 10px;
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
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="logo">
                        <h4><i class="fas fa-tshirt"></i> FASHION STORE</h4>
                    </div>
                    <nav class="nav flex-column px-2">
                        <a class="nav-link active" href="#" onclick="showPage('dashboard', event)">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                        <a class="nav-link" href="#" onclick="showPage('inventory', event)">
                            <i class="fas fa-boxes"></i> Inventory
                        </a>
                        <a class="nav-link" href="#" onclick="showPage('sizes-colors', event)">
                            <i class="fas fa-palette"></i> Sizes & Colors
                        </a>
                        <a class="nav-link" href="#" onclick="showPage('orders', event)">
                            <i class="fas fa-shopping-cart"></i> Orders
                        </a>
                        <a class="nav-link" href="#" onclick="showPage('customers', event)">
                            <i class="fas fa-users"></i> Customers
                        </a>
                        <a class="nav-link" href="#" onclick="showPage('enquiry', event)">
                            <i class="fas fa-envelope"></i> Enquiries
                        </a>
                        <a class="nav-link" href="#" onclick="showPage('reviews', event)">
                            <i class="fas fa-star"></i> Reviews
                        </a>
                        <a class="nav-link" href="#" onclick="showPage('coupons', event)">
                            <i class="fas fa-ticket-alt"></i> Coupons
                        </a>
                        <a class="nav-link" href="#" onclick="showPage('reports', event)">
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
                                <h4 id="page-title">Fashion Store Dashboard</h4>
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

                    <!-- Dynamic Content Area -->
                    <div id="content">
                        <!-- Loading spinner -->
                        <div class="loading-spinner" id="loading-spinner">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading page...</p>
                        </div>

                        <!-- Dashboard Page -->
                        <div class="page-content active" id="dashboard-page">
                            <div class="row stats-row">
                                <!-- Total Products -->
                                <div class="col-lg-3 col-md-4 col-sm-6">
                                    <div class="stats-card card-success" style="--animation-order: 0">
                                        <div class="status-indicator"></div>
                                        <a href="#" class="card-link" onclick="showPage('inventory', event)">View All</a>
                                        <div class="card-icon">
                                            <i class="fas fa-tshirt"></i>
                                        </div>
                                        <div class="card-number" id="total-products">2</div>
                                        <div class="card-title">Total Products</div>
                                    </div>
                                </div>

                                <!-- Total Customers -->
                                <div class="col-lg-3 col-md-4 col-sm-6">
                                    <div class="stats-card card-purple" style="--animation-order: 1">
                                        <div class="status-indicator"></div>
                                        <a href="#" class="card-link" onclick="showPage('customers', event)">View All</a>
                                        <div class="card-icon">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="card-number" id="total-customers">1</div>
                                        <div class="card-title">Total Customers</div>
                                    </div>
                                </div>

                                <!-- Low Stock Items -->
                                <div class="col-lg-3 col-md-4 col-sm-6">
                                    <div class="stats-card card-warning" style="--animation-order: 2">
                                        <div class="status-indicator"></div>
                                        <a href="#" class="card-link" onclick="showPage('inventory', event)">Restock</a>
                                        <div class="card-icon">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                        <div class="card-number" id="low-stock-items">10</div>
                                        <div class="card-title">Low Stock Items</div>
                                    </div>
                                </div>

                                <!-- New Orders -->
                                <div class="col-lg-3 col-md-4 col-sm-6">
                                    <div class="stats-card card-danger" style="--animation-order: 3">
                                        <div class="status-indicator"></div>
                                        <a href="#" class="card-link" onclick="showPage('orders', event)">Process</a>
                                        <div class="card-icon">
                                            <i class="fas fa-shopping-bag"></i>
                                        </div>
                                        <div class="card-number" id="new-orders">2</div>
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
                                        <div class="card-number" id="pending-orders">1</div>
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
                                        <div class="card-number" id="delivered-orders">1</div>
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
                                        <div class="card-number" id="today-revenue">RWF 2,456</div>
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
                                        <div class="card-number" id="monthly-revenue">RWF 48,925</div>
                                        <div class="card-title">Monthly Revenue</div>
                                    </div>
                                </div>

                                <!-- Customer Reviews -->
                                <div class="col-lg-3 col-md-4 col-sm-6">
                                    <div class="stats-card card-warning" style="--animation-order: 8">
                                        <div class="status-indicator"></div>
                                        <a href="#" class="card-link" onclick="showPage('reviews', event)">View All</a>
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
                                        <a href="#" class="card-link" onclick="showPage('coupons', event)">View All</a>
                                        <div class="card-icon">
                                            <i class="fas fa-ticket-alt"></i>
                                        </div>
                                        <div class="card-number" id="active-coupons">7</div>
                                        <div class="card-title">Active Coupons</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Other Pages will be loaded here -->
                        <div class="page-content" id="other-pages"></div>
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

        // Global loadPage function
        window.loadPage = function(page, event) {
            // Prevent default link behavior
            if (event) {
                event.preventDefault();
            }

            // Remove active class from all nav links
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });

            // Add active class to clicked link
            if (event && event.currentTarget) {
                event.currentTarget.classList.add('active');
            }

            console.log('Loading page:', page);

            // Update header title based on page
            const headerTitles = {
                'dashboard': 'Fashion Store Dashboard',
                'inventory': 'Inventory Management',
                'sizes-colors': 'Sizes & Colors Management',
                'orders': 'Orders Management',
                'customers': 'Customer Management',
                'enquiry': 'Customer Enquiries',
                'reviews': 'Customer Reviews',
                'coupons': 'Coupons Management',
                'reports': 'Sales Reports'
            };

            const headerElement = document.querySelector('.header h4');
            if (headerTitles[page]) {
                headerElement.textContent = headerTitles[page];
            }

            // Get content container
            const contentDiv = document.getElementById('content');
            const loadingSpinner = document.getElementById('loading-spinner');
            const dashboardContent = document.getElementById('dashboard-content');

            if (!contentDiv) {
                console.error('Content div not found!');
                return;
            }

            // Show loading spinner
            if (loadingSpinner) {
                loadingSpinner.classList.add('active');
            }

            // Hide dashboard content
            if (dashboardContent) {
                dashboardContent.style.display = 'none';
            }

            // Handle different pages
            if (page === 'dashboard') {
                // Hide loading spinner
                if (loadingSpinner) {
                    loadingSpinner.classList.remove('active');
                }
                
                // Clear any loaded content and show dashboard
                contentDiv.innerHTML = '';
                contentDiv.appendChild(loadingSpinner);
                
                if (dashboardContent) {
                    contentDiv.appendChild(dashboardContent);
                    dashboardContent.style.display = 'block';
                }
                return;
            }

            // For other pages, load content via AJAX
            let filename;
            switch(page) {
                case 'inventory':
                    filename = 'productss.php';
                    break;
                case 'sizes-colors':
                    filename = 'sizes-colors.php';
                    break;
                case 'orders':
                    filename = 'orders.php';
                    break;
                case 'customers':
                    filename = 'customers.php';
                    break;
                case 'enquiry':
                    filename = 'enquiry.php';
                    break;
                case 'reviews':
                    filename = 'reviews.php';
                    break;
                case 'coupons':
                    filename = 'coupons.php';
                    break;
                case 'reports':
                    filename = 'reports.php';
                    break;
                default:
                    filename = page + '.php';
            }

            // Fetch the page content
            fetch(filename)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(html => {
                    // Hide loading spinner
                    if (loadingSpinner) {
                        loadingSpinner.classList.remove('active');
                    }
                    
                    // Clear content and add the loaded content
                    contentDiv.innerHTML = '';
                    contentDiv.appendChild(loadingSpinner);
                    
                    // Create a container for the loaded content
                    const loadedContentDiv = document.createElement('div');
                    loadedContentDiv.innerHTML = html;
                    contentDiv.appendChild(loadedContentDiv);
                    
                    // Execute any scripts in the loaded content
                    const scripts = loadedContentDiv.getElementsByTagName('script');
                    for (let i = 0; i < scripts.length; i++) {
                        const script = scripts[i];
                        const newScript = document.createElement('script');
                        if (script.src) {
                            newScript.src = script.src;
                        } else {
                            newScript.innerHTML = script.innerHTML;
                        }
                        document.head.appendChild(newScript);
                        script.parentNode.removeChild(script);
                        i--;
                    }
                })
                .catch(error => {
                    console.error('Error loading page:', error);
                    
                    // Hide loading spinner
                    if (loadingSpinner) {
                        loadingSpinner.classList.remove('active');
                    }
                    
                    // Show error message
                    contentDiv.innerHTML = '';
                    contentDiv.appendChild(loadingSpinner);
                    
                    const errorDiv = document.createElement('div');
                    errorDiv.innerHTML = `
                        <div class="alert alert-danger" role="alert">
                            <h4 class="alert-heading">Error Loading Page</h4>
                            <p>Sorry, there was an error loading the ${page} page.</p>
                            <hr>
                            <p class="mb-0">Error details: ${error.message}</p>
                            <button class="btn btn-outline-danger mt-2" onclick="loadPage('dashboard')">
                                <i class="fas fa-home me-2"></i>Return to Dashboard
                            </button>
                        </div>
                    `;
                    contentDiv.appendChild(errorDiv);
                });
        };

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Set dashboard as active by default - no need to call loadPage
            // Dashboard content is already visible by default
            console.log('Dashboard initialized');
        });
    </script>