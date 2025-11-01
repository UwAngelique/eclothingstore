<?php
// discount_promotion_content.php - RWF Currency Version
// Headers-safe version - uses JavaScript redirects only

error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connect.php';
include 'discount_functions.php';

// Helper function for safe redirects - JavaScript only (no headers)
function safe_redirect($url) {
    // Always use JavaScript redirect since headers are already sent by index.php
    echo '<script type="text/javascript">window.location.href="' . htmlspecialchars($url, ENT_QUOTES) . '";</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES) . '" /></noscript>';
    exit();
}

$message = '';
$message_type = '';

// Handle success/error messages
if (isset($_GET['success'])) {
    switch($_GET['success']) {
        case 'discount':
            $count = isset($_GET['count']) ? (int)$_GET['count'] : 1;
            $message = $count > 1 ? "Discount applied to {$count} products successfully!" : "Product discount added successfully!";
            $message_type = 'success';
            break;
        case 'promotion':
            $message = "Promotion created successfully!";
            $message_type = 'success';
            break;
        case 'discount_updated':
            $message = "Discount updated successfully!";
            $message_type = 'success';
            break;
        case 'promotion_updated':
            $message = "Promotion updated successfully!";
            $message_type = 'success';
            break;
        case 'discount_deleted':
            $count = isset($_GET['count']) ? (int)$_GET['count'] : 1;
            $message = "Discount removed from {$count} product(s) successfully!";
            $message_type = 'success';
            break;
        case 'promotion_deleted':
            $message = "Promotion deleted successfully!";
            $message_type = 'success';
            break;
    }
}

if (isset($_GET['error'])) {
    $message = urldecode($_GET['error']);
    $message_type = 'error';
}

// Handle Product Discount Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product_discount'])) {
    $productId = (int)$_POST['product_id'];
    $discountPercentage = (float)$_POST['discount_percentage'];
    $startDate = !empty($_POST['discount_start_date']) ? $_POST['discount_start_date'] : null;
    $endDate = !empty($_POST['discount_end_date']) ? $_POST['discount_end_date'] : null;
    
    $errors = [];
    if ($discountPercentage < 0 || $discountPercentage > 100) {
        $errors[] = "Discount percentage must be between 0 and 100";
    }
    
    if (!$errors) {
        $stmt = $conn->prepare("UPDATE products SET discount_percentage = ?, discount_start_date = ?, discount_end_date = ? WHERE id = ?");
        $stmt->bind_param("dssi", $discountPercentage, $startDate, $endDate, $productId);
        
        if ($stmt->execute()) {
            $stmt->close();
            safe_redirect("index.php?page=discount&success=discount");
        } else {
            $stmt->close();
            safe_redirect("index.php?page=discount&error=" . urlencode("Database error occurred"));
        }
    } else {
        safe_redirect("index.php?page=discount&error=" . urlencode(implode(", ", $errors)));
    }
}

// Handle Bulk Discount Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_bulk_discount'])) {
    $productIds = $_POST['product_ids'] ?? '';
    $discountPercentage = (float)$_POST['bulk_discount_percentage'];
    $startDate = !empty($_POST['bulk_discount_start_date']) ? $_POST['bulk_discount_start_date'] : null;
    $endDate = !empty($_POST['bulk_discount_end_date']) ? $_POST['bulk_discount_end_date'] : null;
    $overrideExisting = isset($_POST['override_existing']);
    
    $errors = [];
    if (empty($productIds)) {
        $errors[] = "No products selected";
    }
    if ($discountPercentage < 0 || $discountPercentage > 100) {
        $errors[] = "Discount percentage must be between 0 and 100";
    }
    
    if (!$errors) {
        $ids = explode(',', $productIds);
        $successCount = 0;
        
        foreach ($ids as $id) {
            $id = (int)trim($id);
            if ($id > 0) {
                if (!$overrideExisting) {
                    $checkStmt = $conn->prepare("SELECT discount_percentage FROM products WHERE id = ?");
                    $checkStmt->bind_param("i", $id);
                    $checkStmt->execute();
                    $checkStmt->bind_result($existingDiscount);
                    $checkStmt->fetch();
                    $checkStmt->close();
                    
                    if ($existingDiscount > 0) continue;
                }
                
                $stmt = $conn->prepare("UPDATE products SET discount_percentage = ?, discount_start_date = ?, discount_end_date = ? WHERE id = ?");
                $stmt->bind_param("dssi", $discountPercentage, $startDate, $endDate, $id);
                if ($stmt->execute()) $successCount++;
                $stmt->close();
            }
        }
        
        safe_redirect("index.php?page=discount&success=discount&count=" . $successCount);
    } else {
        safe_redirect("index.php?page=discount&error=" . urlencode(implode(", ", $errors)));
    }
}

// Handle Category-Wide Discount
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category_discount'])) {
    $categoryName = $_POST['category_name'];
    $discountPercentage = (float)$_POST['category_discount_percentage'];
    $startDate = !empty($_POST['category_discount_start_date']) ? $_POST['category_discount_start_date'] : null;
    $endDate = !empty($_POST['category_discount_end_date']) ? $_POST['category_discount_end_date'] : null;
    $overrideExisting = isset($_POST['category_override_existing']);
    
    $errors = [];
    if (empty($categoryName)) {
        $errors[] = "Category is required";
    }
    if ($discountPercentage < 0 || $discountPercentage > 100) {
        $errors[] = "Discount percentage must be between 0 and 100";
    }
    
    if (!$errors) {
        if ($overrideExisting) {
            $stmt = $conn->prepare("UPDATE products SET discount_percentage = ?, discount_start_date = ?, discount_end_date = ? WHERE category = ? AND status = 'active'");
            $stmt->bind_param("dsss", $discountPercentage, $startDate, $endDate, $categoryName);
        } else {
            $stmt = $conn->prepare("UPDATE products SET discount_percentage = ?, discount_start_date = ?, discount_end_date = ? WHERE category = ? AND status = 'active' AND (discount_percentage IS NULL OR discount_percentage = 0)");
            $stmt->bind_param("dsss", $discountPercentage, $startDate, $endDate, $categoryName);
        }
        
        if ($stmt->execute()) {
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            safe_redirect("index.php?page=discount&success=discount&count=" . $affectedRows);
        } else {
            $stmt->close();
            safe_redirect("index.php?page=discount&error=" . urlencode("Database error occurred"));
        }
    } else {
        safe_redirect("index.php?page=discount&error=" . urlencode(implode(", ", $errors)));
    }
}

// Handle Copy Discount
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_copy_discount'])) {
    $discountPercentage = (float)$_POST['copy_discount_percentage'];
    $startDate = !empty($_POST['copy_start_date']) ? $_POST['copy_start_date'] : null;
    $endDate = !empty($_POST['copy_end_date']) ? $_POST['copy_end_date'] : null;
    $targetProducts = $_POST['copy_target_products'] ?? '';
    $overrideExisting = isset($_POST['copy_override_existing']);
    
    $errors = [];
    if (empty($targetProducts)) {
        $errors[] = "No target products selected";
    }
    
    if (!$errors) {
        $ids = explode(',', $targetProducts);
        $successCount = 0;
        
        foreach ($ids as $id) {
            $id = (int)trim($id);
            if ($id > 0) {
                if (!$overrideExisting) {
                    $checkStmt = $conn->prepare("SELECT discount_percentage FROM products WHERE id = ?");
                    $checkStmt->bind_param("i", $id);
                    $checkStmt->execute();
                    $checkStmt->bind_result($existingDiscount);
                    $checkStmt->fetch();
                    $checkStmt->close();
                    
                    if ($existingDiscount > 0) continue;
                }
                
                $stmt = $conn->prepare("UPDATE products SET discount_percentage = ?, discount_start_date = ?, discount_end_date = ? WHERE id = ?");
                $stmt->bind_param("dssi", $discountPercentage, $startDate, $endDate, $id);
                if ($stmt->execute()) $successCount++;
                $stmt->close();
            }
        }
        
        safe_redirect("index.php?page=discount&success=discount&count=" . $successCount);
    } else {
        safe_redirect("index.php?page=discount&error=" . urlencode(implode(", ", $errors)));
    }
}

// Handle Clear All Discounts
if (isset($_GET['clear_all_discounts']) && $_GET['clear_all_discounts'] === 'confirm') {
    $stmt = $conn->prepare("UPDATE products SET discount_percentage = 0, discount_start_date = NULL, discount_end_date = NULL WHERE discount_percentage > 0");
    $stmt->execute();
    $affectedRows = $stmt->affected_rows;
    $stmt->close();
    safe_redirect("index.php?page=discount&success=discount_deleted&count=" . $affectedRows);
}

// Handle Promotion Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_promotion'])) {
    $name = trim($_POST['promotion_name']);
    $code = strtoupper(trim($_POST['promotion_code']));
    $description = trim($_POST['description'] ?? '');
    $discountType = $_POST['discount_type'];
    $discountValue = (float)$_POST['discount_value'];
    $maxDiscount = !empty($_POST['max_discount_amount']) ? (float)$_POST['max_discount_amount'] : null;
    $minPurchase = !empty($_POST['min_purchase_amount']) ? (float)$_POST['min_purchase_amount'] : 0;
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $usageLimit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
    $usagePerCustomer = (int)($_POST['usage_per_customer'] ?? 1);
    $applyTo = $_POST['apply_to'] ?? 'all';
    $status = $_POST['status'] ?? 'active';
    
    $errors = [];
    if (empty($name)) $errors[] = "Promotion name is required";
    if (empty($code)) $errors[] = "Promotion code is required";
    if ($discountValue < 0) $errors[] = "Discount value must be greater than or equal to 0";
    
    // Check if code already exists
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM promotions WHERE code = ?");
    $checkStmt->bind_param("s", $code);
    $checkStmt->execute();
    $checkStmt->bind_result($count);
    $checkStmt->fetch();
    $checkStmt->close();
    
    if ($count > 0) {
        $errors[] = "Promotion code already exists";
    }
    
    if (!$errors) {
        $stmt = $conn->prepare("INSERT INTO promotions (name, code, description, discount_type, discount_value, max_discount_amount, min_purchase_amount, start_date, end_date, usage_limit, usage_per_customer, apply_to, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssdddssiiis", $name, $code, $description, $discountType, $discountValue, $maxDiscount, $minPurchase, $startDate, $endDate, $usageLimit, $usagePerCustomer, $applyTo, $status);
        
        if ($stmt->execute()) {
            $promotionId = $stmt->insert_id;
            $stmt->close();
            
            // Handle eligibility if not 'all'
            if ($applyTo !== 'all' && !empty($_POST['eligible_items'])) {
                $eligibleItems = explode(',', $_POST['eligible_items']);
                $entityType = $applyTo;
                
                foreach ($eligibleItems as $itemId) {
                    $itemId = (int)trim($itemId);
                    if ($itemId > 0) {
                        $stmt = $conn->prepare("INSERT INTO promotion_eligibility (promotion_id, entity_type, entity_id) VALUES (?, ?, ?)");
                        $stmt->bind_param("isi", $promotionId, $entityType, $itemId);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
            
            safe_redirect("index.php?page=discount&success=promotion");
        } else {
            $stmt->close();
            safe_redirect("index.php?page=discount&error=" . urlencode("Database error occurred"));
        }
    } else {
        safe_redirect("index.php?page=discount&error=" . urlencode(implode(", ", $errors)));
    }
}

// Handle Remove Product Discount
if (isset($_GET['remove_discount'])) {
    $productId = (int)$_GET['remove_discount'];
    $stmt = $conn->prepare("UPDATE products SET discount_percentage = 0, discount_start_date = NULL, discount_end_date = NULL WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $stmt->close();
    safe_redirect("index.php?page=discount&success=discount_deleted");
}

// Handle Activate Product Discount
if (isset($_GET['activate_discount'])) {
    $productId = (int)$_GET['activate_discount'];
    $now = date('Y-m-d H:i:s');
    $futureDate = date('Y-m-d H:i:s', strtotime('+1 year'));
    $stmt = $conn->prepare("UPDATE products SET discount_start_date = ?, discount_end_date = ? WHERE id = ?");
    $stmt->bind_param("ssi", $now, $futureDate, $productId);
    $stmt->execute();
    $stmt->close();
    safe_redirect("index.php?page=discount&success=discount_updated");
}

// Handle Deactivate Product Discount
if (isset($_GET['deactivate_discount'])) {
    $productId = (int)$_GET['deactivate_discount'];
    $pastDate = date('Y-m-d H:i:s', strtotime('-1 day'));
    $stmt = $conn->prepare("UPDATE products SET discount_end_date = ? WHERE id = ?");
    $stmt->bind_param("si", $pastDate, $productId);
    $stmt->execute();
    $stmt->close();
    safe_redirect("index.php?page=discount&success=discount_updated");
}

// Handle Toggle Promotion Status
if (isset($_GET['toggle_promotion'])) {
    $promotionId = (int)$_GET['toggle_promotion'];
    $stmt = $conn->prepare("UPDATE promotions SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
    $stmt->bind_param("i", $promotionId);
    $stmt->execute();
    $stmt->close();
    safe_redirect("index.php?page=discount&success=promotion_updated");
}

// Handle Delete Promotion
if (isset($_GET['delete_promotion'])) {
    $promotionId = (int)$_GET['delete_promotion'];
    
    // Delete eligibility rules first
    $stmt = $conn->prepare("DELETE FROM promotion_eligibility WHERE promotion_id = ?");
    $stmt->bind_param("i", $promotionId);
    $stmt->execute();
    $stmt->close();
    
    // Delete promotion
    $stmt = $conn->prepare("DELETE FROM promotions WHERE id = ?");
    $stmt->bind_param("i", $promotionId);
    $stmt->execute();
    $stmt->close();
    
    safe_redirect("index.php?page=discount&success=promotion_deleted");
}

// Fetch all active products for discount selection
$allProducts = [];
$res = $conn->query("SELECT id, product_name, sku, price, category, discount_percentage, discount_start_date, discount_end_date, product_image, status FROM products WHERE status = 'active' ORDER BY product_name");
if ($res) { 
    while ($row = $res->fetch_assoc()) { 
        $allProducts[] = $row; 
    } 
}

// Fetch all promotions
$allPromotions = [];
$res = $conn->query("SELECT * FROM promotions ORDER BY created_at DESC");
if ($res) { 
    while ($row = $res->fetch_assoc()) { 
        $allPromotions[] = $row; 
    } 
}

// Fetch categories for promotion eligibility
$categories = [];
$res = $conn->query("SELECT id, category_name FROM categories WHERE status = 'active' ORDER BY category_name");
if ($res) { 
    while ($row = $res->fetch_assoc()) { 
        $categories[] = $row; 
    } 
}

// Get statistics
$stats = getPromotionStatistics($conn);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    
    .inventory-container {
        padding: 1rem;
        font-size: 14px;
        line-height: 1.6;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        color: #1f2937;
        max-width: 100%;
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

    .nav-item { margin: 0; }

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
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
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
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
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
        animation: modalSlideIn 0.3s ease;
    }

    @keyframes modalSlideIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
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
        transition: color 0.2s;
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

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .stat-card.green {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }
    
    .stat-card.orange {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }
    
    .stat-card.blue {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        font-size: 0.9rem;
        opacity: 0.9;
    }
    
    .code-badge {
        background: #f3f4f6;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-family: monospace;
        font-weight: 600;
        color: #1f2937;
        border: 2px dashed #d1d5db;
    }
    
    .discount-badge {
        background: #fee2e2;
        color: #dc2626;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }
    
    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 24px;
    }
    
    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    
    input:checked + .toggle-slider {
        background-color: #10b981;
    }
    
    input:checked + .toggle-slider:before {
        transform: translateX(26px);
    }
    
    .date-range {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        color: #6b7280;
    }

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
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
</head>
<body>

<div class="inventory-container">
    <?php if (!empty($message)): ?>
        <div class="alert <?= $message_type === 'success' ? 'alert-success' : 'alert-danger' ?>" id="alertMessage">
            <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <!-- Statistics Dashboard -->
    <!-- <div class="stats-grid"> -->
        <!-- <div class="stat-card">
            <div class="stat-value"> $stats['active_promotions'] ?></div>
            <div class="stat-label"><i class="fas fa-tags me-2"></i>Active Promotions</div>
        </div> -->
        <!-- <div class="stat-card green">
            <div class="stat-value">// $stats['products_on_sale'] ?></div>
            <div class="stat-label"><i class="fas fa-percent me-2"></i>Products on Sale</div>
        </div> -->
        <!-- <div class="stat-card orange">
            <div class="stat-value">RWF number_format($stats['total_discount_amount'], 0) </div>
            <div class="stat-label"><i class="fas fa-dollar-sign me-2"></i>Total Discounts Given</div>
        </div>
        <div class="stat-card blue">
            <div class="stat-value">$stats['total_uses'] ?></div>
            <div class="stat-label"><i class="fas fa-chart-line me-2"></i>Total Promo Uses</div>
        </div> -->
    <!-- </div> -->

    <!-- Tabs -->
    <ul class="nav nav-tabs" id="discountTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="product-discounts-tab" data-bs-toggle="tab" data-bs-target="#product-discounts" type="button" role="tab">
                <i class="fas fa-percent me-2"></i>Product Discounts
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="promotions-tab" data-bs-toggle="tab" data-bs-target="#promotions" type="button" role="tab">
                <i class="fas fa-gift me-2"></i>Promotions
            </button>
        </li>
    </ul>

    <div class="tab-content" id="discountTabContent">
        <!-- Product Discounts Tab -->
        <div class="tab-pane fade show active" id="product-discounts" role="tabpanel">
            <div class="management-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="section-title mb-0">
                        <i class="fas fa-percent text-primary"></i>Manage Product Discounts
                    </h3>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="btn btn-outline-custom" onclick="bulkAddDiscount()">
                            <i class="fas fa-layer-group me-2"></i>Bulk Discount
                        </button>
                        <button class="btn btn-outline-custom" onclick="openModal('categoryDiscountModal')">
                            <i class="fas fa-tags me-2"></i>Category Discount
                        </button>
                        <button class="btn btn-outline-custom" onclick="clearAllDiscounts()">
                            <i class="fas fa-eraser me-2"></i>Clear All
                        </button>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="search-section">
                    <div class="row">
                        <div class="col-md-4">
                            <select class="form-select" id="filterCategory" onchange="filterTable()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category['category_name']) ?>">
                                        <?= htmlspecialchars($category['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" id="filterDiscount" onchange="filterTable()">
                                <option value="">All Products</option>
                                <option value="with_discount">With Discount</option>
                                <option value="without_discount">Without Discount</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div style="display: flex; align-items: center; gap: 0.5rem; background: white; padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid #e5e7eb;">
                                <i class="fas fa-info-circle text-primary"></i>
                                <span id="discountStats" style="font-size: 0.9rem; color: #6b7280;">
                                    Loading stats...
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="table-container">
                    <table class="table" id="discountTable">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="selectAllProducts" onchange="toggleSelectAll()">
                                </th>
                                <th>Image</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Original Price</th>
                                <th>Discount %</th>
                                <th>Sale Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($allProducts)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 2rem; color: #6b7280;">
                                    No products found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($allProducts as $product): ?>
                                <?php
                                $discountPercentage = $product['discount_percentage'] ?? 0;
                                $discountAmount = $product['price'] * ($discountPercentage / 100);
                                $salePrice = $product['price'] - $discountAmount;
                                $now = date('Y-m-d H:i:s');
                                $hasDiscount = $discountPercentage > 0;
                                $isActive = false;
                                
                                if ($hasDiscount) {
                                    $isActive = true;
                                    if (!empty($product['discount_start_date']) && $now < $product['discount_start_date']) $isActive = false;
                                    if (!empty($product['discount_end_date']) && $now > $product['discount_end_date']) $isActive = false;
                                }
                                ?>
                                <tr data-product-id="<?= $product['id'] ?>" data-has-discount="<?= $hasDiscount ? '1' : '0' ?>" data-category="<?= htmlspecialchars($product['category']) ?>">
                                    <td>
                                        <input type="checkbox" class="product-checkbox" value="<?= $product['id'] ?>">
                                    </td>
                                    <td>
                                        <?php if ($product['product_image']): ?>
                                            <img src="<?= htmlspecialchars($product['product_image']) ?>" class="image-preview" alt="Product">
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
                                        <strong>RWF <?= number_format($product['price'], 0) ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($hasDiscount): ?>
                                            <span class="discount-badge" style="font-size: 0.8rem;"><?= $discountPercentage ?>% OFF</span>
                                        <?php else: ?>
                                            <span class="text-muted">No discount</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($hasDiscount): ?>
                                            <strong class="text-success">RWF <?= number_format($salePrice, 0) ?></strong><br>
                                            <small class="text-muted">Save RWF <?= number_format($discountAmount, 0) ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($hasDiscount): ?>
                                            <?php if ($isActive): ?>
                                                <span class="badge-status badge-active">Active</span>
                                            <?php else: ?>
                                                <span class="badge-status badge-inactive">Inactive</span>
                                            <?php endif; ?>
                                            <?php if (!empty($product['discount_end_date'])): ?>
                                                <small class="text-muted d-block mt-1">Until <?= date('M d', strtotime($product['discount_end_date'])) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn-action btn-edit" onclick="editProductDiscount(<?= $product['id'] ?>, '<?= htmlspecialchars($product['product_name'], ENT_QUOTES) ?>', <?= $product['price'] ?>, <?= $discountPercentage ?>, '<?= $product['discount_start_date'] ?? '' ?>', '<?= $product['discount_end_date'] ?? '' ?>')" title="Edit Discount">
                                            <i class="fas fa-percent"></i>
                                        </button>
                                        <?php if ($hasDiscount): ?>
                                            <?php if ($isActive): ?>
                                                <button class="btn-action" style="background: #f59e0b; color: white;" onclick="deactivateDiscount(<?= $product['id'] ?>)" title="Deactivate Discount">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn-action" style="background: #10b981; color: white;" onclick="activateDiscount(<?= $product['id'] ?>)" title="Activate Discount">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn-action" style="background: #3b82f6; color: white;" onclick="copyDiscount(<?= $product['id'] ?>, <?= $discountPercentage ?>, '<?= $product['discount_start_date'] ?? '' ?>', '<?= $product['discount_end_date'] ?? '' ?>')" title="Copy Discount">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                            <button class="btn-action btn-delete" onclick="removeDiscount(<?= $product['id'] ?>)" title="Remove Discount">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Promotions Tab -->
        <div class="tab-pane fade" id="promotions" role="tabpanel">
            <div class="management-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="section-title mb-0">
                        <i class="fas fa-gift text-success"></i>Promotion Codes
                    </h3>
                    <button class="btn btn-primary-custom" onclick="openModal('addPromotionModal')">
                        <i class="fas fa-plus me-2"></i>Create Promotion
                    </button>
                </div>

                <div class="table-container">
                    <table class="table" id="promotionTable">
                        <thead>
                            <tr>
                                <th>Promotion Name</th>
                                <th>Code</th>
                                <th>Discount</th>
                                <th>Valid Period</th>
                                <th>Usage</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($allPromotions)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem; color: #6b7280;">
                                    No promotions found. <a href="#" onclick="openModal('addPromotionModal'); return false;" style="color: #2563eb;">Create your first promotion</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($allPromotions as $promo): ?>
                                <?php
                                $discountDesc = formatDiscountDescription($promo);
                                $now = date('Y-m-d H:i:s');
                                $isActive = $promo['status'] === 'active' && $now >= $promo['start_date'] && $now <= $promo['end_date'];
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($promo['name']) ?></strong></td>
                                    <td><span class="code-badge"><?= htmlspecialchars($promo['code']) ?></span></td>
                                    <td><?= htmlspecialchars($discountDesc) ?></td>
                                    <td>
                                        <div class="date-range">
                                            <span><?= date('M d', strtotime($promo['start_date'])) ?></span>
                                            <i class="fas fa-arrow-right"></i>
                                            <span><?= date('M d, Y', strtotime($promo['end_date'])) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?= $promo['times_used'] ?><?= $promo['usage_limit'] ? ' / ' . $promo['usage_limit'] : '' ?>
                                    </td>
                                    <td>
                                        <?php if ($promo['status'] === 'active'): ?>
                                            <button class="btn-action" style="background: #10b981; color: white; padding: 0.5rem 1rem; white-space: nowrap;" 
                                                    onclick="togglePromotion(<?= $promo['id'] ?>)" title="Click to deactivate">
                                                <i class="fas fa-check-circle"></i> Active
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-action" style="background: #6b7280; color: white; padding: 0.5rem 1rem; white-space: nowrap;" 
                                                    onclick="togglePromotion(<?= $promo['id'] ?>)" title="Click to activate">
                                                <i class="fas fa-pause-circle"></i> Inactive
                                            </button>
                                        <?php endif; ?>
                                        <?php if (!$isActive && $promo['status'] === 'active'): ?>
                                            <small class="text-warning d-block" style="margin-top: 0.5rem;">
                                                <i class="fas fa-exclamation-triangle"></i> Not in date range
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn-action btn-delete" onclick="deletePromotion(<?= $promo['id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
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

<!-- Edit Product Discount Modal -->
<div class="modal" id="editProductDiscountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-percent me-2"></i>Set Discount: <span id="edit_product_name"></span></h5>
                    <button type="button" class="btn-close" onclick="closeModal('editProductDiscountModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="edit_product_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Discount Percentage <span class="required">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="discount_percentage" id="edit_discount_percentage" 
                                   min="0" max="100" step="0.01" required oninput="updateEditPreview()">
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="text-muted">Enter 0 to remove discount</small>
                    </div>
                    
                    <div id="edit_price_preview" class="mb-3" style="display: none;">
                        <div class="form-section">
                            <h6>Price Preview</h6>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <small class="text-muted">Original Price</small>
                                    <div><strong id="edit_original_price">RWF 0</strong></div>
                                </div>
                                <i class="fas fa-arrow-right text-primary"></i>
                                <div>
                                    <small class="text-muted">Sale Price</small>
                                    <div><strong class="text-success" id="edit_sale_price">RWF 0</strong></div>
                                </div>
                                <div>
                                    <small class="text-muted">Customer Saves</small>
                                    <div><strong class="text-danger" id="edit_save_amount">RWF 0</strong></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="datetime-local" class="form-control" name="discount_start_date" id="edit_start_date">
                                <small class="text-muted">Leave empty for immediate</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Date</label>
                                <input type="datetime-local" class="form-control" name="discount_end_date" id="edit_end_date">
                                <small class="text-muted">Leave empty for no expiry</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section" style="background: #fef3c7; border-color: #fbbf24;">
                        <div style="display: flex; align-items: start; gap: 0.75rem;">
                            <i class="fas fa-lightbulb" style="color: #f59e0b; margin-top: 0.25rem;"></i>
                            <div>
                                <strong style="color: #92400e;">Quick Presets:</strong>
                                <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem; flex-wrap: wrap;">
                                    <button type="button" class="btn-outline-custom" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;" onclick="setDiscount(10)">10% OFF</button>
                                    <button type="button" class="btn-outline-custom" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;" onclick="setDiscount(20)">20% OFF</button>
                                    <button type="button" class="btn-outline-custom" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;" onclick="setDiscount(30)">30% OFF</button>
                                    <button type="button" class="btn-outline-custom" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;" onclick="setDiscount(50)">50% OFF</button>
                                    <button type="button" class="btn-outline-custom" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;" onclick="setDiscount(0)">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-custom" onclick="closeModal('editProductDiscountModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom" name="save_product_discount">
                        <i class="fas fa-save me-2"></i>Save Discount
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Copy Discount Modal -->
<div class="modal" id="copyDiscountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-copy me-2"></i>Copy Discount to Other Products</h5>
                    <button type="button" class="btn-close" onclick="closeModal('copyDiscountModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="copy_discount_percentage" id="copy_discount_percentage">
                    <input type="hidden" name="copy_start_date" id="copy_start_date">
                    <input type="hidden" name="copy_end_date" id="copy_end_date">
                    <input type="hidden" name="copy_target_products" id="copy_target_products">
                    
                    <div class="form-section" style="background: #eff6ff; border-color: #3b82f6;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <i class="fas fa-info-circle" style="color: #3b82f6; font-size: 1.5rem;"></i>
                            <div>
                                <strong style="color: #1e40af;">Copying discount:</strong>
                                <div id="copy_discount_info" style="margin-top: 0.5rem; color: #1e40af;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3 mt-3">
                        <label class="form-label">Select Target Products <span class="required">*</span></label>
                        <div style="max-height: 300px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 0.5rem;">
                            <?php foreach ($allProducts as $product): ?>
                                <div class="form-check" style="padding: 0.5rem;">
                                    <input class="form-check-input copy-target-checkbox" type="checkbox" 
                                           value="<?= $product['id'] ?>" 
                                           id="copy_target_<?= $product['id'] ?>">
                                    <label class="form-check-label" for="copy_target_<?= $product['id'] ?>" style="cursor: pointer;">
                                        <strong><?= htmlspecialchars($product['product_name']) ?></strong>
                                        <small class="text-muted d-block">SKU: <?= htmlspecialchars($product['sku']) ?> | Category: <?= htmlspecialchars($product['category']) ?></small>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem;">
                            <button type="button" class="btn-outline-custom" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;" onclick="selectAllCopyTargets()">Select All</button>
                            <button type="button" class="btn-outline-custom" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;" onclick="deselectAllCopyTargets()">Deselect All</button>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="copy_override_existing" value="1" checked>
                        <label class="form-check-label">
                            Override existing discounts on target products
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-custom" onclick="closeModal('copyDiscountModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom" name="save_copy_discount">
                        <i class="fas fa-copy me-2"></i>Copy Discount
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Category-Wide Discount Modal -->
<div class="modal" id="categoryDiscountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-tags me-2"></i>Apply Discount to Category</h5>
                    <button type="button" class="btn-close" onclick="closeModal('categoryDiscountModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Category <span class="required">*</span></label>
                        <select class="form-select" name="category_name" id="category_select" required onchange="updateCategoryProductCount()">
                            <option value="">Choose a category...</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category['category_name']) ?>">
                                    <?= htmlspecialchars($category['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted" id="category_product_count"></small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Discount Percentage <span class="required">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="category_discount_percentage" 
                                   min="0" max="100" step="0.01" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="datetime-local" class="form-control" name="category_discount_start_date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Date</label>
                                <input type="datetime-local" class="form-control" name="category_discount_end_date">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="category_override_existing" value="1" checked>
                        <label class="form-check-label">
                            Override existing discounts in this category
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-custom" onclick="closeModal('categoryDiscountModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom" name="save_category_discount">
                        <i class="fas fa-save me-2"></i>Apply to Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Add Discount Modal -->
<div class="modal" id="bulkDiscountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-layer-group me-2"></i>Bulk Add Discount</h5>
                    <button type="button" class="btn-close" onclick="closeModal('bulkDiscountModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="product_ids" id="bulk_product_ids">
                    
                    <div class="alert" style="background: #eff6ff; border: 1px solid #3b82f6; color: #1e40af;">
                        <i class="fas fa-info-circle me-2"></i>
                        <span id="bulk_selection_info">No products selected</span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Discount Percentage <span class="required">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="bulk_discount_percentage" id="bulk_discount_percentage" 
                                   min="0" max="100" step="0.01" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="datetime-local" class="form-control" name="bulk_discount_start_date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Date</label>
                                <input type="datetime-local" class="form-control" name="bulk_discount_end_date">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="override_existing" id="override_existing" value="1">
                        <label class="form-check-label" for="override_existing">
                            Override existing discounts
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-custom" onclick="closeModal('bulkDiscountModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom" name="save_bulk_discount">
                        <i class="fas fa-save me-2"></i>Apply to Selected Products
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Promotion Modal -->
<div class="modal modal-xl" id="addPromotionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="" id="promotionForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-gift me-2"></i>Create Promotion</h5>
                    <button type="button" class="btn-close" onclick="closeModal('addPromotionModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <!-- Basic Information -->
                    <div class="form-section">
                        <h6><i class="fas fa-info-circle me-2"></i>Basic Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Promotion Name <span class="required">*</span></label>
                                    <input type="text" class="form-control" name="promotion_name" required placeholder="e.g., Summer Sale 2025">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Promotion Code <span class="required">*</span></label>
                                    <input type="text" class="form-control" name="promotion_code" id="promo_code" required 
                                           placeholder="e.g., SUMMER25" style="text-transform: uppercase;">
                                    <small class="text-muted">Customers will enter this code at checkout</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2" 
                                      placeholder="Brief description of the promotion"></textarea>
                        </div>
                    </div>
                    
                    <!-- Discount Settings -->
                    <div class="form-section">
                        <h6><i class="fas fa-percent me-2"></i>Discount Settings</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Discount Type <span class="required">*</span></label>
                                    <select class="form-select" name="discount_type" id="discount_type" required onchange="updateDiscountFields()">
                                        <option value="percentage">Percentage Off (%)</option>
                                        <option value="fixed_amount">Fixed Amount Off ($)</option>
                                        <option value="bogo">Buy One Get One Free</option>
                                        <option value="free_shipping">Free Shipping</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3" id="discount_value_field">
                                    <label class="form-label">Discount Value <span class="required">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="discount_prefix">%</span>
                                        <input type="number" class="form-control" name="discount_value" id="discount_value" 
                                               step="0.01" min="0" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3" id="max_discount_field">
                                    <label class="form-label">Max Discount Amount (Optional)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">RWF</span>
                                        <input type="number" class="form-control" name="max_discount_amount" step="0.01" min="0">
                                    </div>
                                    <small class="text-muted">For percentage discounts, cap the maximum discount</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Minimum Purchase Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">RWF</span>
                                        <input type="number" class="form-control" name="min_purchase_amount" step="0.01" min="0" value="0">
                                    </div>
                                    <small class="text-muted">Minimum cart total required</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Validity Period -->
                    <div class="form-section">
                        <h6><i class="fas fa-calendar me-2"></i>Validity Period</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Start Date <span class="required">*</span></label>
                                    <input type="datetime-local" class="form-control" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">End Date <span class="required">*</span></label>
                                    <input type="datetime-local" class="form-control" name="end_date" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Usage Limits -->
                    <div class="form-section">
                        <h6><i class="fas fa-users me-2"></i>Usage Limits</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Total Usage Limit (Optional)</label>
                                    <input type="number" class="form-control" name="usage_limit" min="1" 
                                           placeholder="Leave empty for unlimited">
                                    <small class="text-muted">Total times this code can be used across all customers</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Usage Per Customer <span class="required">*</span></label>
                                    <input type="number" class="form-control" name="usage_per_customer" min="1" value="1" required>
                                    <small class="text-muted">Times each customer can use this code</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Apply To -->
                    <div class="form-section">
                        <h6><i class="fas fa-filter me-2"></i>Apply To</h6>
                        <div class="mb-3">
                            <label class="form-label">Eligible Items <span class="required">*</span></label>
                            <select class="form-select" name="apply_to" id="apply_to" required onchange="toggleEligibility()">
                                <option value="all">All Products</option>
                                <option value="product">Specific Products</option>
                                <option value="category">Specific Categories</option>
                            </select>
                        </div>
                        
                        <div id="eligibility_section" style="display: none;">
                            <div id="product_eligibility" style="display: none;">
                                <label class="form-label">Select Products</label>
                                <select class="form-select" id="eligible_products" multiple size="6">
                                    <?php foreach ($allProducts as $product): ?>
                                        <option value="<?= $product['id'] ?>">
                                            <?= htmlspecialchars($product['product_name']) ?> (<?= htmlspecialchars($product['sku']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted d-block mt-1">Hold Ctrl/Cmd to select multiple</small>
                            </div>
                            
                            <div id="category_eligibility" style="display: none;">
                                <label class="form-label">Select Categories</label>
                                <select class="form-select" id="eligible_categories" multiple size="6">
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>">
                                            <?= htmlspecialchars($category['category_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted d-block mt-1">Hold Ctrl/Cmd to select multiple</small>
                            </div>
                            <input type="hidden" name="eligible_items" id="eligible_items">
                        </div>
                    </div>
                    
                    <!-- Status -->
                    <div class="form-section">
                        <h6><i class="fas fa-toggle-on me-2"></i>Status</h6>
                        <div class="mb-3">
                            <select class="form-select" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <small class="text-muted">You can activate/deactivate the promotion later</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-custom" onclick="closeModal('addPromotionModal')">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary-custom" name="save_promotion">
                        <i class="fas fa-save me-2"></i>Create Promotion
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
// Modal functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        const form = modal.querySelector('form');
        if (form) form.reset();
        
        const pricePreview = document.getElementById('edit_price_preview');
        if (pricePreview) pricePreview.style.display = 'none';
        
        const eligibilitySection = document.getElementById('eligibility_section');
        if (eligibilitySection) eligibilitySection.style.display = 'none';
    }
}

// Tab functionality
function initializeTabs() {
    const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-bs-target');
            
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            
            this.classList.add('active');
            const targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
        });
    });
}

// Edit product discount
function editProductDiscount(productId, productName, price, currentDiscount, startDate, endDate) {
    const modal = document.getElementById('editProductDiscountModal');
    document.getElementById('edit_product_id').value = productId;
    document.getElementById('edit_product_name').textContent = productName;
    document.getElementById('edit_discount_percentage').value = currentDiscount || '';
    document.getElementById('edit_start_date').value = startDate || '';
    document.getElementById('edit_end_date').value = endDate || '';
    
    modal.dataset.productPrice = price;
    
    updateEditPreview();
    openModal('editProductDiscountModal');
}

// Update price preview
function updateEditPreview() {
    const modal = document.getElementById('editProductDiscountModal');
    const price = parseFloat(modal.dataset.productPrice) || 0;
    const discount = parseFloat(document.getElementById('edit_discount_percentage').value) || 0;
    
    if (price > 0 && discount > 0) {
        const discountAmt = price * (discount / 100);
        const salePrice = price - discountAmt;
        
        document.getElementById('edit_original_price').textContent = 'RWF ' + price.toFixed(0);
        document.getElementById('edit_sale_price').textContent = 'RWF ' + salePrice.toFixed(0);
        document.getElementById('edit_save_amount').textContent = 'RWF ' + discountAmt.toFixed(0);
        document.getElementById('edit_price_preview').style.display = 'block';
    } else {
        document.getElementById('edit_price_preview').style.display = 'none';
    }
}

// Set discount percentage
function setDiscount(percentage) {
    document.getElementById('edit_discount_percentage').value = percentage;
    updateEditPreview();
}

// Toggle select all
function toggleSelectAll() {
    const checked = document.getElementById('selectAllProducts').checked;
    document.querySelectorAll('.product-checkbox').forEach(cb => {
        cb.checked = checked;
    });
}

// Bulk add discount
function bulkAddDiscount() {
    const selectedIds = Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        alert('Please select at least one product');
        return;
    }
    
    document.getElementById('bulk_product_ids').value = selectedIds.join(',');
    document.getElementById('bulk_selection_info').textContent = selectedIds.length + ' product(s) selected';
    openModal('bulkDiscountModal');
}

// Update category product count
function updateCategoryProductCount() {
    const categorySelect = document.getElementById('category_select');
    const selectedCategory = categorySelect.value;
    const countDisplay = document.getElementById('category_product_count');
    
    if (!selectedCategory) {
        countDisplay.textContent = '';
        return;
    }
    
    let count = 0;
    document.querySelectorAll('#discountTable tbody tr').forEach(row => {
        const categoryAttr = row.dataset.category || '';
        if (categoryAttr === selectedCategory) {
            count++;
        }
    });
    
    countDisplay.textContent = count + ' product(s) in this category will be affected';
}

// Clear all discounts
function clearAllDiscounts() {
    if (confirm('Are you sure you want to remove ALL discounts from ALL products? This action cannot be undone.')) {
        if (confirm('This will affect ALL products with discounts. Click OK to proceed.')) {
            window.location.href = 'index.php?page=discount&clear_all_discounts=confirm';
        }
    }
}

// Filter table
function filterTable() {
    const categoryFilter = document.getElementById('filterCategory').value;
    const discountFilter = document.getElementById('filterDiscount').value;
    const table = document.getElementById('discountTable');
    const rows = table.querySelectorAll('tbody tr');
    
    let visibleCount = 0;
    let withDiscountCount = 0;
    let totalDiscount = 0;
    
    rows.forEach(row => {
        const category = row.dataset.category || '';
        const hasDiscount = row.dataset.hasDiscount === '1';
        
        let showRow = true;
        
        if (categoryFilter && category !== categoryFilter) {
            showRow = false;
        }
        
        if (discountFilter === 'with_discount' && !hasDiscount) {
            showRow = false;
        } else if (discountFilter === 'without_discount' && hasDiscount) {
            showRow = false;
        }
        
        row.style.display = showRow ? '' : 'none';
        
        if (showRow) {
            visibleCount++;
            if (hasDiscount) {
                withDiscountCount++;
                const priceText = row.cells[4]?.textContent || 'RWF 0';
                const price = parseFloat(priceText.replace('$', '').replace(',', ''));
                const discountBadge = row.cells[5]?.querySelector('.discount-badge');
                if (discountBadge) {
                    const discountPercent = parseFloat(discountBadge.textContent);
                    totalDiscount += price * (discountPercent / 100);
                }
            }
        }
    });
    
    updateDiscountStats(visibleCount, withDiscountCount, totalDiscount);
}

// Update discount statistics
function updateDiscountStats(total, withDiscount, discountAmount) {
    const statsElement = document.getElementById('discountStats');
    if (!statsElement) return;
    
    const percentage = total > 0 ? Math.round((withDiscount / total) * 100) : 0;
    statsElement.innerHTML = '<strong>' + withDiscount + '</strong> of <strong>' + total + '</strong> products have discounts (' + percentage + '%)' +
        (discountAmount > 0 ? ' • Est. savings: <strong>$' + discountAmount.toFixed(0) + '</strong>' : '');
}

// Calculate initial stats
function calculateInitialStats() {
    const table = document.getElementById('discountTable');
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    let total = 0;
    let withDiscount = 0;
    let totalDiscountAmount = 0;
    
    rows.forEach(row => {
        if (row.cells.length < 6) return;
        total++;
        const hasDiscount = row.dataset.hasDiscount === '1';
        if (hasDiscount) {
            withDiscount++;
            const priceText = row.cells[4]?.textContent || 'RWF 0';
            const price = parseFloat(priceText.replace('$', '').replace(',', ''));
            const discountBadge = row.cells[5]?.querySelector('.discount-badge');
            if (discountBadge) {
                const discountPercent = parseFloat(discountBadge.textContent);
                totalDiscountAmount += price * (discountPercent / 100);
            }
        }
    });
    
    updateDiscountStats(total, withDiscount, totalDiscountAmount);
}

// Copy discount
function copyDiscount(productId, discountPercentage, startDate, endDate) {
    document.getElementById('copy_discount_percentage').value = discountPercentage;
    document.getElementById('copy_start_date').value = startDate || '';
    document.getElementById('copy_end_date').value = endDate || '';
    
    let info = '<strong>' + discountPercentage + '% OFF</strong>';
    if (startDate && endDate) {
        const start = new Date(startDate).toLocaleDateString();
        const end = new Date(endDate).toLocaleDateString();
        info += '<br><small>Valid: ' + start + ' - ' + end + '</small>';
    } else if (startDate) {
        const start = new Date(startDate).toLocaleDateString();
        info += '<br><small>Starts: ' + start + '</small>';
    } else if (endDate) {
        const end = new Date(endDate).toLocaleDateString();
        info += '<br><small>Ends: ' + end + '</small>';
    }
    
    document.getElementById('copy_discount_info').innerHTML = info;
    
    document.querySelectorAll('.copy-target-checkbox').forEach(cb => {
        cb.checked = false;
        if (parseInt(cb.value) === productId) {
            cb.disabled = true;
            cb.closest('.form-check').style.opacity = '0.5';
        } else {
            cb.disabled = false;
            cb.closest('.form-check').style.opacity = '1';
        }
    });
    
    openModal('copyDiscountModal');
}

// Select all copy targets
function selectAllCopyTargets() {
    document.querySelectorAll('.copy-target-checkbox:not(:disabled)').forEach(cb => {
        cb.checked = true;
    });
}

// Deselect all copy targets
function deselectAllCopyTargets() {
    document.querySelectorAll('.copy-target-checkbox').forEach(cb => {
        cb.checked = false;
    });
}

// Remove discount
function removeDiscount(productId) {
    if (confirm('Are you sure you want to remove this discount?')) {
        window.location.href = 'index.php?page=discount&remove_discount=' + productId;
    }
}

// Activate discount
function activateDiscount(productId) {
    if (confirm('Activate this discount? It will be valid starting now for 1 year.')) {
        window.location.href = 'index.php?page=discount&activate_discount=' + productId;
    }
}

// Deactivate discount
function deactivateDiscount(productId) {
    if (confirm('Deactivate this discount? Customers will no longer be able to use it.')) {
        window.location.href = 'index.php?page=discount&deactivate_discount=' + productId;
    }
}

// Toggle promotion status
function togglePromotion(promotionId) {
    const button = event.target.closest('button');
    const isActive = button.textContent.includes('Active') && !button.textContent.includes('Inactive');
    
    const message = isActive 
        ? 'Are you sure you want to DEACTIVATE this promotion?' 
        : 'Are you sure you want to ACTIVATE this promotion?';
    
    if (confirm(message)) {
        // Disable button to prevent double-click
        button.disabled = true;
        button.style.opacity = '0.5';
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        
        // Redirect to toggle
        window.location.href = 'index.php?page=discount&toggle_promotion=' + promotionId;
    }
}

// Delete promotion
function deletePromotion(promotionId) {
    if (confirm('Are you sure you want to delete this promotion? This cannot be undone.')) {
        window.location.href = 'index.php?page=discount&delete_promotion=' + promotionId;
    }
}

// Update discount fields based on type
function updateDiscountFields() {
    const discountType = document.getElementById('discount_type').value;
    const discountValueField = document.getElementById('discount_value_field');
    const maxDiscountField = document.getElementById('max_discount_field');
    const discountPrefix = document.getElementById('discount_prefix');
    const discountValue = document.getElementById('discount_value');
    
    if (discountType === 'percentage') {
        discountPrefix.textContent = '%';
        discountValue.max = '100';
        discountValueField.style.display = 'block';
        maxDiscountField.style.display = 'block';
    } else if (discountType === 'fixed_amount') {
        discountPrefix.textContent = 'RWF ';
        discountValue.removeAttribute('max');
        discountValueField.style.display = 'block';
        maxDiscountField.style.display = 'none';
    } else {
        discountValueField.style.display = 'none';
        maxDiscountField.style.display = 'none';
        discountValue.value = '0';
    }
}

// Toggle eligibility
function toggleEligibility() {
    const applyTo = document.getElementById('apply_to').value;
    const eligibilitySection = document.getElementById('eligibility_section');
    const productEligibility = document.getElementById('product_eligibility');
    const categoryEligibility = document.getElementById('category_eligibility');
    
    if (applyTo === 'all') {
        eligibilitySection.style.display = 'none';
    } else {
        eligibilitySection.style.display = 'block';
        if (applyTo === 'product') {
            productEligibility.style.display = 'block';
            categoryEligibility.style.display = 'none';
        } else if (applyTo === 'category') {
            productEligibility.style.display = 'none';
            categoryEligibility.style.display = 'block';
        }
    }
}

// Auto-uppercase promotion code
const promoCodeInput = document.getElementById('promo_code');
if (promoCodeInput) {
    promoCodeInput.addEventListener('input', function(e) {
        this.value = this.value.toUpperCase();
    });
}

// Collect eligible items before form submission
const promotionForm = document.querySelector('#addPromotionModal form');
if (promotionForm) {
    promotionForm.addEventListener('submit', function(e) {
        const applyTo = document.getElementById('apply_to').value;
        const eligibleItemsInput = document.getElementById('eligible_items');
        
        if (applyTo === 'product') {
            const selected = Array.from(document.getElementById('eligible_products').selectedOptions).map(o => o.value);
            eligibleItemsInput.value = selected.join(',');
        } else if (applyTo === 'category') {
            const selected = Array.from(document.getElementById('eligible_categories').selectedOptions).map(o => o.value);
            eligibleItemsInput.value = selected.join(',');
        } else {
            eligibleItemsInput.value = '';
        }
    });
}

// Update copy target products before submission
const copyForm = document.querySelector('#copyDiscountModal form');
if (copyForm) {
    copyForm.addEventListener('submit', function(e) {
        const selectedIds = Array.from(document.querySelectorAll('.copy-target-checkbox:checked')).map(cb => cb.value);
        if (selectedIds.length === 0) {
            e.preventDefault();
            alert('Please select at least one target product');
            return false;
        }
        document.getElementById('copy_target_products').value = selectedIds.join(',');
    });
}

// Modal events
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

// Auto-hide alerts
function initializeAlerts() {
    const alert = document.getElementById('alertMessage');
    if (alert) {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.3s';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 300);
        }, 5000);
    }
}

// Initialize DataTables
$(document).ready(function() {
    $('#discountTable').DataTable({
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
        searching: true,
        columnDefs: [
            { orderable: false, targets: [0, 1, 8] }
        ],
        language: {
            search: "Search products:",
            lengthMenu: "Show _MENU_ products",
            info: "Showing _START_ to _END_ of _TOTAL_ products",
            infoEmpty: "No products available",
            infoFiltered: "(filtered from _MAX_ total products)",
            zeroRecords: "No matching products found"
        }
    });
    
    $('#promotionTable').DataTable({
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
        searching: true,
        language: {
            search: "Search promotions:",
            lengthMenu: "Show _MENU_ promotions",
            info: "Showing _START_ to _END_ of _TOTAL_ promotions",
            infoEmpty: "No promotions available",
            infoFiltered: "(filtered from _MAX_ total promotions)",
            zeroRecords: "No matching promotions found"
        }
    });
});

// Initialize everything
document.addEventListener('DOMContentLoaded', function() {
    initializeTabs();
    initializeModalEvents();
    initializeAlerts();
    calculateInitialStats();
    
    const editDiscountInput = document.getElementById('edit_discount_percentage');
    if (editDiscountInput) {
        editDiscountInput.addEventListener('input', updateEditPreview);
    }
});
</script>

</body>
</html>