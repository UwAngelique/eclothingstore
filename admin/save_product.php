<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json'); // always return JSON

$response = ["success" => false, "message" => "Unknown error"];

// Load DB connection
include 'db.php'; // make sure this defines $conn (mysqli)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ✅ Validate required fields
    $required_fields = ['product_name', 'sku', 'category', 'price', 'quantity'];
    $errors = [];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
        }
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    // ✅ Handle file upload
    $imagePath = null;
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . "/uploads/products/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExtension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . "." . $fileExtension;
        $targetFile = $uploadDir . $fileName;

        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array(strtolower($fileExtension), $allowedTypes)) {
            echo json_encode(['success' => false, 'error' => 'Invalid image format']);
            exit;
        }

        if ($_FILES['product_image']['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'Image size too large']);
            exit;
        }

        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $targetFile)) {
            $imagePath = "uploads/products/" . $fileName;
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to upload image']);
            exit;
        }
    }

    // ✅ Process arrays
    $sizes  = isset($_POST['sizes']) ? json_encode(explode(',', $_POST['sizes'])) : null;
    $colors = isset($_POST['colors']) ? json_encode(explode(',', $_POST['colors'])) : null;

    // ✅ Insert into DB
    $stmt = $conn->prepare("INSERT INTO products (
        product_name, sku, category, brand, description, price, sale_price, cost_price,
        quantity, min_stock, status, sizes, colors, product_image, tags, weight, material,
        season, is_featured, track_inventory, allow_backorder
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $is_featured     = isset($_POST['is_featured']) ? 1 : 0;
    $track_inventory = isset($_POST['track_inventory']) ? 1 : 0;
    $allow_backorder = isset($_POST['allow_backorder']) ? 1 : 0;

    $stmt->bind_param(
        "sssssddiisissssssiii",
        $_POST['product_name'],
        $_POST['sku'],
        $_POST['category'],
        $_POST['brand'],
        $_POST['description'],
        $_POST['price'],
        $_POST['sale_price'],
        $_POST['cost_price'],
        $_POST['quantity'],
        $_POST['min_stock'],
        $_POST['status'],
        $sizes,
        $colors,
        $imagePath,
        $_POST['tags'],
        $_POST['weight'],
        $_POST['material'],
        $_POST['season'],
        $is_featured,
        $track_inventory,
        $allow_backorder
    );

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Product added successfully";
        $response['product_id'] = $stmt->insert_id;
        $response['file'] = $imagePath;
    } else {
        if ($conn->errno === 1062) {
            $response['error'] = "SKU already exists";
        } else {
            $response['error'] = "Database error: " . $stmt->error;
        }
    }

    $stmt->close();
    $conn->close();
}

echo json_encode($response);
exit;
?>
