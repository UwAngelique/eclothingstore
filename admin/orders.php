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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Orders Management</title>
<style>
* { box-sizing: border-box; }
body { 
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
  background: #f3f4f6; 
  color: #0f172a; 
  margin: 0; 
  padding: 0;
}
.wrap { 
  max-width: 1400px; 
  margin: 0 auto; 
  padding: 20px;
}
.card { 
  border-radius: 12px; 
  box-shadow: 0 1px 3px rgba(0,0,0,0.1); 
  margin: 20px 0; 
  padding: 0; 
  background: #fff; 
}
.card-header { 
  padding: 24px; 
  border-bottom: 1px solid #e5e7eb; 
}
.card-title { 
  margin: 0 0 16px 0; 
  font-size: 1.5rem; 
  font-weight: 700;
  color: #0f172a;
}
.card-body { 
  padding: 0; 
}
.table-container {
  overflow-x: auto;
}
.table { 
  width: 100%; 
  border-collapse: collapse; 
  min-width: 800px;
}
.table th, 
.table td { 
  padding: 16px 12px; 
  text-align: left; 
  border-bottom: 1px solid #f3f4f6; 
}
.table thead th { 
  background: #f9fafb; 
  font-weight: 600; 
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #6b7280;
}
.table tbody tr { 
  transition: all 0.2s; 
}
.table tbody tr:hover { 
  background: #f9fafb; 
}
.chip { 
  border-radius: 8px; 
  padding: 8px 16px; 
  font-size: 0.875rem; 
  font-weight: 500; 
  cursor: pointer; 
  transition: all 0.2s; 
  border: none; 
  display: inline-block;
}
.chip.view { 
  background: linear-gradient(135deg, #06b6d4, #3b82f6); 
  color: #fff; 
}
.chip.view:hover { 
  transform: translateY(-2px); 
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); 
}

/* Status badge styles */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: 2px solid transparent;
  white-space: nowrap;
}

.status-badge:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.status-badge:after {
  content: '▼';
  font-size: 0.7rem;
  opacity: 0.7;
}

/* Payment Status Colors */
.payment-awaiting-payment { 
  background: #fef3c7; 
  color: #92400e; 
  border-color: #fbbf24; 
}
.payment-paid { 
  background: #d1fae5; 
  color: #065f46; 
  border-color: #10b981; 
}
.payment-cancelled { 
  background: #fee2e2; 
  color: #991b1b; 
  border-color: #ef4444; 
}
.payment-refunded { 
  background: #ede9fe; 
  color: #5b21b6; 
  border-color: #8b5cf6; 
}
.payment-partially-refunded { 
  background: #e0e7ff; 
  color: #3730a3; 
  border-color: #818cf8; 
}

/* Order Status Colors */
.order-awaiting-processing { 
  background: #fef3c7; 
  color: #92400e; 
  border-color: #fbbf24; 
}
.order-processing { 
  background: #fed7aa; 
  color: #9a3412; 
  border-color: #fb923c; 
}
.order-ready-for-pickup { 
  background: #dbeafe; 
  color: #1e40af; 
  border-color: #3b82f6; 
}
.order-shipped { 
  background: #cffafe; 
  color: #155e75; 
  border-color: #06b6d4; 
}
.order-out-for-delivery { 
  background: #a5f3fc; 
  color: #0e7490; 
  border-color: #22d3ee; 
}
.order-delivered { 
  background: #d1fae5; 
  color: #065f46; 
  border-color: #10b981; 
}
.order-delivery-canceled { 
  background: #fee2e2; 
  color: #991b1b; 
  border-color: #ef4444; 
}
.order-returned { 
  background: #ede9fe; 
  color: #5b21b6; 
  border-color: #8b5cf6; 
}

/* Status Modal */
.status-modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  z-index: 9999;
  justify-content: center;
  align-items: center;
  animation: fadeIn 0.2s;
}

.status-modal.show {
  display: flex;
}

.status-modal-content {
  background: #fff;
  border-radius: 16px;
  padding: 28px;
  width: 90%;
  max-width: 420px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.2);
  animation: slideUp 0.3s;
}

.status-modal-header {
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 2px solid #f3f4f6;
}

.status-modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  color: #0f172a;
  font-weight: 700;
}

.status-modal-header .order-info {
  margin-top: 8px;
  font-size: 0.9rem;
  color: #64748b;
}

.status-options {
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-height: 420px;
  overflow-y: auto;
  padding-right: 4px;
}

.status-option {
  padding: 14px 18px;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 12px;
}

.status-option:hover {
  border-color: #3b82f6;
  background: #f0f9ff;
  transform: translateX(4px);
}

.status-option.selected {
  border-color: #3b82f6;
  background: #dbeafe;
}

.status-option-icon {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #94a3b8;
  flex-shrink: 0;
}

.status-option.selected .status-option-icon {
  background: #3b82f6;
}

.status-modal-footer {
  margin-top: 24px;
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.modal-btn {
  padding: 11px 24px;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.9rem;
}

.modal-btn-cancel {
  background: #f3f4f6;
  color: #64748b;
}

.modal-btn-cancel:hover {
  background: #e5e7eb;
}

.modal-btn-save {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: #fff;
}

.modal-btn-save:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from { 
    opacity: 0;
    transform: translateY(20px);
  }
  to { 
    opacity: 1;
    transform: translateY(0);
  }
}

/* View Order Modal */
.modal-bg { 
  display: none; 
  position: fixed; 
  top: 0; 
  left: 0; 
  width: 100%; 
  height: 100%;
  background: rgba(0, 0, 0, 0.5); 
  z-index: 9998; 
  justify-content: center; 
  align-items: center; 
}

.modal-content { 
  background: #fff; 
  padding: 32px; 
  margin: 20px;
  width: 95%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
  border-radius: 16px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.2);
}

.modal-content h3 {
  margin-top: 0;
  color: #0f172a;
  font-size: 1.5rem;
}

.modal-content table { 
  width: 100%; 
  border-collapse: collapse; 
  margin-top: 20px;
}

.modal-content th, 
.modal-content td {
  padding: 12px 8px;
  text-align: left;
  border-bottom: 1px solid #f3f4f6;
}

.modal-content th {
  font-weight: 600;
  color: #6b7280;
  width: 35%;
}

.modal-content td {
  color: #0f172a;
}

.modal-content button { 
  margin-top: 24px; 
  padding: 12px 24px; 
  border: none; 
  border-radius: 8px; 
  cursor: pointer; 
  font-weight: 600;
  background: #3b82f6;
  color: #fff;
  font-size: 0.9rem;
}

.modal-content button:hover {
  background: #2563eb;
  transform: translateY(-1px);
}

/* Toolbar */
.toolbar { 
  display: flex; 
  flex-wrap: wrap; 
  gap: 12px; 
  margin-top: 16px; 
}

.toolbar input, 
.toolbar select { 
  padding: 10px 14px; 
  border-radius: 8px; 
  border: 1px solid #d1d5db; 
  font-size: 0.9rem;
  transition: border-color 0.2s;
}

.toolbar input:focus,
.toolbar select:focus {
  outline: none;
  border-color: #3b82f6;
}

.toolbar input {
  flex: 1;
  min-width: 240px;
}

.toolbar select {
  min-width: 200px;
}

.toolbar button {
  padding: 10px 20px;
  background: #3b82f6;
  color: #fff;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  font-size: 0.9rem;
  transition: all 0.2s;
}

.toolbar button:hover {
  background: #2563eb;
  transform: translateY(-1px);
}

@media (max-width: 992px) {
  .hide-lg { display: none; }
  .table th, .table td { 
    font-size: 0.85rem; 
    padding: 12px 8px; 
  }
  .wrap {
    padding: 12px;
  }
  .card-header {
    padding: 16px;
  }
}

@media (max-width: 640px) {
  .toolbar {
    flex-direction: column;
  }
  .toolbar input,
  .toolbar select {
    width: 100%;
  }
  .status-badge {
    font-size: 0.75rem;
    padding: 6px 10px;
  }
}
</style>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

</head>
<body>
<div class="wrap">
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Orders Management</h3>
      <div class="toolbar">
        <!-- <input id="q" type="text" placeholder="Search order #, customer name, email..."> -->
        <h id="q"></h>
        <select id="status">
          <option value="">All Payment Statuses</option>
          <option value="awaiting payment">Awaiting Payment</option>
          <option value="paid">Paid</option>
          <option value="cancelled">Cancelled</option>
          <option value="refunded">Refunded</option>
          <option value="partially refunded">Partially Refunded</option>
        </select>
        <button id="clear">Clear Filters</button>
      </div>
    </div>
    <div class="card-body">
      <div class="table-container">
        <table class="table" id="ordersTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Order Number</th>
              <th>Customer</th>
              <th class="hide-lg">Items</th>
              <th class="hide-lg">Date</th>
              <th class="hide-lg">Total</th>
              <th>Payment Status</th>
              <th>Order Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if(!$rows): ?>
            <tr>
              <td colspan="9" style="text-align:center; padding:60px 20px; color:#9ca3af;">
                No orders found.
              </td>
            </tr>
            <?php else: $i=1; foreach($rows as $r): ?>
            <tr data-row 
                data-status="<?=h(strtolower($r['status']))?>" 
                data-payment-status="<?=h(strtolower($r['payment_status']))?>" 
                data-hay="<?=h(strtolower(trim($r['order_number'].' '.$r['customer_name'].' '.$r['customer_email'].' '.$r['status'].' '.$r['payment_status'])))?>">
              <td><?= (int)$i++ ?></td>
              <td><strong><?= h($r['order_number']) ?></strong></td>
              <td>
                <div><?= h($r['customer_name']) ?></div>
                <small style="color:#6b7280;"><?= h($r['customer_email']) ?></small>
              </td>
              <td class="hide-lg"><?= (int)$r['items_count'] ?></td>
              <td class="hide-lg">
                <small><?= h(date('M d, Y', strtotime($r['created_at']))) ?></small>
              </td>
              <td class="hide-lg"><strong><?= money($r['total_amount']) ?></strong></td>
              <td>
                <span class="status-badge payment-<?= str_replace(' ', '-', strtolower($r['payment_status'])) ?>" 
                      data-type="payment" 
                      data-order-id="<?= (int)$r['id'] ?>"
                      data-order-number="<?= h($r['order_number']) ?>"
                      data-current-status="<?= h($r['payment_status']) ?>">
                  <?= h(ucwords($r['payment_status'])) ?>
                </span>
              </td>
              <td>
                <span class="status-badge order-<?= str_replace(' ', '-', strtolower($r['status'])) ?>" 
                      data-type="order" 
                      data-order-id="<?= (int)$r['id'] ?>"
                      data-order-number="<?= h($r['order_number']) ?>"
                      data-current-status="<?= h($r['status']) ?>">
                  <?= h(ucwords($r['status'])) ?>
                </span>
              </td>
              <td>
                <button class="chip view view_order" data-id="<?= (int)$r['id'] ?>">View</button>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal: View Order Details -->
<div id="modalView" class="modal-bg">
  <div class="modal-content">
    <div class="modal-body"></div>
    <button onclick="document.getElementById('modalView').style.display='none'">Close</button>
  </div>
</div>

<!-- Modal: Change Status -->
<div id="statusModal" class="status-modal">
  <div class="status-modal-content">
    <div class="status-modal-header">
      <h3 id="modalTitle">Change Status</h3>
      <div class="order-info" id="modalOrderInfo">Order #</div>
    </div>
    <div class="status-options" id="statusOptions">
      <!-- Options populated dynamically -->
    </div>
    <div class="status-modal-footer">
      <button class="modal-btn modal-btn-cancel" onclick="closeStatusModal()">Cancel</button>
      <button class="modal-btn modal-btn-save" onclick="saveStatusChange()">Save Changes</button>
    </div>
  </div>
</div>

<script>
let currentStatusChange = {
  orderId: null,
  type: null,
  currentStatus: null,
  newStatus: null,
  orderNumber: null,
  badge: null
};

const paymentStatuses = [
  { value: 'awaiting payment', label: 'Awaiting Payment' },
  { value: 'paid', label: 'Paid' },
  { value: 'cancelled', label: 'Cancelled' },
  { value: 'refunded', label: 'Refunded' },
  { value: 'partially refunded', label: 'Partially Refunded' }
];

const orderStatuses = [
  { value: 'awaiting processing', label: 'Awaiting Processing' },
  { value: 'processing', label: 'Processing' },
  { value: 'ready for pickup', label: 'Ready For Pickup' },
  { value: 'shipped', label: 'Shipped' },
  { value: 'out for delivery', label: 'Out For Delivery' },
  { value: 'delivered', label: 'Delivered' },
  { value: 'delivery canceled', label: 'Delivery Canceled' },
  { value: 'returned', label: 'Returned' }
];

// Open status modal when badge clicked
document.addEventListener('click', function(e) {
  const badge = e.target.closest('.status-badge');
  if (badge) {
    const type = badge.dataset.type;
    const orderId = badge.dataset.orderId;
    const orderNumber = badge.dataset.orderNumber;
    const currentStatus = badge.dataset.currentStatus;
    
    currentStatusChange = {
      orderId: orderId,
      type: type,
      currentStatus: currentStatus.toLowerCase(),
      newStatus: currentStatus.toLowerCase(),
      orderNumber: orderNumber,
      badge: badge
    };
    
    openStatusModal(type, currentStatus, orderNumber);
  }
});

function openStatusModal(type, currentStatus, orderNumber) {
  const modal = document.getElementById('statusModal');
  const title = document.getElementById('modalTitle');
  const orderInfo = document.getElementById('modalOrderInfo');
  const optionsContainer = document.getElementById('statusOptions');
  
  title.textContent = type === 'payment' ? 'Change Payment Status' : 'Change Order Status';
  orderInfo.textContent = `Order ${orderNumber}`;
  
  const statuses = type === 'payment' ? paymentStatuses : orderStatuses;
  
  optionsContainer.innerHTML = statuses.map(status => `
    <div class="status-option ${status.value.toLowerCase() === currentStatus.toLowerCase() ? 'selected' : ''}" 
         data-value="${status.value}">
      <span class="status-option-icon"></span>
      ${status.label}
    </div>
  `).join('');
  
  modal.classList.add('show');
  
  // Add click handlers to options
  document.querySelectorAll('.status-option').forEach(option => {
    option.addEventListener('click', function() {
      document.querySelectorAll('.status-option').forEach(o => o.classList.remove('selected'));
      this.classList.add('selected');
      currentStatusChange.newStatus = this.dataset.value;
    });
  });
}

function closeStatusModal() {
  document.getElementById('statusModal').classList.remove('show');
}

function saveStatusChange() {
  if (!currentStatusChange.orderId || !currentStatusChange.newStatus) {
    toast('No changes to save');
    closeStatusModal();
    return;
  }
  
  const action = currentStatusChange.type === 'payment' ? 'update_payment_status' : 'update_order_status';
  const paramName = currentStatusChange.type === 'payment' ? 'payment_status' : 'status';
  
  // Show loading state
  const saveBtn = document.querySelector('.modal-btn-save');
  const originalText = saveBtn.textContent;
  saveBtn.textContent = 'Saving...';
  saveBtn.disabled = true;
  
  fetch('ajaxx.php?action=' + action, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({
      id: currentStatusChange.orderId,
      [paramName]: currentStatusChange.newStatus
    })
  })
  .then(r => r.text())
  .then(resp => {
    saveBtn.textContent = originalText;
    saveBtn.disabled = false;
    
    if (resp === '1' || resp.toUpperCase() === 'OK') {
      toast('Status updated successfully! Email sent to customer.');
      
      // Update badge
      const badge = currentStatusChange.badge;
      const newStatusFormatted = currentStatusChange.newStatus.split(' ').map(w => 
        w.charAt(0).toUpperCase() + w.slice(1)
      ).join(' ');
      
      badge.textContent = newStatusFormatted;
      badge.dataset.currentStatus = currentStatusChange.newStatus;
      
      // Update badge class
      const prefix = currentStatusChange.type === 'payment' ? 'payment-' : 'order-';
      badge.className = 'status-badge ' + prefix + currentStatusChange.newStatus.replace(/ /g, '-');
      
      // Update row data
      const row = badge.closest('tr');
      if (currentStatusChange.type === 'payment') {
        row.dataset.paymentStatus = currentStatusChange.newStatus.toLowerCase();
      } else {
        row.dataset.status = currentStatusChange.newStatus.toLowerCase();
      }
      
      closeStatusModal();
    } else {
      toast('Update failed: ' + resp);
    }
  })
  .catch(err => {
    saveBtn.textContent = originalText;
    saveBtn.disabled = false;
    toast('Network error. Please try again.');
    console.error(err);
  });
}

// Close modal on background click
document.getElementById('statusModal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeStatusModal();
  }
});

// View order details
document.addEventListener('click', function(e) {
  const viewBtn = e.target.closest('.view_order');
  if (viewBtn) {
    const id = viewBtn.dataset.id;
    fetch('ajax.php?action=view_order', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: new URLSearchParams({id})
    })
    .then(r => r.text())
    .then(html => {
      const modal = document.getElementById('modalView');
      modal.querySelector('.modal-body').innerHTML = html;
      modal.style.display = 'flex';
    })
    .catch(err => {
      toast('Failed to load order details');
      console.error(err);
    });
  }
});

// Search and filter
const $q = document.getElementById('q');
const $st = document.getElementById('status');
const $clear = document.getElementById('clear');

function applyFilter() {
  const t = ($q.value || '').toLowerCase().trim();
  const s = ($st.value || '').toLowerCase().trim();
  
  let visibleCount = 0;
  document.querySelectorAll('[data-row]').forEach(row => {
    const hay = row.dataset.hay || '';
    const status = row.dataset.status || '';
    const paymentStatus = row.dataset.paymentStatus || '';
    
    const okT = !t || hay.indexOf(t) !== -1;
    const okS = !s || paymentStatus === s;
    
    if (okT && okS) {
      row.style.display = '';
      visibleCount++;
    } else {
      row.style.display = 'none';
    }
  });
}

$q.addEventListener('input', applyFilter);
$st.addEventListener('change', applyFilter);
$clear.addEventListener('click', () => {
  $q.value = '';
  $st.value = '';
  applyFilter();
});

// Toast notification
let toastTimer;
function toast(msg) {
  let t = document.getElementById('miniToast');
  if (!t) {
    t = document.createElement('div');
    t.id = 'miniToast';
    t.style.cssText = 'position:fixed;left:50%;bottom:24px;transform:translateX(-50%);background:#0f172a;color:#fff;padding:14px 28px;border-radius:10px;z-index:99999;box-shadow:0 4px 12px rgba(0,0,0,0.3);font-weight:500;min-width:250px;text-align:center;';
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.style.display = 'block';
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    t.style.display = 'none';
  }, 3000);
}

  $(document).ready(function() {
    $('#ordersTable').DataTable({
      pageLength: 10,     // number of rows per page
      lengthMenu: [5, 10, 25, 50, 100], // dropdown options
      ordering: true,     // enable sorting
      searching: true     // enable search box
    });
  });

</script>
</body>
</html>