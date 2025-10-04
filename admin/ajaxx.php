<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// include __DIR__ . '/db_connect.php';
include 'db_connect.php';

// Include PHPMailer (you need to install it first)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // If using Composer
// OR if you downloaded PHPMailer manually:
// require 'path/to/PHPMailer/src/Exception.php';
// require 'path/to/PHPMailer/src/PHPMailer.php';
// require 'path/to/PHPMailer/src/SMTP.php';
// require 'PHPMailer/src/Exception.php';
// require 'PHPMailer/src/PHPMailer.php';
// require 'PHPMailer/src/SMTP.php';
// require __DIR__ . '/../PHPMailer/src/Exception.php';
// require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
// require __DIR__ . '/../PHPMailer/src/SMTP.php';

require __DIR__ . '/../vendor/autoload.php';




$action = $_GET['action'] ?? '';

switch($action) {
    
    case 'update_payment_status':
        $id = (int)($_POST['id'] ?? 0);
        $payment_status = trim($_POST['payment_status'] ?? '');
        
        if ($id && $payment_status) {
            $stmt = $conn->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
            $stmt->bind_param('si', $payment_status, $id);
            
            if ($stmt->execute()) {
                sendStatusEmail($conn, $id, 'payment', $payment_status);
                echo '1';
            } else {
                echo 'Failed to update';
            }
            $stmt->close();
        } else {
            echo 'Invalid parameters';
        }
        break;

    case 'update_order_status':
        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        
        if ($id && $status) {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->bind_param('si', $status, $id);
            
            if ($stmt->execute()) {
                sendStatusEmail($conn, $id, 'order', $status);
                echo '1';
            } else {
                echo 'Failed to update';
            }
            $stmt->close();
        } else {
            echo 'Invalid parameters';
        }
        break;
    
    case 'view_order':
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("SELECT o.* FROM orders o WHERE o.id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
            $stmt->close();
            
            if ($order) {
                $stmt2 = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
                $stmt2->bind_param('i', $id);
                $stmt2->execute();
                $items_result = $stmt2->get_result();
                $items = $items_result->fetch_all(MYSQLI_ASSOC);
                $stmt2->close();
                
                echo "<h3>Order Details: " . htmlspecialchars($order['order_number']) . "</h3>";
                echo "<table style='width:100%; margin-top:20px;'>";
                echo "<tr><td style='width:40%;'><strong>Customer Name:</strong></td><td>" . htmlspecialchars($order['customer_name']) . "</td></tr>";
                echo "<tr><td><strong>Email:</strong></td><td>" . htmlspecialchars($order['customer_email']) . "</td></tr>";
                echo "<tr><td><strong>Phone:</strong></td><td>" . htmlspecialchars($order['customer_phone']) . "</td></tr>";
                echo "<tr><td><strong>Shipping Address:</strong></td><td>" . htmlspecialchars($order['shipping_address']) . "</td></tr>";
                echo "<tr><td><strong>Payment Status:</strong></td><td><strong>" . htmlspecialchars(ucwords($order['payment_status'])) . "</strong></td></tr>";
                echo "<tr><td><strong>Order Status:</strong></td><td><strong>" . htmlspecialchars(ucwords($order['status'])) . "</strong></td></tr>";
                echo "<tr><td><strong>Total Amount:</strong></td><td><strong>RWF " . number_format($order['total_amount'], 2) . "</strong></td></tr>";
                echo "<tr><td><strong>Order Date:</strong></td><td>" . htmlspecialchars($order['created_at']) . "</td></tr>";
                echo "</table>";
                
                if ($items) {
                    echo "<h4 style='margin-top:30px;'>Order Items:</h4>";
                    echo "<table style='width:100%;'>";
                    echo "<thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>";
                    echo "<tbody>";
                    foreach ($items as $item) {
                        $itemTotal = $item['quantity'] * $item['price'];
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($item['product_name']) . "</td>";
                        echo "<td>" . (int)$item['quantity'] . "</td>";
                        echo "<td>RWF " . number_format($item['price'], 2) . "</td>";
                        echo "<td>RWF " . number_format($itemTotal, 2) . "</td>";
                        echo "</tr>";
                    }
                    echo "</tbody></table>";
                }
            } else {
                echo "Order not found";
            }
        }
        break;
    
    default:
        echo 'Invalid action';
}

function sendStatusEmail($conn, $orderId, $type, $newStatus) {
    // Gmail SMTP Credentials
    $credentials = [
        "ishyigasoftware216@gmail.com",
        "amdozspatqhnqnsl"
    ];
    $host = "smtp.gmail.com";
    
    // Get order details from database
    $stmt = $conn->prepare("
        SELECT 
            order_number, 
            customer_name, 
            customer_email, 
            total_amount, 
            payment_status, 
            status 
        FROM orders 
        WHERE id = ?
    ");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if (!$order || !$order['customer_email']) {
        error_log("Email not sent: No customer_email found for order ID $orderId");
        return false;
    }
    
    $to = $order['customer_email'];
    $customerName = htmlspecialchars($order['customer_name']);
    $orderNumber = htmlspecialchars($order['order_number']);
    $statusLabel = ucwords($newStatus);
    $totalAmount = 'RWF ' . number_format($order['total_amount'], 2);
    
    if ($type === 'payment') {
        $subject = "Payment Status Update - Order $orderNumber";
        $statusType = "Payment Status";
    } else {
        $subject = "Order Status Update - Order $orderNumber";
        $statusType = "Order Status";
    }
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0;
                padding: 0;
                background-color: #f3f4f6;
            }
            .email-container { 
                max-width: 600px; 
                margin: 0 auto; 
                background: #ffffff;
            }
            .header { 
                background: linear-gradient(135deg, #3b82f6, #2563eb); 
                color: white; 
                padding: 30px 20px; 
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
                font-weight: 700;
            }
            .content { 
                background: #ffffff; 
                padding: 40px 30px;
            }
            .greeting {
                font-size: 16px;
                margin-bottom: 20px;
                color: #0f172a;
            }
            .status-badge { 
                display: inline-block; 
                padding: 12px 24px; 
                border-radius: 25px; 
                font-weight: bold; 
                margin: 15px 0;
                font-size: 16px;
            }
            .status-payment { 
                background: #dbeafe; 
                color: #1e40af; 
                border: 2px solid #3b82f6;
            }
            .status-order { 
                background: #d1fae5; 
                color: #065f46; 
                border: 2px solid #10b981;
            }
            .order-details { 
                background: #f9fafb; 
                padding: 24px; 
                border-radius: 12px; 
                margin: 25px 0;
                border: 1px solid #e5e7eb;
            }
            .order-info-row {
                display: flex;
                justify-content: space-between;
                padding: 12px 0;
                border-bottom: 1px solid #e5e7eb;
            }
            .order-info-row:last-child {
                border-bottom: none;
            }
            .label {
                color: #6b7280;
                font-weight: 500;
            }
            .value {
                color: #0f172a;
                font-weight: 600;
                text-align: right;
            }
            .message-text {
                color: #4b5563;
                font-size: 15px;
                line-height: 1.6;
                margin: 20px 0;
            }
            .footer { 
                text-align: center; 
                color: #6b7280; 
                padding: 30px 20px;
                font-size: 14px;
                background: #f9fafb;
                border-top: 1px solid #e5e7eb;
            }
            .footer p {
                margin: 8px 0;
            }
            .company-name {
                font-weight: 700;
                color: #0f172a;
                font-size: 16px;
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <h1>Order Status Update</h1>
            </div>
            <div class='content'>
                <p class='greeting'>Hello <strong>$customerName</strong>,</p>
                <p class='message-text'>We're writing to inform you that your order status has been updated. Here are the details:</p>
                
                <div class='order-details'>
                    <div class='order-info-row'>
                        <span class='label'>Order Number: </span>
                        <span class='value'>$orderNumber</span>
                    </div>
                    <div class='order-info-row'>
                        <span class='label'>Total Amount: </span>
                        <span class='value'>$totalAmount</span>
                    </div>
                    <div class='order-info-row'>
                        <span class='label'>$statusType</span>
                        <span class='value'>
                            <span class='status-badge status-$type'>$statusLabel</span>
                        </span>
                    </div>
                </div>
                
                <p class='message-text'>Thank you for choosing us! If you have any questions about your order, please don't hesitate to contact our customer support team.</p>
            </div>
            <div class='footer'>
                <p class='company-name'>Shade Beauty</p>
                <p>This is an automated notification email.</p>
                <p style='font-size:12px; color:#9ca3af;'>© 2025 Your Store. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Send email using PHPMailer with Gmail SMTP
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $credentials[0];
        $mail->Password   = $credentials[1];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom($credentials[0], 'Shade Beauty');
        $mail->addAddress($to, $customerName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        
        $mail->send();
        error_log("Email sent successfully to $to for order $orderNumber");
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>