<?php
include 'db_connect.php';
function h($v)
{
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function money($n)
{
  return 'RWF' . number_format((float)$n, 2);
}
ob_start();
$action = $_GET['action'];
include 'admin_class.php';
$crud = new Action();

if ($action == 'login') {
  $login = $crud->login();
  if ($login)
    echo $login;
}
if ($action == 'login2') {
  $login = $crud->login2();
  if ($login)
    echo $login;
}
if ($action == 'logout') {
  $logout = $crud->logout();
  if ($logout)
    echo $logout;
}
if ($action == 'logout2') {
  $logout = $crud->logout2();
  if ($logout)
    echo $logout;
}
if ($action == 'save_user') {
  $save = $crud->save_user();
  if ($save)
    echo $save;
}
if ($action == 'delete_user') {
  $save = $crud->delete_user();
  if ($save)
    echo $save;
}
if ($action == 'signup') {
  $save = $crud->signup();
  if ($save)
    echo $save;
}
if ($action == "save_settings") {
  $save = $crud->save_settings();
  if ($save)
    echo $save;
}
if ($action == "save_art") {
  $save = $crud->save_art();
  if ($save)
    echo $save;
}
if ($action == "delete_art") {
  $save = $crud->delete_art();
  if ($save)
    echo $save;
}
if ($action == "update_order") {
  $save = $crud->update_order();
  if ($save)
    echo $save;
}
if ($action == "delete_order") {
  $save = $crud->delete_order();
  if ($save)
    echo $save;
}
if ($action == "save_event") {
  $save = $crud->save_event();
  if ($save)
    echo $save;
}
if ($action == "delete_event") {
  $save = $crud->delete_event();
  if ($save)
    echo $save;
}
if ($action == "save_artist") {
  $save = $crud->save_artist();
  if ($save)
    echo $save;
}
if ($action == "delete_artist") {
  $save = $crud->delete_artist();
  if ($save)
    echo $save;
}
if ($action == "save_order") {
  $save = $crud->save_order();
  if ($save)
    echo $save;
}
if ($action == "save_art_fs") {
  $save = $crud->save_art_fs();
  if ($save)
    echo $save;
}
if ($action == "delete_art_fs") {
  $save = $crud->delete_art_fs();
  if ($save)
    echo $save;
}
if ($action == "get_pdetails") {
  $get = $crud->get_pdetails();
  if ($get)
    echo $get;
}
// if(isset($_POST['id']) && isset($_POST['status'])){
//   $id = (int)$_POST['id'];
//   $status = $_POST['status'];

//   $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
//   $stmt->bind_param('si', $status, $id);

//   if($stmt->execute()){
//     echo '1';
//   } else {
//     echo $stmt->error;
//   }
//   exit;
// }
//   // Send email to customer
//   if($action==='send_email' && isset($_POST['id'], $_POST['status'])){
//     $id = (int)$_POST['id'];
//     $status = $_POST['status'];
//     $order = $conn->query("SELECT customer_email, order_number FROM orders WHERE id=$id")->fetch_assoc();
//     if($order && filter_var($order['customer_email'], FILTER_VALIDATE_EMAIL)){
//       $to = $order['customer_email'];
//       $subject = "Order {$order['order_number']} updated to $status";
//       $message = "Dear customer,\n\nYour order {$order['order_number']} has been updated to status: $status.\n\nThank you.";
//       @mail($to,$subject,$message);
//     }
//     exit('1');
//   }
//   if($action==='refund_order' && isset($_POST['id'])){
//   $id = (int)$_POST['id'];
//   $stmt = $conn->prepare("UPDATE orders SET status='REFUNDED' WHERE id=?");
//   $stmt->bind_param('i',$id);
//   echo $stmt->execute() ? '1' : $stmt->error;

//   // Send email
//   $order = $conn->query("SELECT customer_email, order_number FROM orders WHERE id=$id")->fetch_assoc();
//   if($order){
//     @mail($order['customer_email'],"Order {$order['order_number']} refunded","Your order has been refunded.");
//   }
//   exit;
// }

// if($action==='view_order' && isset($_POST['id'])){
//   $id = (int)$_POST['id'];
//   $order = $conn->query("SELECT * FROM orders WHERE id=$id")->fetch_assoc();
//   $items = $conn->query("SELECT * FROM order_items WHERE order_id=$id");
//   echo "<strong>Order #: {$order['order_number']}</strong><br>";
//   echo "<strong>Customer:</strong> {$order['customer_name']}<br>";
//   echo "<strong>Status:</strong> {$order['status']}<br>";
//   echo "<strong>Items:</strong><ul>";
//   while($item=$items->fetch_assoc()){
//     echo "<li>{$item['product_name']} x {$item['quantity']}</li>";
//   }
//   echo "</ul>";
//   exit;
// }
if (isset($_GET['action'])) {
  $action = $_GET['action'];

  // Process order
  if ($action === 'process_order' && isset($_POST['id'], $_POST['status'])) {
    // $id=(int)$_POST['id'];
    // $status=$_POST['status'];
    // $payment_status = isset($_POST['payment_status'])?(int)$_POST['payment_status']:0;

    // $stmt=$conn->prepare("UPDATE orders SET status=?, payment_status=? WHERE id=?");
    // $stmt->bind_param('iii',$status,$payment_status,$id);
    // echo $stmt->execute()?'1':$stmt->error;
    $id = (int)$_POST['id'];
    $status = $_POST['status'] ?? '';
    $payment_status = isset($_POST['payment_status']) ? (int)$_POST['payment_status'] : 0;

    $stmt = $conn->prepare("UPDATE orders SET status=?, payment_status=? WHERE id=?");
    $stmt->bind_param("sii", $status, $payment_status, $id);
    echo $stmt->execute() ? "1" : "0";
    $stmt->close();
    exit;

    // Send email
    $order = $conn->query("SELECT customer_email, order_number FROM orders WHERE id=$id")->fetch_assoc();
    if ($order && filter_var($order['customer_email'], FILTER_VALIDATE_EMAIL)) {
      $to = $order['customer_email'];
      @mail($to, "Order {$order['order_number']} updated to $status", "Your order {$order['order_number']} status is now $status.");
    }
    exit;
  }

  // Refund order
  if ($action === 'refund_order' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("UPDATE orders SET status='REFUNDED' WHERE id=?");
    $stmt->bind_param('i', $id);
    echo $stmt->execute() ? '1' : $stmt->error;

    $order = $conn->query("SELECT customer_email, order_number FROM orders WHERE id=$id")->fetch_assoc();
    if ($order && filter_var($order['customer_email'], FILTER_VALIDATE_EMAIL)) {
      @mail($order['customer_email'], "Order {$order['order_number']} refunded", "Your order {$order['order_number']} has been refunded.");
    }
    exit;
  }

  // View order
  //   if($action==='view_order' && isset($_POST['id'])){
  //     $id=(int)$_POST['id'];
  //     $order=$conn->query("SELECT * FROM orders WHERE id=$id")->fetch_assoc();
  //     $items=$conn->query("SELECT * FROM order_items WHERE order_id=$id");
  //     echo "<strong>Order #: {$order['order_number']}</strong><br>";
  //     echo "<strong>Customer:</strong> {$order['customer_name']}<br>";
  //     echo "<strong>Status:</strong> {$order['status']}<br>";
  //     echo "<strong>Payment received:</strong> ".($order['payment_status']? 'Yes':'No')."<br>";
  //     echo "<strong>Items:</strong><ul>";
  //     while($item=$items->fetch_assoc()){
  //       echo "<li>{$item['product_name']} x {$item['quantity']}</li>";
  //     }
  //     echo "</ul>";
  //     exit;
  //   }
  if ($action === 'view_order' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    // Load order
    $stmt = $conn->prepare("
        SELECT id, order_number, customer_name, customer_email, customer_phone,
               shipping_address, total_amount, status, payment_status, created_at
        FROM orders WHERE id = ?
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $ord = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$ord) {
      echo "<div class='p-3 text-center text-danger'>Order not found</div>";
      exit;
    }

    // Load items with LEFT JOIN to products
    $stmt = $conn->prepare("
        SELECT oi.id, oi.product_id, oi.quantity, oi.price, oi.subtotal,
               p.product_name, p.product_image
        FROM order_items oi
        LEFT JOIN products p ON p.id = oi.product_id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    function stock_img()
    {
      static $imgs = [
        'https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?w=320&h=320&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1506629905607-d81034956c7e?w=320&h=320&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1583743089695-4b816a340f82?w=320&h=320&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=320&h=320&fit=crop&crop=center'
      ];
      return $imgs[array_rand($imgs)];
    }

    echo "<div style='max-height:400px;overflow:auto'>";
    echo "<strong>Order #" . h($ord['order_number']) . "</strong><br>";
    echo "<b>Customer:</b> " . h($ord['customer_name']) . " (" . h($ord['customer_email']) . ")<br>";
    echo "<b>Phone:</b> " . h($ord['customer_phone']) . "<br>";
    echo "<b>Shipping:</b> " . nl2br(h($ord['shipping_address'])) . "<br>";
    echo "<b>Status:</b> " . h(strtoupper($ord['status'])) . "<br>";
    echo "<b>Payment received:</b> " . ($ord['payment_status'] ? 'Yes' : 'No') . "<br><br>";

    echo "<table style='width:100%;border-collapse:collapse'>";
    echo "<thead><tr><th>#</th><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead><tbody>";

    if (!$items) {
      echo "<tr><td colspan='5' style='text-align:center;color:#64748b'>No items</td></tr>";
    } else {
      foreach ($items as $i => $it) {
        $name = $it['product_name'] ?? ('Product #' . $it['product_id']);
        $img  = $it['product_image'] ?? stock_img();
        $qty  = $it['quantity'] ?? 1;
        $price = $it['price'] ?? 0;
        $subtotal = $it['subtotal'] ?? 0;

        echo "<tr>";
        echo "<td>" . ($i + 1) . "</td>";
        echo "<td style='display:flex;align-items:center;gap:8px'>";
        echo "<img src='" . h($img) . "' style='width:48px;height:48px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0'>";
        echo "<div><b>" . h($name) . "</b><br>ID: " . (int)$it['product_id'] . "</div></td>";
        echo "<td>" . $qty . "</td>";
        echo "<td>" . money($price) . "</td>";
        echo "<td>" . money($subtotal) . "</td>";
        echo "</tr>";
      }
    }

    echo "</tbody></table>";
    echo "<br><b>Total:</b> " . money($ord['total_amount']);
    echo "</div>";
    exit;
  }


  // Send email manually (optional)
  if ($action === 'send_email' && isset($_POST['id'], $_POST['status'])) {
    $id = (int)$_POST['id'];
    $status = $_POST['status'];
    $order = $conn->query("SELECT customer_email, order_number FROM orders WHERE id=$id")->fetch_assoc();
    if ($order && filter_var($order['customer_email'], FILTER_VALIDATE_EMAIL)) {
      @mail($order['customer_email'], "Order {$order['order_number']} updated to $status", "Your order {$order['order_number']} status is now $status.");
    }
    exit('1');
  }
}
