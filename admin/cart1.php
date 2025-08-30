<?php
// cart.php

// ---- Debug for development (remove in production) ----
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Start session FIRST
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . '/db_connect.php';

// ---------- helpers ----------
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($n){ return '$' . number_format((float)$n, 2); }

// Random nice fashion image if product image is missing
function stock_img(): string {
    static $imgs = [
        'https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?w=600&h=400&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1506629905607-d81034956c7e?w=600&h=400&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1583743089695-4b816a340f82?w=600&h=400&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=600&h=400&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=600&h=400&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=600&h=400&fit=crop&crop=center',
    ];
    return $imgs[array_rand($imgs)];
}

// ----- AJAX actions -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    // 1) Add to cart
    if ($action === 'add_to_cart') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $qty       = max(1, (int)($_POST['quantity'] ?? 1));
        $sid       = session_id();

        if ($productId <= 0) {
            echo json_encode(['success'=>false,'message'=>'Invalid product']); exit;
        }

        // Make sure product exists (active or not—you can enforce status='active' if you prefer)
        $stmt = $conn->prepare("SELECT id, product_name FROM products WHERE id=?");
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $p = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$p) {
            echo json_encode(['success'=>false,'message'=>'Product not found']); exit;
        }

        // If already in cart -> increase qty
        $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE session_id=? AND product_id=?");
        $stmt->bind_param('si', $sid, $productId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $newQty = (int)$row['quantity'] + $qty;
            $stmt = $conn->prepare("UPDATE cart SET quantity=?, updated_at=NOW() WHERE id=?");
            $stmt->bind_param('ii', $newQty, $row['id']);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO cart (product_id, session_id, quantity, created_at, updated_at) VALUES (?,?,?,NOW(),NOW())");
            $stmt->bind_param('isi', $productId, $sid, $qty);
            $stmt->execute();
            $stmt->close();
        }

        // Updated count
        $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) AS total FROM cart WHERE session_id=?");
        $stmt->bind_param('s', $sid);
        $stmt->execute();
        $cartCount = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        echo json_encode(['success'=>true,'message'=>'Added to cart','cart_count'=>$cartCount,'product_name'=>$p['product_name']]); exit;
    }

    // 2) Get cart (FIX: LEFT JOIN + compute price/subtotal here)
    if ($action === 'get_cart') {
        $sid = session_id();
        $stmt = $conn->prepare("
            SELECT
                c.id, c.quantity,
                p.id AS product_id,
                p.product_name,
                p.category,
                p.price,
                p.sale_price,
                p.product_image
            FROM cart c
            LEFT JOIN products p ON p.id = c.product_id
            WHERE c.session_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->bind_param('s', $sid);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $items = [];
        $total = 0.0;

        foreach ($rows as $r) {
            $qty   = (int)$r['quantity'];
            $price = 0.0;

            // choose unit price safely
            if (isset($r['sale_price']) && $r['sale_price'] !== null && (float)$r['sale_price'] > 0 && (float)$r['price'] > 0 && (float)$r['sale_price'] < (float)$r['price']) {
                $price = (float)$r['sale_price'];
            } elseif (isset($r['price']) && $r['price'] !== null) {
                $price = (float)$r['price'];
            }

            $subtotal = $price * $qty;
            $total   += $subtotal;

            $items[] = [
                'id'            => (int)$r['id'],
                'product_id'    => $r['product_id'] ? (int)$r['product_id'] : null,
                'product_name'  => $r['product_name'] ?: '(Unavailable product)',
                'category'      => $r['category'] ?? '',
                'quantity'      => $qty,
                'price'         => $price,
                'subtotal'      => $subtotal,
                'product_image' => $r['product_image'] ?: stock_img(),
            ];
        }

        echo json_encode(['success'=>true,'items'=>$items,'total'=>$total,'count'=>array_sum(array_column($items,'quantity'))]); exit;
    }

    // 3) Update cart line (qty or delete)
    if ($action === 'update_cart') {
        $sid    = session_id();
        $cartId = (int)($_POST['cart_id'] ?? 0);
        $qty    = (int)($_POST['quantity'] ?? 0);

        if ($cartId <= 0) { echo json_encode(['success'=>false]); exit; }

        if ($qty <= 0) {
            $stmt = $conn->prepare("DELETE FROM cart WHERE id=? AND session_id=?");
            $stmt->bind_param('is', $cartId, $sid);
        } else {
            $stmt = $conn->prepare("UPDATE cart SET quantity=?, updated_at=NOW() WHERE id=? AND session_id=?");
            $stmt->bind_param('iis', $qty, $cartId, $sid);
        }
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) AS total FROM cart WHERE session_id=?");
        $stmt->bind_param('s', $sid);
        $stmt->execute();
        $count = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        echo json_encode(['success'=>true,'cart_count'=>$count,'message'=>$qty<=0?'Item removed':'Cart updated']); exit;
    }

    // 4) Simple search (optional)
    if ($action === 'search') {
        $q = trim($_POST['query'] ?? '');
        $sql = "SELECT id, product_name, category, price, sale_price, product_image FROM products WHERE status='active'";
        $types=''; $params=[];

        if ($q !== '') {
            $sql .= " AND (product_name LIKE ? OR category LIKE ?)";
            $qLike = "%$q%";
            $types .= 'ss'; $params[]=$qLike; $params[]=$qLike;
        }
        $sql .= " ORDER BY created_at DESC LIMIT 20";

        $stmt = $conn->prepare($sql);
        if ($params) { $stmt->bind_param($types, ...$params); }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        echo json_encode(['success'=>true,'products'=>$rows]); exit;
    }

    // 5) Checkout (creates an order + order_items then clears cart)
    if ($action === 'checkout') {
        $sid = session_id();
        $name  = trim($_POST['customer_name'] ?? 'Guest');
        $email = trim($_POST['customer_email'] ?? '');
        $phone = trim($_POST['customer_phone'] ?? '');
        $addr  = trim($_POST['shipping_address'] ?? '');

        // Pull cart with product pricing
        $stmt = $conn->prepare("
            SELECT c.product_id, c.quantity, p.price, p.sale_price
            FROM cart c
            LEFT JOIN products p ON p.id = c.product_id
            WHERE c.session_id = ?
        ");
        $stmt->bind_param('s', $sid);
        $stmt->execute();
        $lines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (!$lines) { echo json_encode(['success'=>false,'message'=>'Cart is empty']); exit; }

        $total = 0.0;
        $prepared = [];
        foreach ($lines as $l) {
            if (!$l['product_id']) { continue; } // skip missing products
            $price = 0.0;
            if ($l['sale_price'] !== null && (float)$l['sale_price'] > 0 && (float)$l['price'] > 0 && (float)$l['sale_price'] < (float)$l['price']) {
                $price = (float)$l['sale_price'];
            } else {
                $price = (float)($l['price'] ?? 0);
            }
            $qty = (int)$l['quantity'];
            $subtotal = $qty * $price;
            $total += $subtotal;
            $prepared[] = ['pid'=>(int)$l['product_id'], 'qty'=>$qty, 'price'=>$price, 'subtotal'=>$subtotal];
        }

        if (!$prepared) { echo json_encode(['success'=>false,'message'=>'No valid items to order']); exit; }

        try {
            $conn->begin_transaction();

            $orderNo = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid('',true)),0,6));
            $stmt = $conn->prepare("INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, shipping_address, total_amount, status, created_at) VALUES (?,?,?,?,?,?,'pending',NOW())");
            $stmt->bind_param('sssssd', $orderNo, $name, $email, $phone, $addr, $total);
            $stmt->execute();
            $orderId = $conn->insert_id;
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) VALUES (?,?,?,?,?)");
            foreach ($prepared as $it) {
                $stmt->bind_param('iiidd', $orderId, $it['pid'], $it['qty'], $it['price'], $it['subtotal']);
                $stmt->execute();
            }
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM cart WHERE session_id=?");
            $stmt->bind_param('s', $sid);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            echo json_encode(['success'=>true,'order_number'=>$orderNo,'order_id'=>$orderId,'total'=>$total]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success'=>false,'message'=>'Order failed: '.$e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['success'=>false,'message'=>'Unknown action']); exit;
}

// ---------- Data for initial page ----------
$sid = session_id();

// Cart badge count
$stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) AS total FROM cart WHERE session_id=?");
$stmt->bind_param('s', $sid);
$stmt->execute();
$cartCount = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// A small product grid (latest 12 active)
$stmt = $conn->prepare("SELECT id, product_name, category, price, sale_price, product_image FROM products WHERE status='active' ORDER BY created_at DESC LIMIT 12");
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shop • Demo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700;900&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
<style>
:root{
  --primary:#6b73ff; --primary2:#000dff;
  --ink:#0f172a; --muted:#64748b; --line:#e2e8f0; --bg:#f8fafc; --white:#fff; --danger:#ef4444; --ok:#10b981;
}
*{box-sizing:border-box} body{margin:0;font-family:Inter,system-ui,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background:linear-gradient(135deg,#eef2ff,#fafafa);}
.container{max-width:1200px;margin:0 auto;padding:0 20px}
header{position:sticky;top:0;background:rgba(255,255,255,.9);backdrop-filter:saturate(160%) blur(8px);border-bottom:1px solid var(--line);z-index:10}
.header-inner{display:flex;align-items:center;gap:16px;padding:14px 0}
.logo{font:900 26px Outfit,Inter;color:var(--ink);letter-spacing:.5px}
.search{flex:1;display:flex;background:#fff;border:1px solid var(--line);border-radius:14px;overflow:hidden}
.search input{flex:1;border:0;padding:12px 14px;font-size:15px;outline:none}
.search button{border:0;background:linear-gradient(90deg,var(--primary),var(--primary2));color:#fff;padding:0 14px;cursor:pointer}
.actions{display:flex;gap:10px}
.btn{position:relative;border:1px solid var(--line);background:#fff;padding:10px;border-radius:12px;cursor:pointer}
.badge{position:absolute;top:-6px;right:-6px;background:linear-gradient(90deg,#f093fb,#f5576c);color:#fff;font-size:12px;padding:3px 6px;border-radius:999px;font-weight:700}

.hero{padding:38px 0 14px}
h1{font:900 clamp(32px,5vw,54px)/1.05 Outfit;margin:0;background:linear-gradient(90deg,#111,#334);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.sub{color:var(--muted);margin-top:6px}

.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px;margin:24px 0 60px}
.card{background:#fff;border:1px solid var(--line);border-radius:18px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 4px 18px rgba(0,0,0,0.04)}
.card .img{height:170px;background:#f1f5f9;overflow:hidden}
.card img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .5s}
.card:hover img{transform:scale(1.08)}
.card .body{padding:14px 14px 16px}
.cat{font-size:11px;letter-spacing:.4px;color:#94a3b8;text-transform:uppercase}
.title{font-weight:700;margin:6px 0 8px;color:var(--ink)}
.prices{display:flex;align-items:center;gap:10px;margin-bottom:10px}
.price{font:700 20px Outfit;background:linear-gradient(90deg,var(--primary),var(--primary2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.was{color:#94a3b8;text-decoration:line-through}
.add{border:0;background:var(--ink);color:#fff;padding:10px 12px;border-radius:12px;cursor:pointer;font-weight:700;width:100%;display:flex;gap:8px;align-items:center;justify-content:center}
.add:disabled{opacity:.7;cursor:default}

.cart-modal{position:fixed;inset:0;display:none;background:rgba(15,23,42,.35);backdrop-filter:blur(2px)}
.cart-modal.show{display:block}
.cart-panel{position:absolute;top:0;right:0;height:100%;width:min(520px,100%);background:#fff;border-left:1px solid var(--line);display:flex;flex-direction:column}
.cart-head{display:flex;align-items:center;justify-content:space-between;padding:16px 18px;border-bottom:1px solid var(--line)}
.cart-items{flex:1;overflow:auto;padding:10px 14px}
.empty{display:grid;place-items:center;height:100%;color:#7c8696;text-align:center}
.row{display:flex;gap:12px;padding:12px 6px;border-bottom:1px solid var(--line)}
.row:last-child{border-bottom:0}
.thumb{width:68px;height:68px;border-radius:10px;object-fit:cover;border:1px solid var(--line)}
.info{flex:1}
.rtitle{font-weight:700;color:var(--ink);margin:0 0 4px}
.rcat{font-size:12px;color:#94a3b8;margin-bottom:8px}
.qty{display:flex;align-items:center;gap:8px}
.qbtn{width:28px;height:28px;border:1px solid var(--line);background:#fff;border-radius:8px;cursor:pointer}
.summary{border-top:1px solid var(--line);padding:14px 18px}
.total{display:flex;justify-content:space-between;font-weight:900;font-size:18px;margin-bottom:10px}
.checkout{width:100%;border:0;background:linear-gradient(90deg,var(--primary),var(--primary2));color:#fff;padding:12px;border-radius:12px;font-weight:800;cursor:pointer}

.toast{position:fixed;left:16px;bottom:16px;background:#fff;border:1px solid var(--line);padding:12px 14px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.08);display:none}
.toast.show{display:block}

@media (max-width:680px){ .header-inner{flex-wrap:wrap} .search{order:3;width:100%} }
</style>

</head>
<body>

<header>
  <div class="container header-inner">
    <div class="logo">Anon</div>
    <div class="search">
      <input id="q" placeholder="Search products..." autocomplete="off">
      <button id="searchBtn" title="Search"><ion-icon name="search-outline"></ion-icon></button>
    </div>
    <div class="actions">
      <button class="btn" id="cartBtn" title="Cart">
        <ion-icon name="bag-handle-outline"></ion-icon>
        <span class="badge" id="cartCount"><?= $cartCount ?></span>
      </button>
    </div>
  </div>
</header>

<section class="container hero">
  <h1>Find your next favorite.</h1>
  <p class="sub">Beautiful, modern styles at friendly prices.</p>
</section>

<main class="container">
  <div class="grid" id="grid">
    <?php foreach ($products as $p): ?>
      <?php
        $hasSale = ($p['sale_price'] !== null && (float)$p['sale_price'] > 0 && (float)$p['sale_price'] < (float)$p['price']);
        $img = $p['product_image'] ?: stock_img();
      ?>
      <article class="card">
        <div class="img"><img src="<?= h($img) ?>" alt="<?= h($p['product_name']) ?>"></div>
        <div class="body">
          <div class="cat"><?= h($p['category'] ?: 'General') ?></div>
          <div class="title"><?= h($p['product_name']) ?></div>
          <div class="prices">
            <div class="price"><?= $hasSale ? money($p['sale_price']) : money($p['price']) ?></div>
            <?php if ($hasSale): ?><div class="was"><?= money($p['price']) ?></div><?php endif; ?>
          </div>
          <button class="add" data-id="<?= (int)$p['id'] ?>">
            <ion-icon name="bag-add-outline"></ion-icon> Add to cart
          </button>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</main>

<!-- CART -->
<div class="cart-modal" id="cartModal">
  <div class="cart-panel">
    <div class="cart-head">
      <strong style="font:900 18px Outfit">Shopping Cart</strong>
      <button class="btn" id="cartClose" title="Close"><ion-icon name="close-outline"></ion-icon></button>
    </div>
    <div class="cart-items" id="cartItems">
      <div class="empty">
        <div>
          <ion-icon name="bag-outline" style="font-size:48px"></ion-icon>
          <div>Your cart is empty</div>
          <small>Add some products to get started</small>
        </div>
      </div>
    </div>
    <div class="summary" id="cartSummary" style="display:none">
      <div class="total"><span>Total</span><span id="cartTotal">$0.00</span></div>
      <button class="checkout" id="checkoutBtn"><ion-icon name="card-outline"></ion-icon>&nbsp;Checkout</button>
    </div>
  </div>
</div>

<!-- simple checkout form popup -->
<div class="toast" id="toast"></div>

<script>
// ---- utilities ----
function showToast(msg){ const t=document.getElementById('toast'); t.textContent=msg; t.classList.add('show'); setTimeout(()=>t.classList.remove('show'),2000); }

// ---- add to cart buttons ----
document.querySelectorAll('.add').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    const id = btn.dataset.id;
    btn.disabled = true; const prev = btn.innerHTML; btn.innerHTML = '<ion-icon name="sync-outline" style="animation:spin 1s linear infinite"></ion-icon> Adding...';
    const fd = new FormData(); fd.append('action','add_to_cart'); fd.append('product_id',id); fd.append('quantity','1');
    fetch(location.href,{method:'POST',body:fd,credentials:'same-origin'})
      .then(r=>r.json()).then(d=>{
        btn.disabled=false; btn.innerHTML=prev;
        if(d.success){
          document.getElementById('cartCount').textContent = d.cart_count;
          showToast('Added: '+d.product_name);
        }else{ showToast(d.message||'Error'); }
      }).catch(()=>{
        btn.disabled=false; btn.innerHTML=prev; showToast('Network error');
      });
  });
});

// ---- cart open/close ----
const cartBtn   = document.getElementById('cartBtn');
const cartModal = document.getElementById('cartModal');
document.getElementById('cartClose').addEventListener('click',()=>cartModal.classList.remove('show'));
cartModal.addEventListener('click',e=>{ if(e.target===cartModal) cartModal.classList.remove('show'); });
cartBtn.addEventListener('click', ()=>{ loadCart(); cartModal.classList.add('show'); });

// ---- load & render cart (uses server-prepared price/subtotal) ----
function loadCart(){
  const fd = new FormData(); fd.append('action','get_cart');
  fetch(location.href,{method:'POST',body:fd,credentials:'same-origin'})
    .then(r=>r.json()).then(d=>{
      if(!d.success){ showToast('Could not load cart'); return; }
      renderCart(d.items, d.total);
    }).catch(()=> showToast('Network error'));
}

function renderCart(items,total){
  const wrap = document.getElementById('cartItems');
  const summary = document.getElementById('cartSummary');
  const totalEl = document.getElementById('cartTotal');

  if(!items.length){
    wrap.innerHTML = `<div class="empty">
      <div><ion-icon name="bag-outline" style="font-size:48px"></ion-icon>
      <div>Your cart is empty</div><small>Add some products to get started</small></div></div>`;
    summary.style.display='none'; return;
  }

  wrap.innerHTML = items.map(it=>`
    <div class="row" data-id="${it.id}">
      <img class="thumb" src="${it.product_image}" alt="">
      <div class="info">
        <div class="rtitle">${it.product_name}</div>
        <div class="rcat">${it.category??''}</div>
        <div class="qty">
          <button class="qbtn" data-a="dec">-</button>
          <strong>${it.quantity}</strong>
          <button class="qbtn" data-a="inc">+</button>
          <button class="qbtn" data-a="del" title="Remove"><ion-icon name="trash-outline"></ion-icon></button>
        </div>
      </div>
      <div style="text-align:right;min-width:110px">
        <div style="font-weight:800">${money(it.subtotal)}</div>
        <small style="color:#64748b">${money(it.price)} each</small>
      </div>
    </div>
  `).join('');

  summary.style.display='block';
  totalEl.textContent = money(total);

  // bind qty buttons
  wrap.querySelectorAll('.qbtn').forEach(b=>{
    b.addEventListener('click', e=>{
      const row = e.currentTarget.closest('.row');
      const id  = row.dataset.id;
      const act = e.currentTarget.dataset.a;
      const qtyNow = parseInt(row.querySelector('strong').textContent,10);
      let newQty = qtyNow;
      if (act==='inc') newQty = qtyNow+1;
      if (act==='dec') newQty = Math.max(0, qtyNow-1);
      if (act==='del') newQty = 0;
      updateCart(id, newQty);
    });
  });
}

function money(n){ return '$'+Number(n).toFixed(2); }

function updateCart(cartId, qty){
  const fd=new FormData(); fd.append('action','update_cart'); fd.append('cart_id',cartId); fd.append('quantity',qty);
  fetch(location.href,{method:'POST',body:fd,credentials:'same-origin'})
    .then(r=>r.json()).then(d=>{
      if(d.success){
        document.getElementById('cartCount').textContent = d.cart_count;
        loadCart();
        showToast(d.message);
      }
    }).catch(()=>showToast('Network error'));
}

// ---- checkout (very simple demo) ----
document.getElementById('checkoutBtn').addEventListener('click', ()=>{
  const name = prompt('Your full name:','Angelique');
  if(!name) return;
  const email = prompt('Email:','angelique@example.com');
  const addr  = prompt('Shipping address:','221B Baker Street');
  const fd = new FormData();
  fd.append('action','checkout');
  fd.append('customer_name',name);
  fd.append('customer_email',email||'');
  fd.append('customer_phone','');
  fd.append('shipping_address',addr||'');

  fetch(location.href,{method:'POST',body:fd,credentials:'same-origin'})
    .then(r=>r.json()).then(d=>{
      if(d.success){
        showToast('Order '+d.order_number+' placed!');
        document.getElementById('cartCount').textContent = '0';
        loadCart();
      }else{
        showToast(d.message || 'Checkout failed');
      }
    }).catch(()=>showToast('Network error'));
});

// ---- simple search ----
document.getElementById('searchBtn').addEventListener('click', doSearch);
document.getElementById('q').addEventListener('keydown', e=>{ if(e.key==='Enter'){e.preventDefault();doSearch();}});
function doSearch(){
  const q = document.getElementById('q').value.trim();
  if(q.length<2){ showToast('Type at least 2 letters'); return; }
  const fd=new FormData(); fd.append('action','search'); fd.append('query',q);
  fetch(location.href,{method:'POST',body:fd,credentials:'same-origin'})
    .then(r=>r.json()).then(d=>{
      if(!d.success){ showToast('Search failed'); return; }
      const grid = document.getElementById('grid');
      grid.innerHTML = d.products.map(p=>{
        const hasSale = p.sale_price && Number(p.sale_price) > 0 && Number(p.sale_price) < Number(p.price);
        const price   = hasSale ? Number(p.sale_price) : Number(p.price);
        const was     = hasSale ? `<div class="was">${money(p.price)}</div>` : '';
        const img     = p.product_image || '<?= h(stock_img()) ?>';
        return `
          <article class="card">
            <div class="img"><img src="${img}" alt=""></div>
            <div class="body">
              <div class="cat">${p.category ?? 'General'}</div>
              <div class="title">${p.product_name}</div>
              <div class="prices"><div class="price">${money(price)}</div>${was}</div>
              <button class="add" data-id="${p.id}"><ion-icon name="bag-add-outline"></ion-icon> Add to cart</button>
            </div>
          </article>`;
      }).join('');
      // rebind add buttons
      document.querySelectorAll('.add').forEach(btn=>{
        btn.addEventListener('click', ()=>{
          const id=btn.dataset.id;
          const fd=new FormData(); fd.append('action','add_to_cart'); fd.append('product_id',id); fd.append('quantity','1');
          fetch(location.href,{method:'POST',body:fd,credentials:'same-origin'})
            .then(r=>r.json()).then(d=>{ if(d.success){ document.getElementById('cartCount').textContent=d.cart_count; showToast('Added'); }});
        });
      });
    });
}
</script>
</body>
</html>
