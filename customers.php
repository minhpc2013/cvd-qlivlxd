<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$pageTitle  = 'Quản lý Khách hàng';
$activePage = 'customers';
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        addCustomer([
            'name'    => trim($_POST['name']),
            'phone'   => trim($_POST['phone']),
            'address' => trim($_POST['address'] ?? ''),
            'email'   => trim($_POST['email'] ?? ''),
            'type'    => $_POST['type'],
            'note'    => trim($_POST['note'] ?? ''),
        ]);
        $flash = ['type'=>'success','msg'=>'✅ Thêm khách hàng thành công!'];
    }
    if ($action === 'delete') {
        $customers = array_values(array_filter(getAllCustomers(), fn($c) => $c['id'] !== $_POST['id']));
        writeJSON(CUSTOMERS_FILE, $customers);
        $flash = ['type'=>'success','msg'=>'✅ Đã xoá khách hàng.'];
    }
}

$customers = getAllCustomers();
$search = $_GET['q'] ?? '';
$filterType = $_GET['type'] ?? '';
if ($search)     $customers = array_filter($customers, fn($c) => stripos($c['name'].$c['phone'], $search) !== false);
if ($filterType) $customers = array_filter($customers, fn($c) => ($c['type']??'retail') === $filterType);

// Lấy số đơn hàng từng KH
$allOrders = getAllOrders();
$orderCount = [];
$orderTotal = [];
foreach ($allOrders as $o) {
    if (!empty($o['customer_id'])) {
        $orderCount[$o['customer_id']] = ($orderCount[$o['customer_id']] ?? 0) + 1;
        $orderTotal[$o['customer_id']] = ($orderTotal[$o['customer_id']] ?? 0) + $o['total'];
    }
}

include __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?>
<div class="flash-msg flash-<?= $flash['type'] ?>"><?= $flash['msg'] ?></div>
<?php endif; ?>

<div class="card mb-3">
  <div class="card-header">
    <i class="bi bi-person-plus" style="color:#E85D04"></i> Thêm khách hàng mới
    <button class="btn btn-sm btn-primary ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#addForm">
      <i class="bi bi-plus-lg"></i> Thêm mới
    </button>
  </div>
  <div class="collapse" id="addForm">
    <div style="padding:20px">
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="row g-3">
          <div class="col-md-3"><label class="form-label">Họ tên *</label><input name="name" class="form-control" required placeholder="Nguyễn Văn A"></div>
          <div class="col-md-2"><label class="form-label">Số điện thoại *</label><input name="phone" class="form-control" required placeholder="0901234567"></div>
          <div class="col-md-2"><label class="form-label">Email</label><input name="email" type="email" class="form-control" placeholder="abc@email.com"></div>
          <div class="col-md-2"><label class="form-label">Loại khách</label>
            <select name="type" class="form-select">
              <option value="retail">Khách lẻ</option>
              <option value="wholesale">Khách sỉ</option>
              <option value="vip">VIP</option>
            </select>
          </div>
          <div class="col-md-3"><label class="form-label">Địa chỉ</label><input name="address" class="form-control" placeholder="Địa chỉ…"></div>
          <div class="col-md-6"><label class="form-label">Ghi chú</label><input name="note" class="form-control" placeholder="Ghi chú…"></div>
        </div>
        <button class="btn btn-primary mt-3"><i class="bi bi-plus-lg me-1"></i>Thêm khách hàng</button>
      </form>
    </div>
  </div>
</div>

<!-- FILTER -->
<div class="card mb-3">
  <div style="padding:14px 20px;display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;flex:1">
      <input name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" style="max-width:240px" placeholder="🔍 Tìm tên, SĐT…">
      <select name="type" class="form-select" style="max-width:150px">
        <option value="">Tất cả loại</option>
        <option value="retail"    <?= $filterType==='retail'?'selected':'' ?>>Khách lẻ</option>
        <option value="wholesale" <?= $filterType==='wholesale'?'selected':'' ?>>Khách sỉ</option>
        <option value="vip"       <?= $filterType==='vip'?'selected':'' ?>>VIP</option>
      </select>
      <button class="btn btn-outline-primary">Lọc</button>
      <?php if ($search||$filterType): ?><a href="customers.php" class="btn btn-outline-secondary">✕</a><?php endif; ?>
    </form>
    <span style="color:#aaa;font-size:13px"><?= count($customers) ?> khách hàng</span>
  </div>
</div>

<div class="card">
  <div class="table-wrapper">
    <table class="table table-hover">
      <thead>
        <tr><th>#</th><th>Tên khách hàng</th><th>SĐT</th><th>Địa chỉ</th><th>Loại</th><th>Số đơn</th><th>Tổng mua</th><th>Điểm</th><th>Ngày tạo</th><th>Thao tác</th></tr>
      </thead>
      <tbody>
      <?php $i=1; foreach ($customers as $c): ?>
        <tr>
          <td style="color:#aaa"><?= $i++ ?></td>
          <td>
            <div style="font-weight:600"><?= htmlspecialchars($c['name']) ?></div>
            <?php if (!empty($c['email'])): ?><div style="font-size:11px;color:#aaa"><?= htmlspecialchars($c['email']) ?></div><?php endif; ?>
          </td>
          <td><?= htmlspecialchars($c['phone']) ?></td>
          <td style="font-size:12px;color:#aaa"><?= htmlspecialchars($c['address'] ?? '—') ?></td>
          <td>
            <?php $typeBadge = ['retail'=>['Lẻ','badge-secondary'],'wholesale'=>['Sỉ','badge-info'],'vip'=>['VIP','badge-warning']]; ?>
            <?php [$tlabel, $tclass] = $typeBadge[$c['type']??'retail'] ?? ['Lẻ','badge-secondary']; ?>
            <span class="badge <?= $tclass ?>"><?= $tlabel ?></span>
          </td>
          <td style="text-align:center"><?= $orderCount[$c['id']] ?? 0 ?></td>
          <td style="font-weight:600;color:#E85D04"><?= vnd($orderTotal[$c['id']] ?? 0) ?></td>
          <td><span class="badge badge-success">⭐ <?= $c['points'] ?? 0 ?></span></td>
          <td style="color:#aaa;font-size:12px"><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
          <td>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $c['id'] ?>">
              <button class="btn btn-sm btn-outline-danger" onclick="return confirmDelete('Xoá «<?= htmlspecialchars($c['name']) ?>»?')">
                <i class="bi bi-trash"></i>
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($customers)): ?>
        <tr><td colspan="10" style="text-align:center;padding:30px;color:#aaa">Không có khách hàng nào</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
