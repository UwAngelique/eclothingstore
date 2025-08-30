<?php
// orders_list.php
include __DIR__ . '/db_connect.php';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($n){ return '$' . number_format((float)$n, 2); }

// Get orders with count of items
$sql = "
  SELECT 
    o.id,
    o.order_number,
    o.customer_name,
    o.customer_email,
    o.customer_phone,
    o.shipping_address,
    o.total_amount,
    o.status,
    o.created_at,
    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS items_count
  FROM orders o
  ORDER BY o.id DESC
";
$res  = $conn->query($sql);
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

function status_pill($s){
  $s = strtolower((string)$s);
  $map = [
    'pending'   => 'bg-pending',
    'paid'      => 'bg-paid',
    'shipped'   => 'bg-shipped',
    'cancelled' => 'bg-cancelled',
    'refunded'  => 'bg-refunded'
  ];
  $cls = $map[$s] ?? 'bg-unknown';
  return '<span class="pill '.$cls.'">'.h($s).'</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Orders</title>
<style>
  :root{
    --ink:#0f172a; --muted:#64748b; --line:#e2e8f0; --bg:#f8fafc; --white:#fff;
    --grad1: linear-gradient(135deg,#6366f1,#8b5cf6);
    --grad2: linear-gradient(135deg,#06b6d4,#3b82f6);
    --shadow: 0 10px 30px rgba(2,6,23,.08);
  }
  *{box-sizing:border-box}
  body{margin:0; font-family:system-ui,-apple-system,Segoe UI,Roboto,Inter,Arial,sans-serif; color:var(--ink); background:linear-gradient(180deg,#fafafa,#fff)}
  .wrap{ width:100%; max-width:1200px; margin:24px auto; padding:0 16px }
  /* 50% wide on large screens */
  @media (min-width: 992px){ .wrap.narrow{ width:50%; } }

  .card{ border:1px solid var(--line); border-radius:18px; background:#fff; box-shadow:var(--shadow); overflow:hidden }
  .card-header{
    padding:16px 18px; display:flex; gap:10px; align-items:center; justify-content:space-between;
    border-bottom:1px solid var(--line);
    background:radial-gradient(240px 240px at -5% -30%, rgba(99,102,241,.20), transparent 70%),
               radial-gradient(220px 220px at 110% 10%, rgba(59,130,246,.18), transparent 70%),
               #fff;
  }
  .card-title{ margin:0; font:800 20px/1.1 system-ui }
  .toolbar{ display:flex; gap:8px; flex-wrap:wrap }
  .inp,.sel,.btn{
    border:1px solid var(--line); background:#fff; border-radius:12px; padding:10px 12px; font:600 14px/1.2 system-ui; color:var(--ink)
  }
  .btn{ cursor:pointer; transition:transform .15s }
  .btn:hover{ transform:translateY(-1px) }
  .btn-primary{ background:var(--grad1); color:#fff; border:0 }

  .card-body{ padding:16px 18px 22px }
  table{ width:100%; border-collapse:separate; border-spacing:0 10px }
  thead th{
    font:700 12px/1.2 system-ui; text-transform:uppercase; letter-spacing:.3px; color:var(--muted);
    padding:0 10px 6px; border-bottom:1px solid var(--line)
  }
  tbody tr{
    background:#fff; border:1px solid var(--line); border-radius:14px; box-shadow:0 6px 18px rgba(2,6,23,.04);
    transition:.18s transform, .18s box-shadow;
  }
  tbody tr:hover{ transform:translateY(-2px); box-shadow:0 12px 26px rgba(2,6,23,.08) }
  td{ padding:12px 10px; vertical-align:middle }
  .mini{ color:var(--muted); font-size:12px }
  .nums{ font-variant-numeric:tabular-nums; white-space:nowrap }
  .pill{ display:inline-block; padding:6px 10px; border-radius:999px; font:800 11px/1 system-ui; text-transform:uppercase; letter-spacing:.3px; border:1px solid var(--line) }
  .bg-pending{background:#f8fafc}
  .bg-paid{background:#eef2ff}
  .bg-shipped{background:#ecfeff}
  .bg-cancelled{background:#fff1f2}
  .bg-refunded{background:#fffbeb}
  .bg-unknown{background:#f1f5f9}
  .actions{ display:flex; gap:8px; justify-content:flex-end }
  .chip{
    display:inline-flex; align-items:center; gap:8px; padding:10px 12px; border-radius:12px;
    border:1px solid var(--line); background:#fff; cursor:pointer; transition:.18s
  }
  .chip:hover{ transform:translateY(-1px) }
  .chip.view{ background:var(--grad2); color:#fff; border:0 }
  .chip.del{ color:#ef4444; border-color:#fecaca; background:#fff }
  @media (max-width: 820px){ .hide-sm{ display:none } }
</style>
</head>
<body>
  <div class="wrap narrow">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Orders</h3>
        <div class="toolbar">
          <input id="q" class="inp" placeholder="Search (order #, name, email)">
          <select id="status" class="sel">
            <option value="">All statuses</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="shipped">Shipped</option>
            <option value="cancelled">Cancelled</option>
            <option value="refunded">Refunded</option>
          </select>
          <button id="clear" class="btn">Clear</button>
        </div>
      </div>
      <div class="card-body">
        <table id="ordersTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Order</th>
              <th>Customer</th>
              <th class="hide-sm">Items</th>
              <th class="hide-sm">Placed</th>
              <th class="hide-sm">Total</th>
              <th>Status</th>
              <th style="text-align:right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$rows): ?>
              <tr><td colspan="8" style="padding:18px;text-align:center;color:#64748b">No orders found.</td></tr>
            <?php else: $i=1; foreach ($rows as $r): ?>
              <tr data-row
                  data-status="<?= h(strtolower($r['status'])) ?>"
                  data-hay="<?= h(strtolower(trim($r['order_number'].' '.$r['customer_name'].' '.$r['customer_email']))) ?>">
                <td class="nums"><?= (int)$i++ ?></td>
                <td>
                  <div><b><?= h($r['order_number']) ?></b></div>
                  <div class="mini">#<?= (int)$r['id'] ?></div>
                </td>
                <td>
                  <div><b><?= h($r['customer_name']) ?></b></div>
                  <div class="mini"><?= h($r['customer_email'] ?: '') ?></div>
                </td>
                <td class="hide-sm nums"><?= (int)$r['items_count'] ?></td>
                <td class="hide-sm nums"><?= h($r['created_at']) ?></td>
                <td class="hide-sm nums"><b><?= money($r['total_amount']) ?></b></td>
                <td><?= status_pill($r['status']) ?></td>
                <td>
                  <div class="actions">
                    <button type="button" class="chip view view_order" data-id="<?= (int)$r['id'] ?>">View</button>
                    <button type="button" class="chip del delete_order" data-id="<?= (int)$r['id'] ?>">Delete</button>
                  </div>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

<script>
// ---- Minimal JS: works without jQuery/DataTables ----

// Delegated clicks for dynamic/future rows
document.addEventListener('click', function(e){
  const viewBtn = e.target.closest('.view_order');
  const delBtn  = e.target.closest('.delete_order');

  if (viewBtn) {
    const id = viewBtn.getAttribute('data-id');
    if (!id) { toast('Missing order id'); return; }
    // Use uni_modal if available, else navigate
    if (typeof window.uni_modal === 'function') {
      window.uni_modal('Order Details', 'order_view.php?id=' + encodeURIComponent(id), 'mid-large');
    } else {
      window.location.href = 'order_view.php?id=' + encodeURIComponent(id);
    }
  }

  if (delBtn) {
    const id = delBtn.getAttribute('data-id');
    if (!id) { toast('Missing order id'); return; }
    if (typeof window._conf === 'function') {
      window._conf('Delete this order? This cannot be undone.', 'delete_order', [id]);
    } else {
      if (confirm('Delete this order? This cannot be undone.')) {
        delete_order(id);
      }
    }
  }
});

// Simple client-side search + status filter
const $q = document.getElementById('q');
const $st = document.getElementById('status');
const $clear = document.getElementById('clear');

function applyFilter(){
  const t = ($q.value || '').toLowerCase().trim();
  const s = ($st.value || '').toLowerCase().trim();
  document.querySelectorAll('[data-row]').forEach(row=>{
    const hay = (row.getAttribute('data-hay') || '');
    const st  = (row.getAttribute('data-status') || '');
    const okT = !t || hay.indexOf(t) !== -1;
    const okS = !s || st === s;
    row.style.display = (okT && okS) ? '' : 'none';
  });
}
$q.addEventListener('input', applyFilter);
$st.addEventListener('change', applyFilter);
$clear.addEventListener('click', ()=>{ $q.value=''; $st.value=''; applyFilter(); });

// Delete AJAX (works with or without your helper functions)
function delete_order(id){
  if (typeof window.start_load === 'function') window.start_load();
  fetch('ajax.php?action=delete_order', {
    method:'POST',
    headers:{},
    body: new URLSearchParams({id:String(id)})
  }).then(r=>r.text()).then(resp=>{
    if (typeof window.alert_toast === 'function') {
      if (resp === '1' || resp === 'OK') window.alert_toast('Order deleted','success');
      else window.alert_toast('Delete failed: '+resp,'danger');
    } else {
      if (resp === '1' || resp === 'OK') toast('Order deleted');
      else toast('Delete failed: ' + resp);
    }
    if (resp === '1' || resp === 'OK') setTimeout(()=>location.reload(), 800);
  }).catch(()=> toast('Network error'));
}

// Tiny toast fallback
let toastTimer;
function toast(msg){
  let t = document.getElementById('miniToast');
  if (!t) {
    t = document.createElement('div');
    t.id = 'miniToast';
    t.style.cssText = 'position:fixed;left:16px;bottom:16px;background:#0f172a;color:#fff;padding:10px 12px;border-radius:10px;z-index:9999';
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.style.display = 'block';
  clearTimeout(toastTimer);
  toastTimer = setTimeout(()=> t.style.display='none', 1800);
}
</script>
</body>
</html>
