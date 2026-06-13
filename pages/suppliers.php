<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$pageTitle  = 'Nhà cung cấp';
$activePage = 'suppliers';
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        addSupplier([
            'name'     => trim($_POST['name']),
            'phone'    => trim($_POST['phone']),
            'address'  => trim($_POST['address'] ?? ''),
            'email'    => trim($_POST['email'] ?? ''),
            'category' => $_POST['category'],
            'contact'  => trim($_POST['contact'] ?? ''),
            'note'     => trim($_POST['note'] ?? ''),
        ]);
        $flash = ['type'=>'success','msg'=>'✅ Thêm nhà cung cấp thành công!'];
    }
    if ($action === 'delete') {
        $suppliers = array_values(array_filter(getAllSuppliers(), fn($s) => $s['id'] !== $_POST['id']));
        writeJSON(SUPPLIERS_FILE, $suppliers);
        $flash = ['type'=>'success','msg'=>'✅ Đã xoá nhà cung cấp.'];
    }
}

$suppliers  = getAllSuppliers();
$categories = ['Xi măng','Gạch ngói','Sắt thép','Sơn','Ống nước','Cát đá','Gỗ','Tổng hợp','Khác'];
$search     = $_GET['q'] ?? '';
if ($search) $suppliers = array_filter($suppliers, fn($s) => stripos($s['name'].$s['phone'].$s['category'], $search) !== false);

include __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?>
<div class="flash-msg flash-<?= $flash['type'] ?>"><?= $flash['msg'] ?></div>
<?php endif; ?>

<div class="card mb-3">
  <div class="card-header">
    <i class="bi bi-truck" style="color:#E85D04"></i> Thêm nhà cung cấp
    <button class="btn btn-sm btn-primary ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#addForm">
      <i class="bi bi-plus-lg"></i> Thêm mới
    </button>
  </div>
  <div class="collapse" id="addForm">
    <div style="padding:20px">
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="row g-3">
          <div class="col-md-3"><label class="form-label">Tên công ty / NCC *</label><input name="name" class="form-control" required placeholder="Công ty Xi măng ABC"></div>
          <div class="col-md-2"><label class="form-label">Điện thoại *</label><input name="phone" class="form-control" required placeholder="02812345678"></div>
          <div class="col-md-2"><label class="form-label">Email</label><input name="email" type="email" class="form-control"></div>
          <div class="col-md-2"><label class="form-label">Danh mục hàng</label>
            <select name="category" class="form-select">
              <?php foreach ($categories as $c): ?><option><?= $c ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3"><label class="form-label">Địa chỉ</label><input name="address" class="form-control"></div>
          <div class="col-md-3"><label class="form-label">Người liên hệ</label><input name="contact" class="form-control" placeholder="Anh/Chị…"></div>
          <div class="col-md-5"><label class="form-label">Ghi chú</label><input name="note" class="form-control"></div>
        </div>
        <button class="btn btn-primary mt-3"><i class="bi bi-plus-lg me-1"></i>Thêm nhà cung cấp</button>
      </form>
    </div>
  </div>
</div>

<div class="card mb-3">
  <div style="padding:14px 20px;display:flex;gap:10px">
    <form method="GET" style="display:flex;gap:10px;flex:1">
      <input name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" style="max-width:280px" placeholder="🔍 Tìm nhà cung cấp…">
      <button class="btn btn-outline-primary">Lọc</button>
      <?php if ($search): ?><a href="suppliers.php" class="btn btn-outline-secondary">✕</a><?php endif; ?>
    </form>
    <span style="color:#aaa;font-size:13px;align-self:center"><?= count($suppliers) ?> nhà cung cấp</span>
  </div>
</div>

<div class="card">
  <div class="table-wrapper">
    <table class="table table-hover">
      <thead>
        <tr><th>#</th><th>Tên NCC</th><th>Điện thoại</th><th>Email</th><th>Danh mục</th><th>Người liên hệ</th><th>Địa chỉ</th><th>Ngày thêm</th><th>Thao tác</th></tr>
      </thead>
      <tbody>
      <?php $i=1; foreach ($suppliers as $s):
        $sid     = $s['id']       ?? '';
        $sname   = $s['name']     ?? '—';
        $sphone  = $s['phone']    ?? '';
        $semail  = $s['email']    ?? '—';
        $scat    = $s['category'] ?? '—';
        $scontact= $s['contact']  ?? '—';
        $saddr   = $s['address']  ?? '—';
        $snote   = $s['note']     ?? '';
        $screated= $s['created_at'] ?? date('Y-m-d H:i:s');
      ?>
        <tr>
          <td style="color:#aaa"><?= $i++ ?></td>
          <td>
            <div style="font-weight:600"><?= htmlspecialchars($sname) ?></div>
            <?php if (!empty($snote)): ?><div style="font-size:11px;color:#aaa"><?= htmlspecialchars($snote) ?></div><?php endif; ?>
          </td>
          <td><?= htmlspecialchars($sphone) ?></td>
          <td style="font-size:12px;color:#aaa"><?= htmlspecialchars($semail) ?></td>
          <td><span class="badge badge-info"><?= htmlspecialchars($scat) ?></span></td>
          <td><?= htmlspecialchars($scontact) ?></td>
          <td style="font-size:12px;color:#aaa"><?= htmlspecialchars($saddr) ?></td>
          <td style="color:#aaa;font-size:12px"><?= date('d/m/Y', strtotime($screated)) ?></td>
          <td>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= htmlspecialchars($sid) ?>">
              <button class="btn btn-sm btn-outline-danger"
                      onclick="return confirmDelete('Xoá NCC «<?= htmlspecialchars($sname) ?>»?')">
                <i class="bi bi-trash"></i>
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($suppliers)): ?>
        <tr><td colspan="9" style="text-align:center;padding:30px;color:#aaa">Chưa có nhà cung cấp</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
