<?php
// --- DEV ERROR SETTINGS ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
// make mysqli throw exceptions so errors surface
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

include 'db_connect.php';

$message = '';
$message_type = '';

// ============================
// Handle Category Form Submission
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $categoryName = trim($_POST['category_name'] ?? '');
    $status       = $_POST['status'] ?? 'inactive';
    $description  = trim($_POST['description'] ?? '');

    $errors = [];

    if (empty($categoryName)) {
        $errors[] = "Category name is required";
    }

    // Handle image upload
    $categoryImage = null;
    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . "/uploads/categories/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileExtension = pathinfo($_FILES['category_image']['name'], PATHINFO_EXTENSION);
        $fileName      = uniqid() . "." . $fileExtension;
        $targetFile    = $uploadDir . $fileName;

        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array(strtolower($fileExtension), $allowedTypes)) {
            $errors[] = "Invalid image format";
        } elseif ($_FILES['category_image']['size'] > 5 * 1024 * 1024) {
            $errors[] = "Image too large (max 5MB)";
        } elseif (!move_uploaded_file($_FILES['category_image']['tmp_name'], $targetFile)) {
            $errors[] = "Failed to upload image";
        } else {
            $categoryImage = "uploads/categories/" . $fileName;
        }
    }

    if (empty($errors)) {
        // Check if category already exists
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE category_name = ?");
        $checkStmt->bind_param("s", $categoryName);
        $checkStmt->execute();
        $checkStmt->bind_result($count);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($count > 0) {
            $message = "Category already exists!";
            $message_type = 'error';
        } else {
            // Insert new category
            $stmt = $conn->prepare("INSERT INTO categories (category_name, status, description, category_image) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $categoryName, $status, $description, $categoryImage);
            if ($stmt->execute()) {
                $message = "Category '<strong>" . htmlspecialchars($categoryName) . "</strong>' added successfully!";
                $message_type = 'success';
            } else {
                $message = "Database error: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    } else {
        $message = implode("<br>", $errors);
        $message_type = 'error';
    }
}

// ============================
// Handle Product Form Submission
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $required_fields = ['product_name', 'sku', 'category', 'price', 'quantity'];
    $errors = [];

    foreach ($required_fields as $field) {
        if (empty(trim($_POST[$field] ?? ''))) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
        }
    }

    // Validate numeric fields
    if (!empty($_POST['price']) && !is_numeric($_POST['price'])) {
        $errors[] = "Price must be a valid number";
    }
    if (!empty($_POST['quantity']) && !is_numeric($_POST['quantity'])) {
        $errors[] = "Quantity must be a valid number";
    }

    // Handle image upload
    $imagePath = null;
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . "/uploads/products/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileExtension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $fileName      = uniqid() . "." . $fileExtension;
        $targetFile    = $uploadDir . $fileName;

        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array(strtolower($fileExtension), $allowedTypes)) {
            $errors[] = "Invalid image format";
        } elseif ($_FILES['product_image']['size'] > 5 * 1024 * 1024) {
            $errors[] = "Image too large (max 5MB)";
        } elseif (!move_uploaded_file($_FILES['product_image']['tmp_name'], $targetFile)) {
            $errors[] = "Failed to upload image";
        } else {
            $imagePath = "uploads/products/" . $fileName;
        }
    }

    if (empty($errors)) {
        // Check if SKU already exists
        $sku_check = $_POST['sku'];
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE sku = ?");
        $checkStmt->bind_param("s", $sku_check);
        $checkStmt->execute();
        $checkStmt->bind_result($count);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($count > 0) {
            $message = "SKU already exists!";
            $message_type = 'error';
        } else {
            // ----- Collect all values into VARIABLES (bind_param needs references) -----
            $product_name   = $_POST['product_name'];
            $sku            = $_POST['sku'];
            $category       = $_POST['category']; // storing name as VARCHAR

            $price          = $_POST['price'] !== '' ? (float)$_POST['price'] : 0.0;
            $sale_price_var = $_POST['sale_price'] !== '' ? (float)$_POST['sale_price'] : null;
            $cost_price_var = $_POST['cost_price'] !== '' ? (float)$_POST['cost_price'] : null;

            $quantity       = $_POST['quantity'] !== '' ? (int)$_POST['quantity'] : 0;
            $min_stock_var  = $_POST['min_stock'] !== '' ? (int)$_POST['min_stock'] : null;

            $imagePath_var  = $imagePath;
            $brand_var      = trim($_POST['brand'] ?? '');
            $description_var= trim($_POST['description'] ?? '');

            // ---- CSV -> JSON (or NULL) for JSON columns ----
            $sizes_raw  = trim($_POST['sizes']  ?? '');
            $colors_raw = trim($_POST['colors'] ?? '');

            $csvToJsonOrNull = function ($csv) {
                if ($csv === '' || $csv === null) {
                    return null; // store SQL NULL when nothing selected
                }
                $arr = array_values(
                    array_filter(
                        array_map('trim', explode(',', $csv)),
                        fn($v) => $v !== ''
                    )
                );
                if (empty($arr)) {
                    return null; // still nothing
                }
                $json = json_encode($arr, JSON_UNESCAPED_UNICODE);
                if ($json === false) {
                    return null; // fallback to NULL on encoding issue
                }
                return $json; // e.g. ["S","M","L"]
            };

            $sizes_json  = $csvToJsonOrNull($sizes_raw);
            $colors_json = $csvToJsonOrNull($colors_raw);

            $tags_var       = trim($_POST['tags'] ?? '');

            $weight_var     = $_POST['weight'] !== '' ? (float)$_POST['weight'] : null;
            $material_var   = trim($_POST['material'] ?? '');
            $season_var     = $_POST['season'] ?? '';
            $status_var     = $_POST['status'] ?? 'active';

            $is_featured_var     = isset($_POST['is_featured']) ? 1 : 0;
            $track_inventory_var = isset($_POST['track_inventory']) ? 1 : 0;
            $allow_backorder_var = isset($_POST['allow_backorder']) ? 1 : 0;

            // ----- Prepare & Bind (types match 21 placeholders) -----
            $sql = "INSERT INTO products (
                product_name, sku, category, price, sale_price, cost_price, quantity, min_stock,
                product_image, brand, description, sizes, colors, tags, weight, material, season,
                status, is_featured, track_inventory, allow_backorder, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sssdddiisssssdssssiii",
                $product_name,          // s
                $sku,                   // s
                $category,              // s (name string)
                $price,                 // d
                $sale_price_var,        // d
                $cost_price_var,        // d
                $quantity,              // i
                $min_stock_var,         // i
                $imagePath_var,         // s
                $brand_var,             // s
                $description_var,       // s
                $sizes_json,            // s  (JSON string or NULL)
                $colors_json,           // s  (JSON string or NULL)
                $tags_var,              // s
                $weight_var,            // d
                $material_var,          // s
                $season_var,            // s
                $status_var,            // s
                $is_featured_var,       // i
                $track_inventory_var,   // i
                $allow_backorder_var    // i
            );

            if ($stmt->execute()) {
                $message = "Product '<strong>" . htmlspecialchars($product_name) . "</strong>' added successfully!";
                $message_type = 'success';
            } else {
                $message = "Database error: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    } else {
        $message = implode("<br>", $errors);
        $message_type = 'error';
    }
}

// ============================
// Fetch data for forms/tables
// ============================
$categories = [];
$res = $conn->query("SELECT id, category_name FROM categories WHERE status = 'active' ORDER BY category_name");
if ($res) { while ($row = $res->fetch_assoc()) { $categories[] = $row; } }

$display_categories = [];
$res = $conn->query("SELECT * FROM categories ORDER BY created_at DESC");
if ($res) { while ($row = $res->fetch_assoc()) { $display_categories[] = $row; } }

$display_products = [];
$res = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
if ($res) { while ($row = $res->fetch_assoc()) { $display_products[] = $row; } }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Categories & Products Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1b2e 25%, #1a1a1a 50%, #2e2420 75%, #1a1a1a 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        .page-container { padding: 2rem; max-width: 1400px; margin: 0 auto; }

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
            position: absolute; top: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, var(--primary-gold), var(--secondary-gold), var(--primary-gold));
            background-size: 200% 100%;
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { background-position: -100% 0; }
            50% { background-position: 100% 0; }
        }

        .page-title { font-family: 'Playfair Display', serif; font-size: 2.2rem; color: var(--text-dark); font-weight: 700; margin-bottom: 0.5rem; letter-spacing: 1px; }
        .page-subtitle { color: var(--text-muted); font-size: 1rem; margin-bottom: 0; }

        .nav-tabs { border: none; margin-bottom: 2rem; }
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

        .management-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            border: 2px solid rgba(212, 175, 55, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .form-label { color: var(--text-dark); font-weight: 600; margin-bottom: 0.5rem; font-size: 0.95rem; }

        .form-control, .form-select {
            background: rgba(248, 248, 248, 0.9);
            border: 2px solid var(--border-light);
            border-radius: 12px;
            padding: 0.8rem 1rem;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }
        .form-control:focus, .form-select:focus {
            background: white;
            border-color: var(--primary-gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
            outline: none;
        }

        .btn-gold {
            background: linear-gradient(135deg, var(--primary-gold), var(--secondary-gold));
            color: var(--dark-bg);
            border: none; padding: 0.8rem 1.5rem; border-radius: 12px; font-weight: 600;
            transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .btn-gold:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4); color: var(--dark-bg); }

        .btn-outline-gold {
            background: transparent; color: var(--primary-gold); border: 2px solid var(--primary-gold);
            padding: 0.6rem 1.2rem; border-radius: 10px; font-weight: 500; transition: all 0.3s ease;
        }
        .btn-outline-gold:hover { background: var(--primary-gold); color: white; }

        .table-container { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid rgba(212,175,55,0.1); }
        .table { margin-bottom: 0; }
        .table thead th {
            background: linear-gradient(135deg, var(--primary-gold), var(--secondary-gold));
            color: var(--dark-bg); font-weight: 600; border: none; padding: 1rem; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.85rem;
        }
        .table tbody tr { transition: all 0.2s ease; }
        .table tbody tr:hover { background: rgba(212, 175, 55, 0.05); }
        .table tbody td { padding: 1rem; border-color: rgba(212, 175, 55, 0.1); vertical-align: middle; }

        .btn-action { padding: 0.4rem 0.8rem; margin: 0 0.2rem; border-radius: 8px; border: none; font-size: 0.8rem; transition: all 0.2s ease; }
        .btn-edit { background: var(--warning-color); color: white; }
        .btn-delete { background: var(--danger-color); color: white; }
        .btn-action:hover { transform: scale(1.1); }

        .image-preview { width: 60px; height: 60px; border-radius: 10px; object-fit: cover; border: 2px solid rgba(212,175,55,0.2); }

        .badge-status { padding: 0.4rem 0.8rem; border-radius: 20px; font-weight: 500; font-size: 0.75rem; }
        .badge-active { background: rgba(16,185,129,0.1); color: var(--success-color); border: 1px solid var(--success-color); }
        .badge-inactive { background: rgba(239,68,68,0.1); color: var(--danger-color); border: 1px solid var(--danger-color); }

        .search-section { background: rgba(255,255,255,0.9); padding: 1.5rem; border-radius: 16px; margin-bottom: 2rem; border: 2px solid rgba(212,175,55,0.1); }
        .search-input { position: relative; }
        .search-input i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); z-index: 5; }
        .search-input input { padding-left: 3rem; }

        .modal-content { border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .modal-header { background: linear-gradient(135deg, var(--primary-gold), var(--secondary-gold)); color: var(--dark-bg); border-radius: 20px 20px 0 0; border: none; padding: 1.5rem 2rem; }
        .modal-title { font-family: 'Playfair Display', serif; font-weight: 700; font-size: 1.3rem; }
        .modal-body { padding: 2rem; }
        .modal-footer { border: none; padding: 1rem 2rem 2rem; }

        .size-selection, .color-selection { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.5rem; }
        .size-option, .color-option { padding: 0.5rem 1rem; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; transition: all 0.2s ease; }
        .size-option { background: #f8f9fa; font-weight: 500; }
        .color-option { width: 40px; height: 40px; border-radius: 50%; border: 2px solid #ddd; }
        .size-option.selected { background: var(--primary-gold); color: white; border-color: var(--primary-gold); }
        .color-option.selected { border-color: var(--primary-gold); border-width: 3px; }

        .required { color: var(--danger-color); }

        .alert { border-radius: 12px; border: none; margin-bottom: 2rem; }
        .alert-success { background: rgba(16,185,129,0.1); color: var(--success-color); border: 1px solid var(--success-color); }
        .alert-danger  { background: rgba(239,68,68,0.1); color: var(--danger-color); border: 1px solid var(--danger-color); }

        @media (max-width: 768px) { .page-container { padding: 1rem; } .table-responsive { font-size: 0.85rem; } }
    </style>
</head>

<body>
    <div class="page-container">
        <?php if (!empty($message)): ?>
            <div class="alert <?= $message_type === 'success' ? 'alert-success' : 'alert-danger' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title">Inventory Management</h1>
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="mb-0" style="font-family: 'Playfair Display', serif; color: var(--text-dark);">
                            <i class="fas fa-tags me-2 text-warning"></i>Fashion Categories
                        </h3>
                        <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
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
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($display_categories as $category): ?>
                                    <tr>
                                        <td>
                                            <?php if ($category['category_image']): ?>
                                                <img src="<?= htmlspecialchars($category['category_image']) ?>" class="image-preview" alt="Category Image">
                                            <?php else: ?>
                                                <img src="https://via.placeholder.com/60x60/d4af37/ffffff?text=IMG" class="image-preview" alt="No Image">
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?= htmlspecialchars($category['category_name']) ?></strong></td>
                                        <td><?= htmlspecialchars($category['description']) ?></td>
                                        <td>
                                            <span class="badge-status <?= $category['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                                <?= ucfirst($category['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($category['created_at'])) ?></td>
                                        <td>
                                            <button class="btn btn-action btn-edit" onclick="editCategory(<?= $category['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-action btn-delete" onclick="deleteCategory(<?= $category['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Tab -->
            <div class="tab-pane fade" id="products" role="tabpanel">
                <div class="management-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="mb-0" style="font-family: 'Playfair Display', serif; color: var(--text-dark);">
                            <i class="fas fa-tshirt me-2 text-primary"></i>Fashion Products
                        </h3>
                        <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="fas fa-plus me-2"></i>Add Product
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
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= htmlspecialchars($category['category_name']) ?>"><?= htmlspecialchars($category['category_name']) ?></option>
                                    <?php endforeach; ?>
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
                                    <option value="draft">Draft</option>
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
                                <tbody>
                                    <?php foreach ($display_products as $product): ?>
                                    <tr>
                                        <td>
                                            <?php if ($product['product_image']): ?>
                                                <img src="<?= htmlspecialchars($product['product_image']) ?>" class="image-preview" alt="Product Image">
                                            <?php else: ?>
                                                <img src="https://via.placeholder.com/60x60/d4af37/ffffff?text=IMG" class="image-preview" alt="No Image">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($product['product_name']) ?></strong><br>
                                            <small class="text-muted">SKU: <?= htmlspecialchars($product['sku']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($product['category']) ?></td>
                                        <td>
                                            <strong>$<?= number_format($product['price'], 2) ?></strong>
                                            <?php if ($product['sale_price']): ?>
                                                <br><small class="text-muted">Sale: $<?= number_format($product['sale_price'], 2) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $stock_class = 'bg-success';
                                            $stock_text = 'In Stock';
                                            if ($product['quantity'] == 0) {
                                                $stock_class = 'bg-danger';
                                                $stock_text = 'Out of Stock';
                                            } elseif ($product['min_stock'] && $product['quantity'] <= $product['min_stock']) {
                                                $stock_class = 'bg-warning text-dark';
                                                $stock_text = 'Low Stock';
                                            }
                                            ?>
                                            <span class="badge <?= $stock_class ?>"><?= $product['quantity'] ?></span>
                                            <small class="text-muted d-block"><?= $stock_text ?></small>
                                        </td>
                                        <td>
                                            <span class="badge-status <?= $product['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                                <?= ucfirst($product['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-action btn-edit" onclick="editProduct(<?= $product['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-action btn-delete" onclick="deleteProduct(<?= $product['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
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
                                <label class="form-label">Category Name <span class="required">*</span></label>
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

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data" id="addProductForm">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-plus-circle me-2"></i>Add New Product
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Basic Information Section -->
                        <div class="form-section mb-4">
                            <h6><i class="fas fa-info-circle me-2"></i>Basic Information</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Product Name <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="product_name" id="product_name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">SKU <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="sku" id="sku" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Category <span class="required">*</span></label>
                                        <select class="form-select" name="category" id="category" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= htmlspecialchars($category['category_name']) ?>"><?= htmlspecialchars($category['category_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Brand</label>
                                        <input type="text" class="form-control" name="brand" placeholder="e.g., Nike, Zara, Gucci">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="4" placeholder="Detailed product description..."></textarea>
                            </div>
                        </div>

                        <!-- Pricing & Inventory Section -->
                        <div class="form-section mb-4">
                            <h6><i class="fas fa-dollar-sign me-2"></i>Pricing & Inventory</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Regular Price <span class="required">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Sale Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" name="sale_price" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Cost Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" name="cost_price" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Stock Quantity <span class="required">*</span></label>
                                        <input type="number" class="form-control" name="quantity" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Low Stock Alert</label>
                                        <input type="number" class="form-control" name="min_stock" min="0" placeholder="Minimum quantity">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status">
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
                                        <img id="imagePreview" class="image-preview mt-2" style="display: none;" alt="Image preview">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tags</label>
                                        <input type="text" class="form-control" name="tags" placeholder="e.g., trendy, summer, casual">
                                        <small class="text-muted">Separate tags with commas</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Weight (kg)</label>
                                        <input type="number" class="form-control" name="weight" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Material</label>
                                        <input type="text" class="form-control" name="material" placeholder="e.g., Cotton, Silk, Leather">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Season</label>
                                        <select class="form-select" name="season">
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
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-gold" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-gold" name="save_product">
                            <i class="fas fa-save me-2"></i>Save Product
                        </button>
                    </div>
                </form>
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
            const skuField = document.getElementById('sku');

            if (productName && skuField.value === '') {
                const sku = productName
                    .toUpperCase()
                    .replace(/[^A-Z0-9]/g, '')
                    .substring(0, 6) + '-' + Math.floor(Math.random() * 1000).toString().padStart(3, '0');
                skuField.value = sku;
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

        // Reset form when modal is closed
        document.getElementById('addProductModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('addProductForm').reset();
            document.getElementById('imagePreview').style.display = 'none';
            document.querySelectorAll('.size-option.selected').forEach(option => {
                option.classList.remove('selected');
            });
            document.querySelectorAll('.color-option.selected').forEach(option => {
                option.classList.remove('selected');
            });
            document.getElementById('sizes').value = '';
            document.getElementById('colors').value = '';
        });

        // Placeholder functions for edit and delete operations
        function editCategory(id) {
            alert('Edit category functionality would be implemented here for ID: ' + id);
        }

        function deleteCategory(id) {
            if (confirm('Are you sure you want to delete this category?')) {
                alert('Delete category functionality would be implemented here for ID: ' + id);
            }
        }

        function editProduct(id) {
            alert('Edit product functionality would be implemented here for ID: ' + id);
        }

        function deleteProduct(id) {
            if (confirm('Are you sure you want to delete this product?')) {
                alert('Delete product functionality would be implemented here for ID: ' + id);
            }
        }

        function exportData() {
            alert('Export functionality would be implemented here');
        }

        function showImportModal() {
            alert('Import functionality would be implemented here');
        }

        // Search functionality placeholders
        document.getElementById('categorySearch').addEventListener('input', function() {
            // Implement category search filter
        });

        document.getElementById('productSearch').addEventListener('input', function() {
            // Implement product search filter
        });
    </script>
</body>
</html>
