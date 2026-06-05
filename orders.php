<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$pageTitle  = 'Quản lý Đơn hàng';
$activePage = 'orders';
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_status') {
        updateOrderStatus($_POST['id'], $_POST['status']);
        $flash = ['type'=>'success','msg'=>'✅ Cập nhật trạng thái thành công!'];
    }
}

$orders = array_reverse(getAllOrders());
$filterStatus = $_GET['status'] ?? '';
$filterDate   = $_GET['date'] ?? '';
$search       = $_GET['q'] ?? '';

if ($filterStatus) $orders = array_filter($orders, fn($o) => $o['status'] === $filterStatus);
if ($filterDate)   $orders = array_filter($orders, fn($o) => str_starts_with($o['created_at'], $filterDate));
if ($search)       $orders = array_filter($orders, fn($o) => stripos($o['customer_name'].$o['id'], $search) !== false);

include __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?>
<div class="flash-msg flash-<?= $flash['type'] ?>"><?= $flash['msg'] ?></div>
<?php endif; ?>

<!-- FILTER BAR -->
<div class="card mb-3">
  <div style="padding:14px 20px">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <input name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" style="max-width:220px" placeholder="🔍 Tìm mã ĐH, khách…">
      <select name="status" class="form-select" style="max-width:160px">
        <option value="">Tất cả trạng thái</option>
        <option value="pending"   <?= $filterStatus==='pending'?'selected':'' ?>>Chờ xử lý</option>
        <option value="confirmed" <?= $filterStatus==='confirmed'?'selected':'' ?>>Đã xác nhận</option>
        <option value="delivered" <?= $filterStatus==='delivered'?'selected':'' ?>>Đã giao</option>
        <option value="cancelled" <?= $filterStatus==='cancelled'?'selected':'' ?>>Đã huỷ</option>
      </select>
      <input name="date" type="date" class="form-control" style="max-width:160px" value="<?= $filterDate ?>">
      <button class="btn btn-outline-primary">Lọc</button>
      <?php if ($filterStatus || $filterDate || $search): ?>
        <a href="orders.php" class="btn btn-outline-secondary">✕ Xoá</a>
      <?php endif; ?>
      <span style="color:#aaa;font-size:13px;margin-left:auto"><?= count($orders) ?> đơn hàng</span>
    </form>
  </div>
</div>

<!-- TABLE -->
<div class="card">
  <div class="table-wrapper">
    <table class="table table-hover">
      <thead>
        <tr>
          <th>Mã ĐH</th><th>Khách hàng</th><th>SĐT</th>
          <th>Sản phẩm</th><th>Tổng tiền</th><th>Đã trả</th><th>Còn nợ</th>
          <th>Trạng thái</th><th>Ngày tạo</th><th>Thao tác</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td style="font-weight:700;color:#E85D04">#<?= $o['id'] ?></td>
          <td style="font-weight:600"><?= htmlspecialchars($o['customer_name']) ?></td>
          <td style="color:#aaa"><?= htmlspecialchars($o['phone'] ?? '') ?></td>
          <td style="font-size:12px;max-width:200px">
            <?= implode(', ', array_map(fn($i) => "{$i['name']} x{$i['qty']}", array_slice($o['items'], 0, 2))) ?>
            <?php if (count($o['items']) > 2): ?><em style="color:#aaa"> +<?= count($o['items'])-2 ?> nữa</em><?php endif; ?>
          </td>
          <td style="font-weight:700"><?= vnd($o['total']) ?></td>
          <td style="color:#10b981;font-weight:600"><?= vnd($o['paid'] ?? 0) ?></td>
          <td style="color:<?= ($o['debt']??0)>0?'#ef4444':'#10b981' ?>;font-weight:600">
            <?= vnd($o['debt'] ?? 0) ?>
          </td>
          <td><?= statusBadge($o['status']) ?></td>
          <td style="color:#aaa;font-size:12px"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
          <td>
            <button class="btn btn-sm btn-outline-primary"
                    data-bs-toggle="modal" data-bs-target="#statusModal"
                    onclick="setOrderId('<?= $o['id'] ?>','<?= $o['status'] ?>')">
              <i class="bi bi-arrow-repeat"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary"
                    data-bs-toggle="modal" data-bs-target="#detailModal"
                    onclick="showDetail(<?= htmlspecialchars(json_encode($o)) ?>)">
              <i class="bi bi-eye"></i>
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($orders)): ?>
        <tr><td colspan="10" style="text-align:center;padding:30px;color:#aaa">Không có đơn hàng nào</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- STATUS MODAL -->
<div class="modal fade" id="statusModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Cập nhật trạng thái</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form method="POST">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="id" id="modalOrderId">
        <div class="modal-body">
          <select name="status" id="modalStatus" class="form-select">
            <option value="pending">Chờ xử lý</option>
            <option value="confirmed">Đã xác nhận</option>
            <option value="delivered">Đã giao</option>
            <option value="cancelled">Đã huỷ</option>
          </select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Huỷ</button>
          <button type="submit" class="btn btn-primary">Cập nhật</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- DETAIL MODAL -->
<div class="modal fade" id="detailModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="detailTitle">Chi tiết đơn hàng</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body" id="detailBody"></div>
    </div>
  </div>
</div>

<?php
$extraJs = '<script>
function setOrderId(id, status) {
  document.getElementById("modalOrderId").value = id;
  const sel = document.getElementById("modalStatus");
  for (let o of sel.options) { if (o.value === status) { o.selected = true; break; } }
}

function showDetail(o) {
  document.getElementById("detailTitle").textContent = "Đơn hàng #" + o.id;
  let rows = o.items.map(i => `<tr><td>${i.name}</td><td>${i.qty} ${i.unit||""}</td><td>${new Intl.NumberFormat("vi-VN").format(i.price)} đ</td><td style="font-weight:700">${new Intl.NumberFormat("vi-VN").format(i.qty*i.price)} đ</td></tr>`).join("");
  document.getElementById("detailBody").innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
      <div><strong>Khách hàng:</strong> ${o.customer_name}<br><strong>SĐT:</strong> ${o.phone||"—"}</div>
      <div><strong>Ngày:</strong> ${o.created_at}<br><strong>Ghi chú:</strong> ${o.note||"—"}</div>
    </div>
    <table class="table table-sm"><thead><tr><th>Sản phẩm</th><th>Số lượng</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead><tbody>${rows}</tbody></table>
    <div style="text-align:right;font-size:16px"><strong>Tổng: <span style="color:#E85D04">${new Intl.NumberFormat("vi-VN").format(o.total)} đ</span></strong><br>
    Đã trả: <span style="color:#10b981">${new Intl.NumberFormat("vi-VN").format(o.paid||0)} đ</span> | Còn nợ: <span style="color:#ef4444">${new Intl.NumberFormat("vi-VN").format(o.debt||0)} đ</span></div>
  `;
}
</script>';
include __DIR__ . '/../includes/footer.php';
?>
