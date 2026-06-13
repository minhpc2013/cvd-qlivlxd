<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$pageTitle  = 'Nhật ký hoạt động';
$activePage = 'activity';

$logs   = array_reverse(readJSON(ACTIVITY_FILE));
$search = $_GET['q'] ?? '';
$filterDate = $_GET['date'] ?? '';

if ($search)     $logs = array_filter($logs, fn($l) => stripos($l['action'].$l['detail'].$l['user'], $search) !== false);
if ($filterDate) $logs = array_filter($logs, fn($l) => str_starts_with($l['created_at'], $filterDate));

$actionLabels = [
    'LOGIN'          => ['🔑 Đăng nhập',      'badge-info'],
    'LOGOUT'         => ['🚪 Đăng xuất',       'badge-secondary'],
    'ADD_PRODUCT'    => ['➕ Thêm sản phẩm',   'badge-success'],
    'UPDATE_PRODUCT' => ['✏️ Sửa sản phẩm',    'badge-warning'],
    'DELETE_PRODUCT' => ['🗑 Xoá sản phẩm',    'badge-danger'],
    'ADD_ORDER'      => ['🛒 Tạo đơn hàng',    'badge-success'],
    'UPDATE_ORDER'   => ['🔄 Cập nhật đơn',    'badge-warning'],
    'ADD_CUSTOMER'   => ['👤 Thêm khách',       'badge-success'],
    'ADD_SUPPLIER'   => ['🚚 Thêm NCC',         'badge-success'],
    'PAY_DEBT'       => ['💳 Thanh toán CN',    'badge-info'],
];

include __DIR__ . '/../includes/header.php';
?>

<div class="card mb-3">
  <div style="padding:14px 20px;display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap">
      <input name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" style="max-width:260px" placeholder="🔍 Tìm hoạt động, nhân viên…">
      <input name="date" type="date" class="form-control" style="max-width:160px" value="<?= $filterDate ?>">
      <button class="btn btn-outline-primary">Lọc</button>
      <?php if ($search||$filterDate): ?><a href="activity.php" class="btn btn-outline-secondary">✕</a><?php endif; ?>
    </form>
    <span style="margin-left:auto;color:#aaa;font-size:13px"><?= count($logs) ?> bản ghi</span>
  </div>
</div>

<div class="card">
  <div class="table-wrapper">
    <table class="table table-hover">
      <thead>
        <tr><th>Thời gian</th><th>Hành động</th><th>Chi tiết</th><th>Nhân viên</th><th>IP</th></tr>
      </thead>
      <tbody>
      <?php foreach (array_values($logs) as $log):
        $laction  = $log['action']     ?? '';
        $ldetail  = $log['detail']     ?? '';
        $luser    = $log['user']       ?? '';
        $lip      = $log['ip']         ?? '';
        $lcreated = $log['created_at'] ?? date('Y-m-d H:i:s');
        [$label, $cls] = $actionLabels[$laction] ?? [$laction ?: '—', 'badge-secondary'];
      ?>
        <tr>
          <td style="color:#aaa;font-size:12px;white-space:nowrap"><?= date('d/m/Y H:i:s', strtotime($lcreated)) ?></td>
          <td><span class="badge <?= $cls ?>"><?= htmlspecialchars($label) ?></span></td>
          <td style="font-size:13px"><?= htmlspecialchars($ldetail) ?></td>
          <td style="font-weight:600;font-size:13px"><?= htmlspecialchars($luser) ?></td>
          <td style="font-size:12px;color:#aaa"><?= htmlspecialchars($lip) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($logs)): ?>
        <tr><td colspan="5" style="text-align:center;padding:30px;color:#aaa">Không có nhật ký nào</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
