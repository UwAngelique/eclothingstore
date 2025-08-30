<?php
ini_set('display_errors', 0);          // hide errors from output
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
include 'db_connect.php'; // your DB connection

$response = ["success" => false, "message" => "Unknown error"];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim($_POST['category_name'] ?? '');
    $status = $_POST['status'] ?? 'inactive';
    $description = $_POST['description'] ?? '';
    $imagePath = null;

    // Validate required fields
    if ($category_name === '') {
        $response['message'] = "Category name is required";
        echo json_encode($response);
        exit;
    }

    // Handle image upload
    if (!empty($_FILES['category_image']['name'])) {
        $uploadDir = __DIR__ . "/uploads/categories/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $imageName = time() . "_" . basename($_FILES['category_image']['name']);
        $targetFile = $uploadDir . $imageName;

        // Validate image
        $check = getimagesize($_FILES['category_image']['tmp_name']);
        if ($check === false) {
            $response['message'] = "Uploaded file is not a valid image";
            echo json_encode($response);
            exit;
        }

        // Move uploaded file
        if (move_uploaded_file($_FILES['category_image']['tmp_name'], $targetFile)) {
            $imagePath = "uploads/categories/" . $imageName; // relative path for DB
        } else {
            $response['message'] = "Failed to move uploaded file";
            $response['error'] = $_FILES['category_image']['error'];
            echo json_encode($response);
            exit;
        }
    }

    // Insert into DB
    $stmt = $conn->prepare("INSERT INTO categories (category_name, status, description, category_image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $category_name, $status, $description, $imagePath);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Category added successfully";
        $response['file'] = $imagePath;
    } else {
        $response['message'] = "Database error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}

echo json_encode($response);
exit;
?>
