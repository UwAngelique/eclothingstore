<?php
// order_view.php — lists items for a specific order (works in modal or standalone)
include __DIR__ . '/db_connect.php';
// function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
// function money($n){ return 'RWF' . number_format((float)$n, 2); }
// function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
// function money($n){ return '$' . number_format((float)$n, 2); }

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { echo "<div class='p-3 text-center text-danger'>Invalid order id</div>"; exit; }

// Load order
$stmt = $conn->prepare("
  SELECT id, order_number, customer_name, customer_email, customer_phone,
         shipping_address, total_amount, status, created_at
  FROM orders WHERE id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$ord = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$ord) { echo "<div class='p-3 text-center text-danger'>Order not found</div>"; exit; }

// Load items (+ product name/image if available)
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

function stock_img(){
  static $imgs = [
    'https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?w=320&h=320&fit=crop&crop=center',
    'https://images.unsplash.com/photo-1506629905607-d81034956c7e?w=320&h=320&fit=crop&crop=center',
    'https://images.unsplash.com/photo-1583743089695-4b816a340f82?w=320&h=320&fit=crop&crop=center',
    'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=320&h=320&fit=crop&crop=center'
  ];
  return $imgs[array_rand($imgs)];
}
?>
<div class="container-fluid">
  <style>
    .wrap{ width:100%; max-width:960px; margin:10px auto }
    .head{ border-bottom:1px solid #e2e8f0; padding-bottom:8px; margin-bottom:12px }
    .muted{ color:#64748b; font-size:12px }
    .grid{ display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px }
    .box{ border:1px dashed #e2e8f0; border-radius:12px; padding:10px }
    @media (max-width: 640px){ .grid{ grid-template-columns:1fr } }
    table{ width:100%; border-collapse:separate; border-spacing:0 8px }
    thead th{ font:700 12px system-ui; text-transform:uppercase; letter-spacing:.3px; color:#64748b; padding:0 8px 6px; border-bottom:1px solid #e2e8f0 }
    tbody tr{ background:#fff; border:1px solid #e2e8f0; border-radius:10px }
    td{ padding:10px 8px; vertical-align:middle }
    .nums{ font-variant-numeric:tabular-nums; white-space:nowrap }
    .thumb{ width:48px; height:48px; object-fit:cover; border-radius:8px; border:1px solid #e2e8f0 }
    .total-row th, .total-row td{ border-top:1px solid #e2e8f0; padding-top:10px }
  </style>

  <div class="wrap">
    <div class="head">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap">
        <div>
          <div style="font:800 18px system-ui">Order <?= h($ord['order_number']) ?></div>
          <div class="muted">Placed: <?= h($ord['created_at']) ?> • Status: <b><?= h(strtolower($ord['status'])) ?></b></div>
        </div>
        <button type="button" onclick="window.print()" style="border:1px solid #e2e8f0;background:#fff;border-radius:8px;padding:8px 10px;cursor:pointer">Print</button>
      </div>
    </div>

    <div class="grid">
      <div class="box">
        <div class="muted">Customer</div>
        <div><b><?= h($ord['customer_name']) ?></b></div>
        <div><?= h($ord['customer_email'] ?: '-') ?></div>
        <div><?= h($ord['customer_phone'] ?: '-') ?></div>
      </div>
      <div class="box">
        <div class="muted">Shipping Address</div>
        <div><?= nl2br(h($ord['shipping_address'] ?: '-')) ?></div>
      </div>
    </div>

    <div>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Product</th>
            <th class="nums">Qty</th>
            <th class="nums">Price</th>
            <th class="nums">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$items): ?>
            <tr><td colspan="5" style="padding:14px;text-align:center;color:#64748b">No items in this order.</td></tr>
          <?php else: foreach ($items as $i => $it): 
            $name = $it['product_name'] ?: ('Product #'.$it['product_id']);
            $img  = $it['product_image'] ?: stock_img();
          ?>
            <tr>
              <td class="nums"><?= $i+1 ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:8px">
                  <img class="thumb" src="<?= h($img) ?>" alt="">
                  <div>
                    <div><b><?= h($name) ?></b></div>
                    <div class="muted">ID: <?= (int)$it['product_id'] ?></div>
                  </div>
                </div>
              </td>
              <td class="nums"><?= (int)$it['quantity'] ?></td>
              <td class="nums"><?= money($it['price']) ?></td>
              <td class="nums"><b><?= money($it['subtotal']) ?></b></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
        <?php if ($items): ?>
        <tfoot>
          <tr class="total-row">
            <th colspan="4" style="text-align:right">Order Total</th>
            <th class="nums"><?= money($ord['total_amount']) ?></th>
          </tr>
        </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>
</div>
