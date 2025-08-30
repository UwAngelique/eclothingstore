<?php


ini_set('display_errors', 0);          // hide errors from output
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Categories & Products Management</title>
    <!-- <link rel="stylesheet" href="style.css"> -->
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
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1b2e 25%, #1a1a1a 50%, #2e2420 75%, #1a1a1a 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        .page-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header Section */
        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            border: 2px solid rgba(212, 175, 55, 0.1);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
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

            0%,
            100% {
                background-position: -100% 0;
            }

            50% {
                background-position: 100% 0;
            }
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            color: var(--text-dark);
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 1rem;
            margin-bottom: 0;
        }

        /* Tab Navigation */
        .nav-tabs {
            border: none;
            margin-bottom: 2rem;
        }

        .nav-tabs .nav-link {
            background: rgba(255, 255, 255, 0.8);
            border: 2px solid rgba(212, 175, 55, 0.2);
            color: var(--text-dark);
            padding: 1rem 2rem;
            border-radius: 16px 16px 0 0;
            margin-right: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-tabs .nav-link.active {
            background: var(--primary-gold);
            color: white;
            border-color: var(--primary-gold);
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        }

        .nav-tabs .nav-link:hover:not(.active) {
            background: rgba(212, 175, 55, 0.1);
            border-color: var(--primary-gold);
        }

        /* Card Styling */
        .management-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            border: 2px solid rgba(212, 175, 55, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        /* Form Styling */
        .form-label {
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-control,
        .form-select {
            background: rgba(248, 248, 248, 0.9);
            border: 2px solid var(--border-light);
            border-radius: 12px;
            padding: 0.8rem 1rem;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        .form-control:focus,
        .form-select:focus {
            background: white;
            border-color: var(--primary-gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
            outline: none;
        }

        /* Button Styling */
        .btn-gold {
            background: linear-gradient(135deg, var(--primary-gold), var(--secondary-gold));
            color: var(--dark-bg);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
            color: var(--dark-bg);
        }

        .btn-outline-gold {
            background: transparent;
            color: var(--primary-gold);
            border: 2px solid var(--primary-gold);
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-gold:hover {
            background: var(--primary-gold);
            color: white;
        }

        /* Table Styling */
        .table-container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(212, 175, 55, 0.1);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: linear-gradient(135deg, var(--primary-gold), var(--secondary-gold));
            color: var(--dark-bg);
            font-weight: 600;
            border: none;
            padding: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background: rgba(212, 175, 55, 0.05);
        }

        .table tbody td {
            padding: 1rem;
            border-color: rgba(212, 175, 55, 0.1);
            vertical-align: middle;
        }

        /* Action Buttons */
        .btn-action {
            padding: 0.4rem 0.8rem;
            margin: 0 0.2rem;
            border-radius: 8px;
            border: none;
            font-size: 0.8rem;
            transition: all 0.2s ease;
        }

        .btn-edit {
            background: var(--warning-color);
            color: white;
        }

        .btn-delete {
            background: var(--danger-color);
            color: white;
        }

        .btn-action:hover {
            transform: scale(1.1);
        }

        /* Image Preview */
        .image-preview {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid rgba(212, 175, 55, 0.2);
        }

        /* Status Badges */
        .badge-status {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.75rem;
        }

        .badge-active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .badge-inactive {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }

        /* Search and Filter */
        .search-section {
            background: rgba(255, 255, 255, 0.9);
            padding: 1.5rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            border: 2px solid rgba(212, 175, 55, 0.1);
        }

        .search-input {
            position: relative;
        }

        .search-input i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            z-index: 5;
        }

        .search-input input {
            padding-left: 3rem;
        }

        /* Modal Styling */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-gold), var(--secondary-gold));
            color: var(--dark-bg);
            border-radius: 20px 20px 0 0;
            border: none;
            padding: 1.5rem 2rem;
        }

        .modal-title {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.3rem;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            border: none;
            padding: 1rem 2rem 2rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-container {
                padding: 1rem;
            }

            .table-responsive {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<?php
include 'db_connect.php';
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    // include 'db.php'; // your DB connection

    // Required fields
    $required_fields = ['product_name', 'sku', 'category', 'price', 'quantity'];
    $errors = [];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
        }
    }

    // Handle image upload
    $imagePath = null;
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . "/uploads/products/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileExtension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . "." . $fileExtension;
        $targetFile = $uploadDir . $fileName;

        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array(strtolower($fileExtension), $allowedTypes)) {
            $errors[] = "Invalid image format";
        } elseif ($_FILES['product_image']['size'] > 5 * 1024 * 1024) {
            $errors[] = "Image too large";
        } elseif (!move_uploaded_file($_FILES['product_image']['tmp_name'], $targetFile)) {
            $errors[] = "Failed to upload image";
        } else {
            $imagePath = "uploads/products/" . $fileName;
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO products (product_name, sku, category, price, quantity, product_image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdis", $_POST['product_name'], $_POST['sku'], $_POST['category'], $_POST['price'], $_POST['quantity'], $imagePath);

        if ($stmt->execute()) {
            $message = "✨ Product '<strong>" . htmlspecialchars($_POST['product_name']) . "</strong>' added successfully! ✨";
            $message_type = 'success';
        } else {
            if ($conn->errno === 1062) $message = "❌ SKU already exists!";
            else $message = "❌ Database error: " . $stmt->error;
            $message_type = 'error';
        }

        $stmt->close();
        $conn->close();
    } else {
        $message = "❌ " . implode("<br>", $errors);
        $message_type = 'error';
    }
}
?>
<?php
// include 'db_connect.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $categoryName = $_POST['category_name'] ?? '';
    $status = $_POST['status'] ?? 'inactive';
    $description = $_POST['description'] ?? '';

    // Function to upload file
    function uploadFile($inputName)
    {
        if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === 0) {
            $uploadDir = "uploads/categories/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = basename($_FILES[$inputName]['name']);
            $targetFile = $uploadDir . time() . '_' . $fileName;

            if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $targetFile)) {
                return $targetFile;
            }
        }
        return null;
    }

    // Upload category image
    $categoryImage = uploadFile('category_image');

    // Check if category already exists
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE category_name = ?");
    $checkStmt->bind_param("s", $categoryName);
    $checkStmt->execute();
    $checkStmt->bind_result($count);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($count > 0) {
        echo "<script>
            alert('This category already exists. Category not added.');
            window.location.href = 'index.php?page=add_category';
        </script>";
        exit;
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO categories (category_name, status, description, category_image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $categoryName, $status, $description, $categoryImage);

    if ($stmt->execute()) {
        echo "<script>
            alert('Category added successfully!');
            window.location.href = 'index.php?page=add_category';
        </script>";
    } else {
        echo "<script>
            alert('Error: " . addslashes($stmt->error) . "');
            window.location.href = 'index.php?page=add_category';
        </script>";
    }

    $stmt->close();
}
?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<body>
    <div class="page-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title">Inventory Management</h1>
                    <?php
                    echo "Current PHP temp dir test: ";

                    echo "Current PHP temp dir: " . sys_get_temp_dir();
                    ?>

                    <p class="page-subtitle">Manage your fashion categories and products</p>
                </div>
                <div>
                    <button class="btn btn-outline-gold me-2" onclick="exportData()">
                        <i class="fas fa-download me-2"></i>Export
                    </button>
                    <button class="btn btn-gold" onclick="showImportModal()">
                        <i class="fas fa-upload me-2"></i>Import
                    </button>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs" id="managementTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab">
                    <i class="fas fa-tags me-2"></i>Categories
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab">
                    <i class="fas fa-tshirt me-2"></i>Products
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="managementTabContent">
            <!-- Categories Tab -->
            <div class="tab-pane fade show active" id="categories" role="tabpanel">
                <div class="management-card">
                    <!-- Categories Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="mb-0" style="font-family: 'Playfair Display', serif; color: var(--text-dark);">
                            <i class="fas fa-tags me-2 text-warning"></i>Fashion Categories
                        </h3>
                        <!-- <button class="btn btn-gold" onclick="showAddCategoryModal()">
                            <i class="fas fa-plus me-2"></i>Add Category
                        </button> -->
                        <!-- ANGELIQUE
                        addCategoryModal -->
                        <button class="btn btn-gold demo-button" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus me-2"></i>Add Category
                        </button>
                    </div>

                    <!-- Search Section -->
                    <div class="search-section">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="search-input">
                                    <i class="fas fa-search"></i>
                                    <input type="text" class="form-control" placeholder="Search categories..." id="categorySearch">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" id="categoryFilter">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Categories Table -->
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Category Name</th>
                                        <th>Description</th>
                                        <th>Products Count</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="categoriesTableBody">
                                    <tr>
                                        <td><img src="https://via.placeholder.com/60x60/d4af37/ffffff?text=👕" class="image-preview" alt="Men's Clothing"></td>
                                        <td><strong>Men's Clothing</strong></td>
                                        <td>Stylish clothing for modern men</td>
                                        <td><span class="badge bg-primary">45</span></td>
                                        <td><span class="badge-status badge-active">Active</span></td>
                                        <td>
                                            <button class="btn btn-action btn-edit" onclick="editCategory(1)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-action btn-delete" onclick="deleteCategory(1)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><img src="https://via.placeholder.com/60x60/d4af37/ffffff?text=👗" class="image-preview" alt="Women's Clothing"></td>
                                        <td><strong>Women's Clothing</strong></td>
                                        <td>Elegant fashion for women</td>
                                        <td><span class="badge bg-primary">67</span></td>
                                        <td><span class="badge-status badge-active">Active</span></td>
                                        <td>
                                            <button class="btn btn-action btn-edit" onclick="editCategory(2)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-action btn-delete" onclick="deleteCategory(2)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><img src="https://via.placeholder.com/60x60/d4af37/ffffff?text=👟" class="image-preview" alt="Footwear"></td>
                                        <td><strong>Footwear</strong></td>
                                        <td>Premium shoes and accessories</td>
                                        <td><span class="badge bg-primary">32</span></td>
                                        <td><span class="badge-status badge-active">Active</span></td>
                                        <td>
                                            <button class="btn btn-action btn-edit" onclick="editCategory(3)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-action btn-delete" onclick="deleteCategory(3)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><img src="https://via.placeholder.com/60x60/d4af37/ffffff?text=👜" class="image-preview" alt="Accessories"></td>
                                        <td><strong>Accessories</strong></td>
                                        <td>Bags, belts, and luxury accessories</td>
                                        <td><span class="badge bg-primary">28</span></td>
                                        <td><span class="badge-status badge-inactive">Inactive</span></td>
                                        <td>
                                            <button class="btn btn-action btn-edit" onclick="editCategory(4)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-action btn-delete" onclick="deleteCategory(4)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Tab -->
            <div class="tab-pane fade" id="products" role="tabpanel">
                <div class="management-card">
                    <!-- Products Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="mb-0" style="font-family: 'Playfair Display', serif; color: var(--text-dark);">
                            <i class="fas fa-tshirt me-2 text-primary"></i>Fashion Products
                        </h3>
                        <!-- <button class="btn btn-gold" onclick="showAddProductModal()">
                            <i class="fas fa-plus me-2"></i>Add Product
                        </button> -->
                        <button class="btn btn-gold demo-button" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="fas fa-plus me-2"></i>Add New Product
                        </button>
                    </div>

                    <!-- Search Section -->
                    <div class="search-section">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="search-input">
                                    <i class="fas fa-search"></i>
                                    <input type="text" class="form-control" placeholder="Search products..." id="productSearch">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="categoryFilterProducts">
                                    <option value="">All Categories</option>
                                    <option value="mens">Men's Clothing</option>
                                    <option value="womens">Women's Clothing</option>
                                    <option value="footwear">Footwear</option>
                                    <option value="accessories">Accessories</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="stockFilter">
                                    <option value="">All Stock Levels</option>
                                    <option value="in-stock">In Stock</option>
                                    <option value="low-stock">Low Stock</option>
                                    <option value="out-of-stock">Out of Stock</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Products Table -->
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Product Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="productsTableBody">
                                    <tr>
                                        <td><img src="https://via.placeholder.com/60x60/d4af37/ffffff?text=👔" class="image-preview" alt="Designer Shirt"></td>
                                        <td>
                                            <strong>Premium Cotton Shirt</strong><br>
                                            <small class="text-muted">SKU: PS001</small>
                                        </td>
                                        <td>Men's Clothing</td>
                                        <td><strong>$89.99</strong></td>
                                        <td>
                                            <span class="badge bg-success">25</span>
                                            <small class="text-muted d-block">In Stock</small>
                                        </td>
                                        <td><span class="badge-status badge-active">Active</span></td>
                                        <td>
                                            <button class="btn btn-action btn-edit" onclick="editProduct(1)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-action btn-delete" onclick="deleteProduct(1)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><img src="https://via.placeholder.com/60x60/d4af37/ffffff?text=👗" class="image-preview" alt="Elegant Dress"></td>
                                        <td>
                                            <strong>Silk Evening Dress</strong><br>
                                            <small class="text-muted">SKU: SED002</small>
                                        </td>
                                        <td>Women's Clothing</td>
                                        <td><strong>$149.99</strong></td>
                                        <td>
                                            <span class="badge bg-warning text-dark">5</span>
                                            <small class="text-muted d-block">Low Stock</small>
                                        </td>
                                        <td><span class="badge-status badge-active">Active</span></td>
                                        <td>
                                            <button class="btn btn-action btn-edit" onclick="editProduct(2)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-action btn-delete" onclick="deleteProduct(2)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><img src="https://via.placeholder.com/60x60/d4af37/ffffff?text=👟" class="image-preview" alt="Luxury Sneakers"></td>
                                        <td>
                                            <strong>Designer Sneakers</strong><br>
                                            <small class="text-muted">SKU: DS003</small>
                                        </td>
                                        <td>Footwear</td>
                                        <td><strong>$199.99</strong></td>
                                        <td>
                                            <span class="badge bg-danger">0</span>
                                            <small class="text-muted d-block">Out of Stock</small>
                                        </td>
                                        <td><span class="badge-status badge-inactive">Inactive</span></td>
                                        <td>
                                            <button class="btn btn-action btn-edit" onclick="editProduct(3)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-action btn-delete" onclick="deleteProduct(3)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item">
                                <a class="page-link" href="#" style="color: var(--primary-gold); border-color: var(--primary-gold);">Previous</a>
                            </li>
                            <li class="page-item active">
                                <a class="page-link" href="#" style="background: var(--primary-gold); border-color: var(--primary-gold);">1</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="#" style="color: var(--primary-gold); border-color: var(--primary-gold);">2</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="#" style="color: var(--primary-gold); border-color: var(--primary-gold);">3</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="#" style="color: var(--primary-gold); border-color: var(--primary-gold);">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <!-- <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Add New Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                   <form id="addCategoryForm" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Category Name</label>
                                    <input type="text" class="form-control" name="category_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category Image</label>
                            <input type="file" class="form-control" name="category_image" accept="image/*">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-gold" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-gold" onclick="saveCategory()">
                        <i class="fas fa-save me-2"></i>Save Category
                    </button>
                </div>
            </div>
        </div>
    </div> -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add New Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category Name</label>
                                <input type="text" class="form-control" name="category_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category Image</label>
                            <input type="file" class="form-control" name="category_image" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-gold" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-gold" name="save_category">
                            <i class="fas fa-save me-2"></i>Save Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Product Modal
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Add New Product
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addProductForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Product Name</label>
                                    <input type="text" class="form-control" name="product_ -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Add New Product
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Progress Indicator -->
                    <div class="progress-indicator">
                        <div class="progress-step active">
                            <span>Basic Info</span>
                        </div>
                        <div class="progress-step">
                            <span>Pricing & Stock</span>
                        </div>
                        <div class="progress-step">
                            <span>Details</span>
                        </div>
                    </div>

                    <!-- <form id="addProductForm"> -->
                    <form id="addProductForm" action="" method="POST" enctype="multipart/form-data">
                        
                        <!-- Basic Information Section -->
                        <div class="form-section">
                            <h6><i class="fas fa-info-circle me-2"></i>Basic Information</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Product Name <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="product_name" id="product_name" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">SKU <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="sku" id="sku" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Category <span class="required">*</span></label>
                                        <select class="form-select" name="category" id="category" required>
                                            <option value="">Select Category</option>
                                            <option value="mens">Men's Clothing</option>
                                            <option value="womens">Women's Clothing</option>
                                            <option value="footwear">Footwear</option>
                                            <option value="accessories">Accessories</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Brand</label>
                                        <input type="text" class="form-control" name="brand" id="brand" placeholder="e.g., Nike, Zara, Gucci">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" id="description" rows="4" placeholder="Detailed product description..."></textarea>
                            </div>
                        </div>

                        <!-- Pricing & Inventory Section -->
                        <div class="form-section">
                            <h6><i class="fas fa-dollar-sign me-2"></i>Pricing & Inventory</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Regular Price <span class="required">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" name="price" id="price" step="0.01" min="0" required>
                                        </div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Sale Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" name="sale_price" id="sale_price" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Cost Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" name="cost_price" id="cost_price" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Stock Quantity <span class="required">*</span></label>
                                        <input type="number" class="form-control" name="quantity" id="quantity" min="0" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Low Stock Alert</label>
                                        <input type="number" class="form-control" name="min_stock" id="min_stock" min="0" placeholder="Minimum quantity">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status" id="status">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="draft">Draft</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Product Details Section -->
                        <div class="form-section">
                            <h6><i class="fas fa-cogs me-2"></i>Product Details</h6>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Available Sizes</label>
                                        <div class="size-selection">
                                            <div class="size-option" data-size="XS">XS</div>
                                            <div class="size-option" data-size="S">S</div>
                                            <div class="size-option" data-size="M">M</div>
                                            <div class="size-option" data-size="L">L</div>
                                            <div class="size-option" data-size="XL">XL</div>
                                            <div class="size-option" data-size="XXL">XXL</div>
                                        </div>
                                        <input type="hidden" name="sizes" id="sizes">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Available Colors</label>
                                        <div class="color-selection">
                                            <div class="color-option" data-color="Black" style="background-color: #000000;"></div>
                                            <div class="color-option" data-color="White" style="background-color: #FFFFFF; border: 1px solid #ccc;"></div>
                                            <div class="color-option" data-color="Red" style="background-color: #EF4444;"></div>
                                            <div class="color-option" data-color="Blue" style="background-color: #3B82F6;"></div>
                                            <div class="color-option" data-color="Green" style="background-color: #10B981;"></div>
                                            <div class="color-option" data-color="Pink" style="background-color: #EC4899;"></div>
                                            <div class="color-option" data-color="Gold" style="background-color: #D4AF37;"></div>
                                        </div>
                                        <input type="hidden" name="colors" id="colors">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Product Image</label>
                                        <input type="file" class="form-control" name="product_image" id="product_image" accept="image/*">
                                        <img id="imagePreview" class="image-preview" alt="Image preview">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tags</label>
                                        <input type="text" class="form-control" name="tags" id="tags" placeholder="e.g., trendy, summer, casual">
                                        <small class="text-muted">Separate tags with commas</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Weight (kg)</label>
                                        <input type="number" class="form-control" name="weight" id="weight" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Material</label>
                                        <input type="text" class="form-control" name="material" id="material" placeholder="e.g., Cotton, Silk, Leather">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Season</label>
                                        <select class="form-select" name="season" id="season">
                                            <option value="">Select Season</option>
                                            <option value="spring">Spring</option>
                                            <option value="summer">Summer</option>
                                            <option value="fall">Fall</option>
                                            <option value="winter">Winter</option>
                                            <option value="all-season">All Season</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured">
                                            <label class="form-check-label" for="is_featured">
                                                <i class="fas fa-star text-warning me-1"></i>Featured Product
                                            </label>
                                        </div>

                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="track_inventory" id="track_inventory" checked>
                                            <label class="form-check-label" for="track_inventory">
                                                <i class="fas fa-boxes text-info me-1"></i>Track Inventory
                                            </label>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="allow_backorder" id="allow_backorder">
                                            <label class="form-check-label" for="allow_backorder">
                                                <i class="fas fa-clock text-primary me-1"></i>Allow Backorder
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-gold" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <!-- <button type="button" class="btn btn-gold" id="saveProduct">
                        <i class="fas fa-save me-2"></i>Save Product
                    </button> -->

                    <button type="submit" class="btn btn-gold" name="save_product">
    <i class="fas fa-save me-2"></i>Save Product
</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview functionality
        document.getElementById('product_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagePreview');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });

        // Auto-generate SKU based on product name
        document.getElementById('product_name').addEventListener('input', function(e) {
            const productName = e.target.value;
            if (productName && document.getElementById('sku').value === '') {
                const sku = productName
                    .toUpperCase()
                    .replace(/[^A-Z0-9]/g, '')
                    .substring(0, 6) + '-' + Math.floor(Math.random() * 1000).toString().padStart(3, '0');
                document.getElementById('sku').value = sku;
            }
        });

        // Size selection functionality
        document.querySelectorAll('.size-option').forEach(option => {
            option.addEventListener('click', function() {
                this.classList.toggle('selected');
                updateSizes();
            });
        });

        function updateSizes() {
            const selectedSizes = Array.from(document.querySelectorAll('.size-option.selected'))
                .map(option => option.dataset.size);
            document.getElementById('sizes').value = selectedSizes.join(',');
        }

        // Color selection functionality
        document.querySelectorAll('.color-option').forEach(option => {
            option.addEventListener('click', function() {
                this.classList.toggle('selected');
                updateColors();
            });
        });

        function updateColors() {
            const selectedColors = Array.from(document.querySelectorAll('.color-option.selected'))
                .map(option => option.dataset.color);
            document.getElementById('colors').value = selectedColors.join(',');
        }

        // Form validation
        function validateForm() {
            const requiredFields = ['product_name', 'sku', 'category', 'price', 'quantity'];
            let isValid = true;

            requiredFields.forEach(field => {
                const input = document.getElementById(field);
                const value = input.value.trim();

                if (!value) {
                    input.classList.add('is-invalid');
                    const feedback = input.parentNode.querySelector('.invalid-feedback') ||
                        input.nextElementSibling;
                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                        feedback.textContent = 'This field is required';
                    }
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            return isValid;
        }

        // Calculate profit margin
        function calculateProfitMargin() {
            const price = parseFloat(document.getElementById('price').value) || 0;
            const costPrice = parseFloat(document.getElementById('cost_price').value) || 0;

            if (price > 0 && costPrice > 0) {
                const margin = ((price - costPrice) / price * 100).toFixed(2);
                console.log(`Profit margin: ${margin}%`);

                // You could display this in the UI
                // For now, just logging to console
            }
        }

        document.getElementById('price').addEventListener('input', calculateProfitMargin);
        document.getElementById('cost_price').addEventListener('input', calculateProfitMargin);

        // Clear validation on input
        document.querySelectorAll('.form-control, .form-select').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        });


        // Reset form function
        function resetForm() {
            document.getElementById('addProductForm').reset();
            document.getElementById('imagePreview').style.display = 'none';
            document.querySelectorAll('.size-option.selected').forEach(option => {
                option.classList.remove('selected');
            });
            document.querySelectorAll('.color-option.selected').forEach(option => {
                option.classList.remove('selected');
            });
            document.querySelectorAll('.is-invalid').forEach(input => {
                input.classList.remove('is-invalid');
            });
            document.getElementById('sizes').value = '';
            document.getElementById('colors').value = '';
        }

        // Reset form when modal is closed
        document.getElementById('addProductModal').addEventListener('hidden.bs.modal', function() {
            resetForm();
        });

        // Progress indicator simulation (you could expand this for multi-step form)
        document.querySelectorAll('.form-control, .form-select').forEach(input => {
            input.addEventListener('input', updateProgress);
        });

        function updateProgress() {
            const requiredFields = ['product_name', 'sku', 'category'];
            const pricingFields = ['price', 'quantity'];

            const basicComplete = requiredFields.every(field =>
                document.getElementById(field).value.trim() !== ''
            );
            const pricingComplete = pricingFields.every(field =>
                document.getElementById(field).value.trim() !== ''
            );

            const steps = document.querySelectorAll('.progress-step');

            // Reset all steps
            steps.forEach(step => step.classList.remove('active'));

            // Activate completed steps
            steps[0].classList.add('active'); // Always show first step as active
            if (basicComplete) {
                steps[1].classList.add('active');
            }
            // if (basicComplete && pricingComplete) {
            //     steps[2].classList.add('active');
        }

        function saveCategory() {
            const form = document.getElementById("addCategoryForm");
            const formData = new FormData(form); // includes all fields + file

            fetch("save_category.php", { // <-- replace with your PHP filename
                    method: "POST",
                    body: formData
                    // ⚠️ do NOT set Content-Type manually, FormData does it automatically
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert("Category saved successfully!");
                        form.reset();
                        // Close the modal if you want:
                        const modal = bootstrap.Modal.getInstance(document.getElementById("addCategoryModal"));
                        modal.hide();
                    } else {
                        alert("Error: " + data.message);
                    }
                })
                .catch(err => console.error("Request failed:", err));
        }
        // Product form handling JavaScript
        document.addEventListener('DOMContentLoaded', function() {

            // Size selection handling
            const sizeOptions = document.querySelectorAll('.size-option');
            const sizesInput = document.getElementById('sizes');
            let selectedSizes = [];

            sizeOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const size = this.dataset.size;

                    if (this.classList.contains('selected')) {
                        this.classList.remove('selected');
                        selectedSizes = selectedSizes.filter(s => s !== size);
                    } else {
                        this.classList.add('selected');
                        selectedSizes.push(size);
                    }

                    sizesInput.value = selectedSizes.join(',');
                });
            });

            // Color selection handling
            const colorOptions = document.querySelectorAll('.color-option');
            const colorsInput = document.getElementById('colors');
            let selectedColors = [];

            colorOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const color = this.dataset.color;

                    if (this.classList.contains('selected')) {
                        this.classList.remove('selected');
                        selectedColors = selectedColors.filter(c => c !== color);
                    } else {
                        this.classList.add('selected');
                        selectedColors.push(color);
                    }

                    colorsInput.value = selectedColors.join(',');
                });
            });

            // Image preview
            const imageInput = document.getElementById('product_image');
            const imagePreview = document.getElementById('imagePreview');

            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                    }
                    reader.readAsDataURL(file);
                }
            });

            // Form submission
            const form = document.getElementById('addProductForm');
            const saveButton = document.getElementById('saveProduct');

            saveButton.addEventListener('click', function() {
                // Basic form validation
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                // Show loading state
                saveButton.disabled = true;
                saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';

                // Create FormData object
                const formData = new FormData(form);

                // Submit form via AJAX
                fetch('save_product.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Success notification
                            showNotification('Product saved successfully!', 'success');

                            // Close modal
                            const modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
                            modal.hide();

                            // Reset form
                            form.reset();
                            selectedSizes = [];
                            selectedColors = [];
                            sizeOptions.forEach(opt => opt.classList.remove('selected'));
                            colorOptions.forEach(opt => opt.classList.remove('selected'));
                            imagePreview.style.display = 'none';

                            // Refresh product list if function exists
                            if (typeof refreshProductList === 'function') {
                                refreshProductList();
                            }

                        } else {
                            // Error notification
                            showNotification(data.error || 'Failed to save product', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred while saving the product', 'error');
                    })
                    .finally(() => {
                        // Reset button state
                        saveButton.disabled = false;
                        saveButton.innerHTML = '<i class="fas fa-save me-2"></i>Save Product';
                    });
            });

            // Notification function
            function showNotification(message, type) {
                // You can implement your notification system here
                // This is a simple alert, but you can use toast notifications, etc.
                if (type === 'success') {
                    alert('✅ ' + message);
                } else {
                    alert('❌ ' + message);
                }
            }

            // SKU generation based on product name
            const productNameInput = document.getElementById('product_name');
            const skuInput = document.getElementById('sku');

            productNameInput.addEventListener('blur', function() {
                if (this.value && !skuInput.value) {
                    // Generate SKU from product name
                    const sku = this.value
                        .toUpperCase()
                        .replace(/[^A-Z0-9]/g, '')
                        .substring(0, 8) +
                        Math.floor(Math.random() * 1000).toString().padStart(3, '0');
                    skuInput.value = sku;
                }
            });

            // Price validation
            const priceInput = document.getElementById('price');
            const salePriceInput = document.getElementById('sale_price');

            salePriceInput.addEventListener('change', function() {
                const regularPrice = parseFloat(priceInput.value) || 0;
                const salePrice = parseFloat(this.value) || 0;

                if (salePrice > regularPrice && regularPrice > 0) {
                    alert('Sale price cannot be higher than regular price');
                    this.value = '';
                }
            });
        });
    </script>