<?php
include __DIR__ . '/db_connect.php';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($n){ return 'RWF' . number_format((float)$n, 2); }

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
    o.payment_status,
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
    'pending'=>'bg-pending',
    'paid'=>'bg-paid',
    'shipped'=>'bg-shipped',
    'cancelled'=>'bg-cancelled',
    'refunded'=>'bg-refunded',
    'invoice'=>'bg-invoice'
  ];
  $cls = $map[$s] ?? 'bg-unknown';
  return '<span class="pill '.$cls.'">'.h(strtoupper($s)).'</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Orders</title>
<style>
body { font-family:'Inter',sans-serif; background:#f3f4f6; color:#0f172a; margin:0; }
.card { border-radius:1rem; box-shadow:0 10px 25px rgba(0,0,0,.05); margin:20px; padding:0; }
.table { width:100%; border-collapse:collapse; }
.table th, .table td { padding:8px 12px; text-align:left; }
.table tbody tr:hover { transform:translateY(-2px); box-shadow:0 10px 20px rgba(0,0,0,.08); transition:.2s; }
.pill { padding:.4rem .75rem; border-radius:50px; font-weight:600; font-size:.8rem; }
.chip { border-radius:50px; padding:.4rem .8rem; font-size:.85rem; font-weight:500; cursor:pointer; transition:.2s; border:1px solid #ddd; margin:2px; }
.chip.view { background:linear-gradient(135deg,#06b6d4,#3b82f6); color:#fff; border:none; }
.chip.invoice { background:linear-gradient(135deg,#06b6d4,#3b82f6); color:#fff; border:none; }
.chip.del { background:#fff; color:#ef4444; border:1px solid #fecaca; }
.bg-invoice { background:#3b82f6; color:#fff; padding:.3rem .7rem; border-radius:50px; font-weight:600; font-size:.8rem; }
.modal-bg { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.5); z-index:9998; justify-content:center; align-items:center; }
/* .modal-content { background:#fff; padding:20px; border-radius:12px; width:90%; max-width:400px; text-align:center; } */
.modal-content { 
  background:#fff; 
  padding:20px; 
  margin-top:50px;;
  /* border-radius:12px;  */
  width:95%;       /* almost full width on small screens */
  max-width:900px; /* wider on large screens */
  text-align:center; 
  overflow-x:auto; /* scroll if table is wide */
}
.modal-content table { 
  width:100%; 
  border-collapse:collapse; 
  table-layout:auto; 
}
.modal-content table {
  width:100%;
  border-collapse:collapse;
  table-layout:auto;  /* allows flexible column width */
}
.modal-content th, .modal-content td {
  padding:8px;
  text-align:left;
}

.modal-content button { margin:10px; padding:8px 15px; border:none; border-radius:8px; cursor:pointer; font-weight:600; }
.modal-invoice { background:#3b82f6; color:#fff; }
.modal-cancel  { background:#ef4444; color:#fff; }
.toggle-container { display:flex; justify-content:center; gap:20px; margin-top:10px; }
.toggle-btn { padding:6px 12px; border-radius:6px; cursor:pointer; font-weight:600; border:1px solid #ccc; }
.toggle-active { border:2px solid #3b82f6; }
@media (max-width:992px){
  .hide-lg { display:none; }
  .wrap { width:95%; margin:auto; }
  .table th, .table td { font-size:.85rem; padding:6px 8px; }
}
.toolbar { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:10px; }
.toolbar input, .toolbar select, .toolbar button { padding:6px 10px; border-radius:6px; border:1px solid #ccc; }
</style>
</head>
<body>
<div class="wrap narrow">
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Orders</h3>
      <div class="toolbar">
        <input id="q" placeholder="Search order #, name, email or status">
        <select id="status">
          <option value="">All statuses</option>
          <option value="pending">Pending</option>
          <option value="paid">Paid</option>
          <option value="shipped">Shipped</option>
          <option value="cancelled">Cancelled</option>
          <option value="refunded">Refunded</option>
          <option value="invoice">Invoice</option>
        </select>
        <button id="clear">Clear</button>
      </div>
    </div>
    <div class="card-body" style="overflow-x:auto;">
      <table class="table" id="ordersTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Order</th>
            <th>Customer</th>
            <th class="hide-lg">Items</th>
            <th class="hide-lg">Placed</th>
            <th class="hide-lg">Total</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if(!$rows): ?>
          <tr><td colspan="8" style="text-align:center;">No orders found.</td></tr>
          <?php else: $i=1; foreach($rows as $r): ?>
          <tr data-row data-status="<?=h(strtolower($r['status']))?>" data-hay="<?=h(strtolower(trim($r['order_number'].' '.$r['customer_name'].' '.$r['customer_email'].' '.$r['status'])))?>">
            <td><?= (int)$i++ ?></td>
            <td><?= h($r['order_number']) ?></td>
            <td><?= h($r['customer_name']) ?> <br><small><?= h($r['customer_email']) ?></small></td>
            <td class="hide-lg"><?= (int)$r['items_count'] ?></td>
            <td class="hide-lg"><?= h($r['created_at']) ?></td>
            <td class="hide-lg"><?= money($r['total_amount']) ?></td>
            <td><?= status_pill($r['status']) ?></td>
            <td>
              <button class="chip view view_order" data-id="<?= (int)$r['id'] ?>">View</button>
              <?php if(strtolower($r['status'])!='invoice'): ?>
              <button class="chip invoice process_order" data-id="<?= (int)$r['id'] ?>">Process</button>
              <?php else: ?>
              <button class="chip del refund_order" data-id="<?= (int)$r['id'] ?>">Refund</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Confirm -->
<div id="modalConfirm" class="modal-bg">
  <div class="modal-content">
    <p>Has payment been received for this order?</p>
    <div class="toggle-container">
      <div class="toggle-btn toggle-no toggle-active" id="toggleNo">No</div>
      <div class="toggle-btn toggle-yes" id="toggleYes">Yes</div>
    </div>
    <button class="modal-invoice" id="btnInvoice">INVOICE</button>
    <button class="modal-cancel" id="btnCancel">CANCEL</button>
  </div>
</div>

<!-- Modal View -->
<div id="modalView" class="modal-bg" style="width:500px !important;">
  <div class="modal-content">
    <div class="modal-body"></div>
    <button onclick="document.getElementById('modalView').style.display='none'">Close</button>
  </div>
</div>

<script>
let selectedId=null, paymentReceived=false;
const toggleYes=document.getElementById('toggleYes'), toggleNo=document.getElementById('toggleNo');

toggleYes.addEventListener('click',()=>{paymentReceived=true; toggleYes.classList.add('toggle-active'); toggleNo.classList.remove('toggle-active');});
toggleNo.addEventListener('click',()=>{paymentReceived=false; toggleNo.classList.add('toggle-active'); toggleYes.classList.remove('toggle-active');});

document.addEventListener('click',function(e){
  const viewBtn=e.target.closest('.view_order'), procBtn=e.target.closest('.process_order'), refundBtn=e.target.closest('.refund_order');

  if(viewBtn){
    const id=viewBtn.dataset.id;
    fetch('ajax.php?action=view_order',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({id})})
    .then(r=>r.text()).then(html=>{ const modal=document.getElementById('modalView'); modal.querySelector('.modal-body').innerHTML=html; modal.style.display='flex'; });
  }

  if(procBtn){
    selectedId=procBtn.dataset.id; paymentReceived=false; toggleNo.classList.add('toggle-active'); toggleYes.classList.remove('toggle-active'); document.getElementById('modalConfirm').style.display='flex';
  }

  if(refundBtn){
    const id=refundBtn.dataset.id;
    if(confirm('Refund this INVOICE order?')){
      fetch('ajax.php?action=refund_order',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({id})})
      .then(r=>r.text()).then(resp=>{ if(resp==='1'){ toast('Order refunded'); const row=refundBtn.closest('tr'); row.querySelector('td:nth-child(7)').innerHTML='<span class="pill bg-refunded">REFUNDED</span>'; refundBtn.remove(); } else toast('Refund failed: '+resp); });
    }
  }
});

document.getElementById('btnInvoice').addEventListener('click',()=>updateStatus('INVOICE'));
document.getElementById('btnCancel').addEventListener('click',()=>updateStatus('CANCEL'));

// function updateStatus(status){
//   document.getElementById('modalConfirm').style.display='none';
//   if(!selectedId) return;
//   const payment_status = paymentReceived?1:0;
//   if(status==='INVOICE' && !paymentReceived){ toast('Cannot create INVOICE: payment not received'); return; }

//   fetch('ajax.php?action=process_order',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({id:selectedId,status:status,payment_status:payment_status})})
//   .then(r=>r.text()).then(resp=>{
//     if(resp==='1'||resp.toUpperCase()==='OK'){
//       toast('Order updated to '+status);
//       const row=document.querySelector('[data-row] [data-id="'+selectedId+'"]').closest('tr');
//       if(row){ row.querySelector('td:nth-child(7)').innerHTML=`<span class="pill ${status.toLowerCase()}">${status}</span>`; const btn=row.querySelector('.process_order'); if(btn){btn.textContent=status; btn.disabled=true; btn.style.opacity=0.6;} }
//       fetch('ajax.php?action=send_email',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({id:selectedId,status:status})});
//     } else toast('Update failed: '+resp);
//   }).catch(()=>toast('Network error'));
// }
function updateStatus(status){
  document.getElementById('modalConfirm').style.display='none';
  if(!selectedId) return;

  // paymentReceived is true/false depending on the toggle
  const payment_status = paymentReceived ? 1 : 0;

  fetch('ajax.php?action=process_order',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:new URLSearchParams({
      id:selectedId,
      status:status,
      payment_status:payment_status
    })
  })
  .then(r=>r.text())
  .then(resp=>{
    if(resp==='1' || resp.toUpperCase()==='OK'){
      toast('Order updated to '+status);

      // Update table row visually
      const row=document.querySelector('[data-row] [data-id="'+selectedId+'"]').closest('tr');
      if(row){
        row.querySelector('td:nth-child(7)').innerHTML =
          `<span class="pill ${status.toLowerCase()}">${status}</span>`;
        const btn=row.querySelector('.process_order');
        if(btn){
          btn.textContent=status;
          btn.disabled=true;
          btn.style.opacity=0.6;
        }
      }

      // Optional: notify backend to send email
      fetch('ajax.php?action=send_email',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:new URLSearchParams({id:selectedId,status:status,payment_status:payment_status})
      });
    } else {
      toast('Update failed: '+resp);
    }
  })
  .catch(()=>toast('Network error'));
}

// Search/filter
const $q=document.getElementById('q'), $st=document.getElementById('status'), $clear=document.getElementById('clear');
function applyFilter(){ const t=($q.value||'').toLowerCase().trim(); const s=($st.value||'').toLowerCase().trim(); document.querySelectorAll('[data-row]').forEach(row=>{ const hay=row.dataset.hay||''; const st=row.dataset.status||''; const okT=!t||hay.indexOf(t)!==-1||st.indexOf(t)!==-1; const okS=!s||st===s; row.style.display=(okT&&okS)?'':'none'; });}
$q.addEventListener('input',applyFilter); $st.addEventListener('change',applyFilter); $clear.addEventListener('click',()=>{$q.value='';$st.value='';applyFilter();});

let toastTimer;
function toast(msg){ let t=document.getElementById('miniToast'); if(!t){ t=document.createElement('div'); t.id='miniToast'; t.style.cssText='position:fixed;left:16px;bottom:16px;background:#0f172a;color:#fff;padding:10px 12px;border-radius:10px;z-index:9999'; document.body.appendChild(t);} t.textContent=msg; t.style.display='block'; clearTimeout(toastTimer); toastTimer=setTimeout(()=>t.style.display='none',1800);}
</script>
</body>
</html>
