<?php
include __DIR__ . '/db_connect.php';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($n){ return 'RWF ' . number_format((float)$n, 2); }

$sql = "
  SELECT 
    o.id, o.order_number, o.customer_name, o.customer_email, o.customer_phone,
    o.shipping_address, o.total_amount, o.status, o.created_at,
    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS items_count
  FROM orders o
  ORDER BY o.id DESC
";
$res  = $conn->query($sql);
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

function status_pill($s){
  $s = strtolower((string)$s);
  $map = [
    'pending'   => 'bg-warning text-dark',
    'paid'      => 'bg-primary text-white',
    'shipped'   => 'bg-info text-dark',
    'cancelled' => 'bg-danger text-white',
    'refunded'  => 'bg-secondary text-white'
  ];
  $cls = $map[$s] ?? 'bg-light text-dark';
  return '<span class="badge '.$cls.'">'.h(ucfirst($s)).'</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Orders</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f3f4f6; color: #0f172a; font-family: 'Inter', sans-serif; }
.card { border-radius: 1rem; box-shadow: 0 10px 25px rgba(0,0,0,.05); }
.table tbody tr { transition: 0.2s; }
.table tbody tr:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,.08); }
.pill { padding: 0.4rem 0.75rem; border-radius: 50px; font-weight:600; font-size:.8rem; }
.chip { border-radius: 50px; padding: 0.4rem 0.8rem; font-size:.85rem; font-weight:500; cursor:pointer; transition:0.2s; border:1px solid #ddd; }
.chip.view { background: linear-gradient(135deg,#06b6d4,#3b82f6); color:#fff; border:none; }
.chip.del { background:#fff; color:#ef4444; border:1px solid #fecaca; }
@media (max-width: 992px){
  .hide-lg { display:none; }
  .wrap { width:95%; margin:auto; }
}
</style>
</head>
<body>

<div class="container my-4 wrap">
  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 bg-white border-0 pb-0">
      <h3 class="mb-2 mb-md-0 fw-bold">Orders</h3>
      <div class="d-flex flex-wrap gap-2 align-items-center">
        <input id="q" class="form-control form-control-sm" style="min-width:200px;" placeholder="Search (order #, name, email)">
        <select id="status" class="form-select form-select-sm" style="min-width:150px;">
          <option value="">All statuses</option>
          <option value="pending">Pending</option>
          <option value="paid">Paid</option>
          <option value="shipped">Shipped</option>
          <option value="cancelled">Cancelled</option>
          <option value="refunded">Refunded</option>
        </select>
        <button id="clear" class="btn btn-outline-secondary btn-sm">Clear</button>
      </div>
    </div>
    <div class="card-body p-0 mt-2">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="ordersTable">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Order</th>
              <th>Customer</th>
              <th class="hide-lg">Items</th>
              <th class="hide-lg">Placed</th>
              <th class="hide-lg">Total</th>
              <th>Status</th>
              <th style="text-align:right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$rows): ?>
              <tr><td colspan="8" class="text-center py-3 text-muted">No orders found.</td></tr>
            <?php else: $i=1; foreach ($rows as $r): ?>
              <tr data-row data-status="<?= h(strtolower($r['status'])) ?>" data-hay="<?= h(strtolower(trim($r['order_number'].' '.$r['customer_name'].' '.$r['customer_email']))) ?>">
                <td><?= (int)$i++ ?></td>
                <td>
                  <div class="fw-bold"><?= h($r['order_number']) ?></div>
                  <div class="text-muted small">#<?= (int)$r['id'] ?></div>
                </td>
                <td>
                  <div class="fw-bold"><?= h($r['customer_name']) ?></div>
                  <div class="text-muted small"><?= h($r['customer_email'] ?: '') ?></div>
                </td>
                <td class="hide-lg text-center"><?= (int)$r['items_count'] ?></td>
                <td class="hide-lg"><?= h($r['created_at']) ?></td>
                <td class="hide-lg fw-bold"><?= money($r['total_amount']) ?></td>
                <td><?= status_pill($r['status']) ?></td>
                <td class="text-end">
                  <button class="chip view view_order me-1" data-id="<?= (int)$r['id'] ?>">View</button>
                  <button class="chip del delete_order" data-id="<?= (int)$r['id'] ?>">Delete</button>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
// Client-side search/filter
const $q = document.getElementById('q');
const $st = document.getElementById('status');
const $clear = document.getElementById('clear');

function applyFilter(){
  const t = ($q.value||'').toLowerCase().trim();
  const s = ($st.value||'').toLowerCase().trim();
  document.querySelectorAll('[data-row]').forEach(row=>{
    const hay = row.getAttribute('data-hay')||'';
    const st  = row.getAttribute('data-status')||'';
    row.style.display = (!t || hay.includes(t)) && (!s || st===s) ? '' : 'none';
  });
}
$q.addEventListener('input', applyFilter);
$st.addEventListener('change', applyFilter);
$clear.addEventListener('click', ()=>{ $q.value=''; $st.value=''; applyFilter(); });

// Action buttons
document.addEventListener('click', function(e){
  const viewBtn = e.target.closest('.view_order');
  const delBtn  = e.target.closest('.delete_order');

  if(viewBtn){
    const id = viewBtn.dataset.id;
    if(typeof window.uni_modal==='function'){
      window.uni_modal('Order Details','order_view.php?id='+encodeURIComponent(id),'mid-large');
    } else { window.location.href='order_view.php?id='+encodeURIComponent(id); }
  }

  if(delBtn){
    const id = delBtn.dataset.id;
    if(confirm('Delete this order? This cannot be undone.')) delete_order(id);
  }
});

function delete_order(id){
  fetch('ajax.php?action=delete_order', {
    method:'POST',
    body: new URLSearchParams({id:String(id)})
  }).then(r=>r.text()).then(resp=>{
    if(resp==='1'||resp==='OK') location.reload();
    else alert('Delete failed: '+resp);
  }).catch(()=> alert('Network error'));
}
</script>
</body>
</html>
