<!DOCTYPE html>
<html lang="en">

<?php session_start(); ?>

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title><?php echo isset($_SESSION['system']['name']) ? $_SESSION['system']['name'] : '' ?></title>


    <?php
    if (!isset($_SESSION['login_id']))
        header('location:login.php');
    include('./header.php');
    // include('./auth.php'); 
    ?>
</head>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clothing Store Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --light-bg: #f8fafc;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            --card-hover-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #ffffff;
            font-family: 'Poppins', sans-serif;
        }

        .container-fluid {
            background: #ffffff;
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.9);
            padding: 12px 20px;
            margin: 3px 10px;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar .nav-link i {
            width: 18px;
            margin-right: 10px;
            font-size: 0.9rem;
        }

        .main-content {
            padding: 20px;
            background: #ffffff;
            min-height: 100vh;
        }

        .header {
            background: #ffffff;
            padding: 20px 25px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }

        .header h4 {
            color: #2d3748;
            font-weight: 600;
            margin: 0;
        }

        .stats-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 15px 18px;
            margin-bottom: 15px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            position: relative;
        }

        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-hover-shadow);
        }

        .stats-card .card-icon {
            font-size: 1.8rem;
            margin-bottom: 8px;
            color: var(--card-color, var(--primary-color));
        }

        .stats-card .card-number {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--card-color, var(--primary-color));
            margin-bottom: 5px;
        }

        .stats-card .card-title {
            color: #64748b;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0;
        }

        .stats-card .card-link {
            position: absolute;
            top: 12px;
            right: 12px;
            color: #64748b;
            text-decoration: none;
            font-size: 0.7rem;
            font-weight: 500;
            padding: 4px 8px;
            background: #f8fafc;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .stats-card .card-link:hover {
            background: var(--card-color, var(--primary-color));
            color: white;
        }

        /* Color variations for cards */
        .card-success {
            --card-color: #00b894;
        }

        .card-info {
            --card-color: #0984e3;
        }

        .card-warning {
            --card-color: #fdcb6e;
        }

        .card-danger {
            --card-color: #fd79a8;
        }

        .card-primary {
            --card-color: #667eea;
        }

        .card-purple {
            --card-color: #a855f7;
        }

        .card-orange {
            --card-color: #ff7675;
        }

        .card-teal {
            --card-color: #00cec9;
        }

        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
            }

            .stats-card {
                text-align: center;
            }

            .stats-card .card-link {
                position: static;
                display: block;
                margin-top: 8px;
            }
        }

        .logo {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 20px;
        }

        .logo h4 {
            color: white;
            margin: 0;
            font-weight: 700;
            font-size: 1.3rem;
        }

        .logo i {
            margin-right: 10px;
            font-size: 1.4rem;
        }

        .form-select {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.85rem;
        }

        .form-select:focus {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            border-color: var(--primary-color);
        }

        /* Smaller row spacing */
        .stats-row {
            margin: 0 -8px;
        }

        .stats-row>[class*="col"] {
            padding: 0 8px;
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
                        <a class="nav-link active" href="#" onclick="loadPage('dashboard',event)">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                        <a class="nav-link" href="#" onclick="loadPage('clothing-category')">
                            <i class="fas fa-tags"></i> Clothing Categories
                        </a>
                        <a class="nav-link" href="#" onclick="loadPage('products')">
                            <i class="fas fa-tshirt"></i> Products
                        </a>
                        <!-- <a class="nav-link" href="#" onclick="loadPage('inventory',event)">
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
                            <i class="fas fa-envelope"></i> Customer Enquiry
                        </a>
                        <a class="nav-link" href="#" onclick="loadPage('reviews')">
                            <i class="fas fa-star"></i> Reviews
                        </a>
                        <a class="nav-link" href="#" onclick="loadPage('coupons')">
                            <i class="fas fa-ticket-alt"></i> Coupons
                        </a>
                        <a class="nav-link" href="#" onclick="loadPage('reports')">
                            <i class="fas fa-chart-bar"></i> Sales Reports
                        </a>
                        <!-- <a class="nav-link" href="#" onclick="loadPage('search-order')">
                            <i class="fas fa-search"></i> Search Order
                        </a> -->
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
                                <?php
                                session_start();
                                ?>
                                <div class="dropdown">
                                    <?php
                                    session_start();
                                    $username = isset($_SESSION['login_username']) ? htmlspecialchars($_SESSION['login_username']) : 'Guest';
                                    ?>

                                    <!-- Button shows logged-in username -->
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                        type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-person-fill me-1"></i> <strong><?php echo $username; ?></strong>
                                    </button>

                                    <!-- Dropdown menu -->
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuButton">
                                        <?php if (isset($_SESSION['login_username'])): ?>
                                            <li><a class="dropdown-item" href="settings.php">System Settings</a></li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                                        <?php else: ?>
                                            <li><a class="dropdown-item" href="login.php">Login</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>

                            </div>


                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row stats-row">
                        <!-- Total Products -->
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="stats-card card-success">
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
                            <div class="stats-card card-purple">
                                <a href="#" class="card-link">View Customers</a>
                                <div class="card-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="card-number" id="total-customers">1,247</div>
                                <div class="card-title">Total Customers</div>
                            </div>
                        </div>

                        <!-- Low Stock Items -->
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="stats-card card-warning">
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
                            <div class="stats-card card-danger">
                                <a href="#" class="card-link">Process Orders</a>
                                <div class="card-icon">
                                    <i class="fas fa-shopping-bag"></i>
                                </div>
                                <div class="card-number" id="new-orders">23</div>
                                <div class="card-title">New Orders</div>
                            </div>
                        </div>

                        <!-- Pending Orders -->
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="stats-card card-info">
                                <div class="card-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="card-number" id="pending-orders">15</div>
                                <div class="card-title">Pending Orders</div>
                            </div>
                        </div>

                        <!-- Delivered Orders -->
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="stats-card card-teal">
                                <div class="card-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="card-number" id="delivered-orders">156</div>
                                <div class="card-title">Served Orders</div>
                            </div>
                        </div>

                        <!-- Today's Revenue -->
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="stats-card card-success">
                                <div class="card-icon">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="card-number" id="today-revenue">$2,456</div>
                                <div class="card-title">Today's Revenue</div>
                            </div>
                        </div>

                        <!-- Monthly Revenue -->
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="stats-card card-primary">
                                <div class="card-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="card-number" id="monthly-revenue">$48,925</div>
                                <div class="card-title">Monthly Revenue</div>
                            </div>
                        </div>

                        <!-- Customer Reviews -->
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="stats-card card-warning">
                                <div class="card-icon">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="card-number" id="new-reviews">34</div>
                                <div class="card-title">New Reviews</div>
                            </div>
                        </div>

                        <!-- Active Coupons -->
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="stats-card card-purple">
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
        // function loadPage(page, event) {
        //     document.querySelectorAll('.nav-link').forEach(link => {
        //         link.classList.remove('active');
        //     });

        //     if(event) event.target.classList.add('active');

        //     console.log('Loading page:', page);

        //     // Update header title
        //     const headerTitles = {
        //         'dashboard': 'Fashion Store Dashboard',
        //         'clothing-category': 'Clothing Categories Management',
        //         'inventory': 'Inventory Management',
        //         // ... other pages
        //     };

        //     const headerElement = document.querySelector('.header h4');
        //     if(headerTitles[page]) headerElement.textContent = headerTitles[page];
        // }
        window.loadPage = function(page, event) {
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });

            if (event) event.currentTarget.classList.add('active');

            console.log('Loading page:', page);

            const headerTitles = {
                'dashboard': 'Fashion Store Dashboard',
                'clothing-category': 'Clothing Categories Management',
                'inventory': 'Inventory Management',
            };

            const headerElement = document.querySelector('.header h4');
            if (headerTitles[page]) headerElement.textContent = headerTitles[page];

            // Load dynamic content
            const contentDiv = document.getElementById('content');
            if (contentDiv) {
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
    </script>
</body>

</html>