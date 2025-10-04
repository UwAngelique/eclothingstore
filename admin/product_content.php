<?php
// product_content.php - Complete functional version for dashboard integration
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connect.php';

$message = '';
$message_type = '';

// Handle success messages from redirects
if (isset($_GET['success'])) {
    switch($_GET['success']) {
        case 'category':
            $message = "Category added successfully!";
            $message_type = 'success';
            break;
        case 'product':
            $message = "Product added successfully!";
            $message_type = 'success';
            break;
    }
}

if (isset($_GET['error'])) {
    $message = urldecode($_GET['error']);
    $message_type = 'error';
}

/**
 * Ensure a directory exists and is writable
 */
function ensure_dir(string $dir, array &$errors): bool {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true)) {
            $errors[] = "Failed to create directory: $dir";
            return false;
        }
    }
    if (!is_writable($dir)) {
        @chmod($dir, 0777);
        if (!is_writable($dir)) {
            $errors[] = "Directory not writable: $dir";
            return false;
        }
    }
    return true;
}

/**
 * Image upload handler with strong validation and diagnostics
 */
function handle_upload(array $file = null, string $subfolder = 'products', array &$errors = []): ?string {
    if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $err = $file['error'];
    $errMap = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE from form.',
        UPLOAD_ERR_PARTIAL    => 'File partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder (upload_tmp_dir).',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk (permissions).',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the upload.',
    ];
    if ($err !== UPLOAD_ERR_OK) {
        $errors[] = "Upload error: " . ($errMap[$err] ?? "Unknown error ($err)");
        return null;
    }

    $base = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $subfolder . DIRECTORY_SEPARATOR;
    if (!ensure_dir($base, $errors)) return null;

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        $errors[] = "Invalid image type: {$mime}. Allowed: JPEG, PNG, GIF, WEBP";
        return null;
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        $errors[] = "Image too large (max 5MB)";
        return null;
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        $errors[] = "Security check failed: not an uploaded file.";
        return null;
    }

    $name = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $dest = $base . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        $errors[] = "Failed to move uploaded file. Check folder permissions.";
        return null;
    }
    @chmod($dest, 0644);

    return 'uploads/' . $subfolder . '/' . $name;
}

/**
 * Convert CSV string to JSON array string for JSON columns, or NULL
 */
function csv_to_json_or_null(?string $csv): ?string {
    $csv = trim((string)$csv);
    if ($csv === '') return null;
    $arr = array_values(array_filter(array_map('trim', explode(',', $csv)), fn($v)=>$v!==''));
    if (!$arr) return null;
    $json = json_encode($arr, JSON_UNESCAPED_UNICODE);
    return $json ?: null;
}

// Handle Category Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $categoryName = trim($_POST['category_name'] ?? '');
    $status       = $_POST['status'] ?? 'inactive';
    $description  = trim($_POST['description'] ?? '');

    $errors = [];
    if ($categoryName === '') $errors[] = "Category name is required";

    $categoryImage = handle_upload($_FILES['category_image'] ?? null, 'categories', $errors);

    if (!$errors) {
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE category_name = ?");
        $checkStmt->bind_param("s", $categoryName);
        $checkStmt->execute();
        $checkStmt->bind_result($count);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($count > 0) {
            // header("Location: " . $_SERVER['PHP_SELF'] . "?page=inventory&error=" . urlencode("Category already exists!"));
            header("Location: index.php?page=inventory&success=category");
            exit();
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (category_name, status, description, category_image) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $categoryName, $status, $description, $categoryImage);
            if ($stmt->execute()) {
                $stmt->close();
                // header("Location: " . $_SERVER['PHP_SELF'] . "?page=inventory&success=category");
                header("Location: index.php?page=inventory&success=category");
                exit();
            } else {
                $stmt->close();
                // header("Location: " . $_SERVER['PHP_SELF'] . "?page=inventory&error=" . urlencode("Database error occurred"));
                header("Location: index.php?page=inventory&error=" . urlencode("Database error occurred"));
                exit();
            }
        }
    } else {
        $errorMessage = implode(", ", $errors);
        // header("Location: " . $_SERVER['PHP_SELF'] . "?page=inventory&error=" . urlencode($errorMessage));
        header("Location: index.php?page=inventory&error=" . urlencode($errorMessage));
        exit();
    }
}

// Handle Product Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $required_fields = ['product_name', 'sku', 'category', 'price', 'quantity'];
    $errors = [];

    foreach ($required_fields as $field) {
        if (empty(trim($_POST[$field] ?? ''))) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
        }
    }
    if (($_POST['price'] ?? '') !== '' && !is_numeric($_POST['price']))     $errors[] = "Price must be a valid number";
    if (($_POST['quantity'] ?? '') !== '' && !is_numeric($_POST['quantity'])) $errors[] = "Quantity must be a valid number";

    $imagePath = handle_upload($_FILES['product_image'] ?? null, 'products', $errors);

    if (!$errors) {
        $sku_check = $_POST['sku'];
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE sku = ?");
        $checkStmt->bind_param("s", $sku_check);
        $checkStmt->execute();
        $checkStmt->bind_result($count);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($count > 0) {
            // header("Location: " . $_SERVER['PHP_SELF'] . "?page=inventory&error=" . urlencode("SKU already exists!"));
            header("Location: index.php?page=inventory&error=" . urlencode("SKU already exists!"));
            exit();
        } else {
            $product_name   = $_POST['product_name'];
            $sku            = $_POST['sku'];
            $category       = $_POST['category'];
            $price          = (float)($_POST['price'] ?? 0);
            $sale_price     = ($_POST['sale_price'] ?? '') !== '' ? (float)$_POST['sale_price'] : null;
            $cost_price     = ($_POST['cost_price'] ?? '') !== '' ? (float)$_POST['cost_price'] : null;
            $quantity       = (int)($_POST['quantity'] ?? 0);
            $min_stock      = ($_POST['min_stock'] ?? '') !== '' ? (int)$_POST['min_stock'] : null;
            $brand          = trim($_POST['brand'] ?? '');
            $description    = trim($_POST['description'] ?? '');
            $sizes_json     = csv_to_json_or_null($_POST['sizes']  ?? '');
            $colors_json    = csv_to_json_or_null($_POST['colors'] ?? '');
            $tags           = trim($_POST['tags'] ?? '');
            $weight         = ($_POST['weight'] ?? '') !== '' ? (float)$_POST['weight'] : null;
            $material       = trim($_POST['material'] ?? '');
            $season         = $_POST['season'] ?? '';
            $status         = $_POST['status'] ?? 'active';
            $is_featured    = isset($_POST['is_featured']) ? 1 : 0;
            $track_inventory= isset($_POST['track_inventory']) ? 1 : 0;
            $allow_backorder= isset($_POST['allow_backorder']) ? 1 : 0;

            $sql = "INSERT INTO products (
                product_name, sku, category, price, sale_price, cost_price, quantity, min_stock,
                product_image, brand, description, sizes, colors, tags, weight, material, season,
                status, is_featured, track_inventory, allow_backorder, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sssdddiisssssdssssiii",
                $product_name, $sku, $category, $price, $sale_price, $cost_price, $quantity, $min_stock,
                $imagePath, $brand, $description, $sizes_json, $colors_json, $tags, $weight, $material, $season,
                $status, $is_featured, $track_inventory, $allow_backorder
            );
            
            if ($stmt->execute()) {
                $stmt->close();
                // header("Location: " . $_SERVER['PHP_SELF'] . "?page=inventory&success=product");
                header("Location: index.php?page=inventory&success=product");
                exit();
            } else {
                $stmt->close();
                // header("Location: " . $_SERVER['PHP_SELF'] . "?page=inventory&error=" . urlencode("Database error occurred"));
                header("Location: index.php?page=inventory&error=" . urlencode("Database error occurred"));
                exit();
            }
        }
    } else {
        $errorMessage = implode(", ", $errors);
        // header("Location: " . $_SERVER['PHP_SELF'] . "?page=inventory&error=" . urlencode($errorMessage));
        header("Location: index.php?page=inventory&error=" . urlencode($errorMessage));
        exit();
    }
}

// Fetch data for forms/tables
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
<head>
<style>
    /* Complete styling for inventory management within dashboard */
    .inventory-container {
        padding: 1rem;
        font-size: 14px;
        line-height: 1.6;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        color: #1f2937;
    }
    
    .nav-tabs {
        border: none;
        margin-bottom: 2rem;
        background: #ffffff;
        padding: 0.5rem;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        display: flex;
        list-style: none;
    }

    .nav-item {
        margin: 0;
    }

    .nav-tabs .nav-link {
        background: transparent;
        border: none;
        color: #6b7280;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        margin-right: 0.25rem;
        font-weight: 500;
        transition: all 0.2s ease;
        text-decoration: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .nav-tabs .nav-link.active {
        background: #2563eb;
        color: #ffffff;
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
    }

    .nav-tabs .nav-link:hover:not(.active) {
        background: #eff6ff;
        color: #2563eb;
    }

    .management-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid #e5e7eb;
        margin-bottom: 2rem;
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn, .btn-primary-custom {
        background: #2563eb;
        border: 1px solid #2563eb;
        color: #ffffff;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 500;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        font-size: 14px;
    }

    .btn:hover, .btn-primary-custom:hover {
        background: #3b82f6;
        border-color: #3b82f6;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    .btn-outline-custom {
        background: transparent;
        border: 1px solid #e5e7eb;
        color: #6b7280;
        padding: 0.6rem 1.2rem;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
    }

    .btn-outline-custom:hover {
        background: #f9fafb;
        border-color: #2563eb;
        color: #2563eb;
    }

    .search-section {
        background: #f9fafb;
        padding: 1.5rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        border: 1px solid #e5e7eb;
    }

    .form-control, .form-select, input, select, textarea {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        transition: all 0.2s ease;
        font-size: 14px;
        width: 100%;
        font-family: inherit;
    }

    .form-control:focus, .form-select:focus, input:focus, select:focus, textarea:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        outline: none;
    }

    .form-label, label {
        color: #1f2937;
        font-weight: 500;
        margin-bottom: 0.5rem;
        display: block;
    }

    .table-container {
        background: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid #e5e7eb;
    }

    .table {
        margin-bottom: 0;
        width: 100%;
        border-collapse: collapse;
    }

    .table thead th {
        background: #f9fafb;
        color: #1f2937;
        font-weight: 600;
        border: none;
        padding: 1rem;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-align: left;
    }

    .table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #e5e7eb;
    }

    .table tbody tr:hover {
        background: #f9fafb;
    }

    .table tbody tr:last-child td {
        border-bottom: none;
    }

    .image-preview {
        width: 60px;
        height: 60px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid #e5e7eb;
    }

    .badge-status {
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-weight: 500;
        font-size: 0.75rem;
        display: inline-block;
    }

    .badge-active {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
        border: 1px solid #10b981;
    }

    .badge-inactive {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border: 1px solid #ef4444;
    }

    .btn-action {
        padding: 0.4rem 0.8rem;
        margin: 0 0.2rem;
        border-radius: 6px;
        border: none;
        font-size: 0.8rem;
        transition: all 0.2s ease;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-edit {
        background: #f59e0b;
        color: #ffffff;
    }

    .btn-delete {
        background: #ef4444;
        color: #ffffff;
    }

    .btn-action:hover {
        transform: scale(1.05);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .alert {
        border-radius: 12px;
        border: none;
        margin-bottom: 2rem;
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
    }

    .alert-success {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
        border: 1px solid #10b981;
    }

    .alert-danger {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border: 1px solid #ef4444;
    }

    /* Enhanced Modal Styles */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0,0,0,0.6);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 99999;
        padding: 1rem;
    }

    .modal.show {
        display: flex !important;
    }

    .modal-dialog {
        background: white;
        border-radius: 16px;
        max-width: 600px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        margin: 0;
    }

    .modal-xl .modal-dialog {
        max-width: 900px;
    }

    .modal-header {
        background: #f9fafb;
        border-radius: 16px 16px 0 0;
        border-bottom: 1px solid #e5e7eb;
        padding: 1.5rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title {
        font-weight: 600;
        font-size: 1.2rem;
        color: #1f2937;
        margin: 0;
    }

    .btn-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #6b7280;
        padding: 0.25rem 0.5rem;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-close:hover {
        color: #1f2937;
    }

    .modal-body {
        padding: 2rem;
        max-height: calc(90vh - 140px);
        overflow-y: auto;
    }

    .modal-footer {
        border-top: 1px solid #e5e7eb;
        padding: 1rem 2rem 2rem;
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }

    .form-section {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 1.5rem;
        background: #f9fafb;
        margin-bottom: 1.5rem;
    }

    .form-section h6 {
        color: #1f2937;
        font-weight: 600;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1rem;
    }

    .size-selection, .color-selection {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }

    .size-option {
        padding: 0.5rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #ffffff;
        font-weight: 500;
        color: #6b7280;
        font-size: 14px;
    }

    .size-option:hover {
        border-color: #2563eb;
        color: #2563eb;
    }

    .size-option.selected {
        background: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
    }

    .color-option {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 2px solid #e5e7eb;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .color-option:hover {
        border-color: #2563eb;
        transform: scale(1.1);
    }

    .color-option.selected {
        border-color: #2563eb;
        border-width: 3px;
        transform: scale(1.1);
    }

    .input-group {
        display: flex;
        width: 100%;
    }

    .input-group-text {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        color: #6b7280;
        padding: 0.75rem 1rem;
        border-radius: 8px 0 0 8px;
        border-right: none;
        font-size: 14px;
    }

    .input-group .form-control, .input-group input {
        border-radius: 0 8px 8px 0;
        border-left: none;
    }

    .form-check {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }

    .form-check-input {
        margin: 0;
        width: auto;
    }

    .form-check-label {
        margin-bottom: 0;
        cursor: pointer;
    }

    .required {
        color: #ef4444;
    }

    .small, small {
        font-size: 0.8rem;
    }

    /* Utility classes */
    .text-primary { color: #2563eb !important; }
    .text-success { color: #10b981 !important; }
    .text-muted { color: #6b7280 !important; }
    .text-warning { color: #f59e0b !important; }
    .d-flex { display: flex; }
    .d-block { display: block; }
    .justify-content-between { justify-content: space-between; }
    .align-items-center { align-items: center; }
    .mb-4 { margin-bottom: 1.5rem; }
    .mb-3 { margin-bottom: 1rem; }
    .mb-2 { margin-bottom: 0.5rem; }
    .mb-0 { margin-bottom: 0; }
    .me-2 { margin-right: 0.5rem; }
    .me-1 { margin-right: 0.25rem; }
    .mt-2 { margin-top: 0.5rem; }
    .mt-1 { margin-top: 0.25rem; }
    .fw-bold { font-weight: 700; }

    .row { 
        display: flex; 
        flex-wrap: wrap; 
        margin-left: -0.5rem;
        margin-right: -0.5rem;
    }
    .col-md-8 { flex: 0 0 66.666667%; max-width: 66.666667%; padding: 0 0.5rem; }
    .col-md-6 { flex: 0 0 50%; max-width: 50%; padding: 0 0.5rem; }
    .col-md-4 { flex: 0 0 33.333333%; max-width: 33.333333%; padding: 0 0.5rem; }
    .col-md-3 { flex: 0 0 25%; max-width: 25%; padding: 0 0.5rem; }
    .col-md-2 { flex: 0 0 16.666667%; max-width: 16.666667%; padding: 0 0.5rem; }

    .tab-content {
        margin-top: 0;
    }

    .tab-pane {
        display: none;
    }

    .tab-pane.show.active {
        display: block;
    }

    @media (max-width: 768px) {
        .col-md-8, .col-md-6, .col-md-4, .col-md-3, .col-md-2 {
            flex: 0 0 100%;
            max-width: 100%;
        }
        
        .modal-dialog {
            margin: 0.5rem;
            max-width: calc(100vw - 1rem);
        }
        
        .inventory-container {
            padding: 0.5rem;
        }
    }
</style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

</head>
<div class="inventory-container">
    <?php if (!empty($message)): ?>
        <div class="alert <?= $message_type === 'success' ? 'alert-success' : 'alert-danger' ?>" id="alertMessage">
            <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
            <?= $message ?>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
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

    <div class="tab-content" id="managementTabContent">
        <!-- Categories Tab -->
        <div class="tab-pane fade show active" id="categories" role="tabpanel">
            <div class="management-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="section-title mb-0">
                        <i class="fas fa-tags text-primary"></i>Categories
                    </h3>
                    <button class="btn btn-primary-custom" onclick="openModal('addCategoryModal')">
                        <i class="fas fa-plus me-2"></i>Add Category
                    </button>
                </div>

                <!-- Search -->
                <div class="search-section">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- <input type="text" class="form-control" placeholder="Search categories..." id="categorySearch"> -->
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
                    <table class="table" id="categoryTable">
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
                        <?php if (empty($display_categories)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem; color: #6b7280;">
                                    No categories found. <a href="#" onclick="openModal('addCategoryModal')" style="color: #2563eb;">Add your first category</a>
                                </td>
                            </tr>
                        <?php else: ?>
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
                                        <button class="btn-action btn-edit" onclick="editCategory(<?= (int)$category['id'] ?>)"><i class="fas fa-edit"></i></button>
                                        <button class="btn-action btn-delete" onclick="deleteCategory(<?= (int)$category['id'] ?>)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Products Tab -->
        <div class="tab-pane fade" id="products" role="tabpanel">
            <div class="management-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="section-title mb-0">
                        <i class="fas fa-tshirt text-success"></i>Products
                    </h3>
                    <button class="btn btn-primary-custom" onclick="openModal('addProductModal')">
                        <i class="fas fa-plus me-2"></i>Add Product
                    </button>
                </div>

                <!-- Products Table -->
                <div class="table-container">
                    <table class="table" id="productTable">
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
                        <?php if (empty($display_products)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem; color: #6b7280;">
                                    No products found. <a href="#" onclick="openModal('addProductModal')" style="color: #2563eb;">Add your first product</a>
                                </td>
                            </tr>
                        <?php else: ?>
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
                                        <strong>$<?= number_format((float)$product['price'], 2) ?></strong>
                                        <?php if (!is_null($product['sale_price']) && $product['sale_price'] !== ''): ?>
                                            <br><small class="text-muted">Sale: $<?= number_format((float)$product['sale_price'], 2) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $qty = (int)$product['quantity'];
                                        $min = isset($product['min_stock']) ? (int)$product['min_stock'] : null;
                                        $stock_class = 'background: #10b981; color: white;';
                                        $stock_text = 'In Stock';
                                        if ($qty === 0) { $stock_class = 'background: #ef4444; color: white;'; $stock_text = 'Out of Stock'; }
                                        elseif (!is_null($min) && $qty <= $min) { $stock_class = 'background: #f59e0b; color: white;'; $stock_text = 'Low Stock'; }
                                        ?>
                                        <span style="padding: 0.25rem 0.5rem; <?= $stock_class ?> border-radius: 4px; font-size: 0.8rem;"><?= $qty ?></span>
                                        <small class="text-muted d-block"><?= $stock_text ?></small>
                                    </td>
                                    <td>
                                        <span class="badge-status <?= $product['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                            <?= ucfirst($product['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-action btn-edit" onclick="editProduct(<?= (int)$product['id'] ?>)"><i class="fas fa-edit"></i></button>
                                        <button class="btn-action btn-delete" onclick="deleteProduct(<?= (int)$product['id'] ?>)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- <form method="POST" enctype="multipart/form-data" action="?page=inventory"> -->
                <form method="POST" enctype="multipart/form-data" action="product_content.php">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add New Category</h5>
                    <button type="button" class="btn-close" onclick="closeModal('addCategoryModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Category Name <span class="required">*</span></label>
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
                        <small class="text-muted d-block mt-1">Allowed: JPG, PNG, GIF, WEBP. Max 5MB.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-custom" onclick="closeModal('addCategoryModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom" name="save_category">
                        <i class="fas fa-save me-2"></i>Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal modal-xl" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- <form method="POST" enctype="multipart/form-data" id="addProductForm" action="?page=inventory"> -->
                <form method="POST" enctype="multipart/form-data" id="addProductForm" action="product_content.php">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add New Product</h5>
                    <button type="button" class="btn-close" onclick="closeModal('addProductModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <!-- Basic Information -->
                    <div class="form-section">
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

                    <!-- Pricing & Inventory -->
                    <div class="form-section">
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

                    <!-- Product Details -->
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
                                        <div class="color-option" data-color="White" style="background-color: #FFFFFF; border: 2px solid #ddd;"></div>
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
                                    <small class="text-muted d-block mt-1">Allowed: JPG, PNG, GIF, WEBP. Max 5MB.</small>
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

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured">
                                <label class="form-check-label" for="is_featured"><i class="fas fa-star text-warning me-1"></i>Featured Product</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="track_inventory" id="track_inventory" checked>
                                <label class="form-check-label" for="track_inventory"><i class="fas fa-boxes text-primary me-1"></i>Track Inventory</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="allow_backorder" id="allow_backorder">
                                <label class="form-check-label" for="allow_backorder"><i class="fas fa-clock text-primary me-1"></i>Allow Backorder</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-custom" onclick="closeModal('addProductModal')">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary-custom" name="save_product">
                        <i class="fas fa-save me-2"></i>Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Simple modal functionality without AJAX
function openModal(modalId) {
    console.log('Opening modal:', modalId);
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Focus first input in modal
        setTimeout(() => {
            const firstInput = modal.querySelector('input, select, textarea');
            if (firstInput) firstInput.focus();
        }, 100);
    }
}

function closeModal(modalId) {
    console.log('Closing modal:', modalId);
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        // Reset forms
        resetModalForm(modalId);
    }
}

function resetModalForm(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    const form = modal.querySelector('form');
    if (form) form.reset();
    
    // Reset image preview
    const preview = modal.querySelector('#imagePreview');
    if (preview) preview.style.display = 'none';
    
    // Reset selections
    modal.querySelectorAll('.size-option.selected').forEach(o => o.classList.remove('selected'));
    modal.querySelectorAll('.color-option.selected').forEach(o => o.classList.remove('selected'));
    
    // Clear hidden inputs
    const sizesInput = modal.querySelector('#sizes');
    const colorsInput = modal.querySelector('#colors');
    if (sizesInput) sizesInput.value = '';
    if (colorsInput) colorsInput.value = '';
}

// Tab functionality
function initializeTabs() {
    const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-bs-target');
            
            // Remove active classes
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            
            // Add active classes
            this.classList.add('active');
            const targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
        });
    });
}

// Image preview functionality
function initializeImagePreview() {
    const productImageInput = document.getElementById('product_image');
    if (productImageInput) {
        productImageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagePreview');
            if (file && preview) {
                const reader = new FileReader();
                reader.onload = function(ev) { 
                    preview.src = ev.target.result; 
                    preview.style.display = 'block'; 
                };
                reader.readAsDataURL(file);
            } else if (preview) {
                preview.style.display = 'none';
            }
        });
    }
}

// Auto-generate SKU from product name
function initializeSKUGeneration() {
    const productNameInput = document.getElementById('product_name');
    if (productNameInput) {
        productNameInput.addEventListener('input', function(e) {
            const productName = e.target.value;
            const skuField = document.getElementById('sku');
            if (productName && skuField && skuField.value === '') {
                const sku = productName.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 6)
                    + '-' + Math.floor(Math.random() * 1000).toString().padStart(3, '0');
                skuField.value = sku;
            }
        });
    }
}

// Size selection functionality
function initializeSizeSelection() {
    document.querySelectorAll('.size-option').forEach(option => {
        option.addEventListener('click', function() { 
            this.classList.toggle('selected'); 
            updateSizes(); 
        });
    });
    
    function updateSizes() {
        const selectedSizes = Array.from(document.querySelectorAll('.size-option.selected')).map(o => o.dataset.size);
        const sizesInput = document.getElementById('sizes');
        if (sizesInput) {
            sizesInput.value = selectedSizes.join(',');
        }
    }
}

// Color selection functionality
function initializeColorSelection() {
    document.querySelectorAll('.color-option').forEach(option => {
        option.addEventListener('click', function() { 
            this.classList.toggle('selected'); 
            updateColors(); 
        });
    });
    
    function updateColors() {
        const selectedColors = Array.from(document.querySelectorAll('.color-option.selected')).map(o => o.dataset.color);
        const colorsInput = document.getElementById('colors');
        if (colorsInput) {
            colorsInput.value = selectedColors.join(',');
        }
    }
}

// Close modal when clicking outside or pressing ESC
function initializeModalEvents() {
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal') && e.target.classList.contains('show')) {
            closeModal(e.target.id);
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                closeModal(openModal.id);
            }
        }
    });
}

// Auto-hide alert messages
function initializeAlerts() {
    const alert = document.getElementById('alertMessage');
    if (alert) {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 300);
        }, 5000);
    }
}

// Initialize all functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing inventory management...');
    
    initializeTabs();
    initializeImagePreview();
    initializeSKUGeneration();
    initializeSizeSelection();
    initializeColorSelection();
    initializeModalEvents();
    initializeAlerts();
    
    console.log('Inventory management initialized successfully');
});

// Placeholder functions for edit/delete actions
function editCategory(id) { 
    alert('Edit category functionality to be implemented. ID: ' + id); 
}

function deleteCategory(id) { 
    if (confirm('Are you sure you want to delete this category?')) {
        alert('Delete category functionality to be implemented. ID: ' + id); 
    }
}

function editProduct(id) { 
    alert('Edit product functionality to be implemented. ID: ' + id); 
}

function deleteProduct(id) { 
    if (confirm('Are you sure you want to delete this product?')) {
        alert('Delete product functionality to be implemented. ID: ' + id); 
    }
}
  $(document).ready(function() {
            $('#productTable').DataTable({
                pageLength: 10, // number of rows per page
                lengthMenu: [5, 10, 25, 50, 100], // dropdown options
                ordering: true, // enable sorting
                searching: true // enable search box
            });
        });
          $(document).ready(function() {
            $('#categoryTable').DataTable({
                pageLength: 10, // number of rows per page
                lengthMenu: [5, 10, 25, 50, 100], // dropdown options
                ordering: true, // enable sorting
                searching: true // enable search box
            });
        });
</script>