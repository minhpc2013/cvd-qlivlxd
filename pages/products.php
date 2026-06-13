<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$pageTitle  = 'Quản lý Sản phẩm';
$activePage = 'products';
$flash = '';

// ── XỬ LÝ FORM ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        addProduct([
            'name'       => trim($_POST['name']),
            'sku'        => strtoupper(trim($_POST['sku'] ?? '')),
            'category'   => $_POST['category'],
            'unit'       => trim($_POST['unit']),
            'price_buy'  => floatval($_POST['price_buy']),
            'price_sell' => floatval($_POST['price_sell']),
            'stock'      => intval($_POST['stock']),
            'note'       => trim($_POST['note'] ?? ''),
        ]);
        $flash = ['type'=>'success','msg'=>'✅ Thêm sản phẩm thành công!'];
    }

    if ($action === 'edit') {
        updateProduct($_POST['id'], [
            'name'       => trim($_POST['name']),
            'sku'        => strtoupper(trim($_POST['sku'] ?? '')),
            'category'   => $_POST['category'],
            'unit'       => trim($_POST['unit']),
            'price_buy'  => floatval($_POST['price_buy']),
            'price_sell' => floatval($_POST['price_sell']),
            'note'       => trim($_POST['note'] ?? ''),
        ]);
        $flash = ['type'=>'success','msg'=>'✅ Cập nhật thành công!'];
    }

    if ($action === 'delete') {
        deleteProduct($_POST['id']);
        $flash = ['type'=>'success','msg'=>'✅ Đã xoá sản phẩm.'];
    }
}

$products   = getAllProducts();
$categories = ['Xi măng','Gạch ngói','Sắt thép','Sơn','Ống nước','Cát đá','Gỗ','Kính','Khác'];

// Filter
$filterCat  = $_GET['cat'] ?? '';
$filterSearch = $_GET['q'] ?? '';
if ($filterCat)    $products = array_filter($products, fn($p) => $p['category'] === $filterCat);
if ($filterSearch) $products = array_filter($products, fn($p) => stripos($p['name'].$p['category'], $filterSearch) !== false);

include __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?>
<div class="flash-msg flash-<?= $flash['type'] ?>"><?= $flash['msg'] ?></div>
<?php endif; ?>

<div class="card mb-3">
  <div class="card-header">
    <i class="bi bi-plus-circle" style="color:#E85D04"></i> Thêm sản phẩm mới
    <button class="btn btn-sm btn-primary ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#addForm">
      <i class="bi bi-plus-lg"></i> Thêm mới
    </button>
  </div>
  <div class="collapse" id="addForm">
    <div style="padding:20px">
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Tên sản phẩm *</label>
            <input name="name" class="form-control" required placeholder="VD: Xi măng Hà Tiên PCB40">
          </div>
          <div class="col-md-2">
            <label class="form-label">Mã sản phẩm</label>
            <input name="sku" class="form-control" placeholder="VD: XM-001" oninput="this.value=this.value.toUpperCase()">
          </div>
          <div class="col-md-2">
            <label class="form-label">Danh mục *</label>
            <select name="category" class="form-select" required>
              <?php foreach ($categories as $c): ?>
              <option><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Đơn vị *</label>
            <input name="unit" class="form-control" required placeholder="bao, m², kg, cây…">
          </div>
          <div class="col-md-2">
            <label class="form-label">Giá nhập (đ)</label>
            <input name="price_buy" type="number" class="form-control" placeholder="0" min="0">
          </div>
          <div class="col-md-2">
            <label class="form-label">Giá bán (đ) *</label>
            <input name="price_sell" type="number" class="form-control" required placeholder="0" min="0">
          </div>
          <div class="col-md-2">
            <label class="form-label">Tồn kho ban đầu</label>
            <input name="stock" type="number" class="form-control" value="0" min="0">
          </div>
          <div class="col-md-6">
            <label class="form-label">Ghi chú</label>
            <input name="note" class="form-control" placeholder="Mô tả thêm…">
          </div>
        </div>
        <button class="btn btn-primary mt-3"><i class="bi bi-plus-lg me-1"></i>Thêm sản phẩm</button>
      </form>
    </div>
  </div>
</div>

<!-- FILTER / SEARCH -->
<div class="card mb-3">
  <div style="padding:14px 20px;display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <form method="GET" style="display:flex;gap:10px;flex:1;flex-wrap:wrap">
      <input name="q" value="<?= htmlspecialchars($filterSearch) ?>" class="form-control" style="max-width:260px" placeholder="🔍 Tìm sản phẩm…">
      <select name="cat" class="form-select" style="max-width:180px">
        <option value="">Tất cả danh mục</option>
        <?php foreach ($categories as $c): ?>
        <option <?= $filterCat===$c?'selected':'' ?>><?= $c ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-outline-primary">Lọc</button>
      <?php if ($filterCat || $filterSearch): ?>
        <a href="products.php" class="btn btn-outline-secondary">✕ Xoá lọc</a>
      <?php endif; ?>
    </form>
    <span style="color:#aaa;font-size:13px"><?= count($products) ?> sản phẩm</span>
  </div>
</div>

<!-- TABLE -->
<div class="card">
  <div class="table-wrapper">
    <table class="table table-hover">
      <thead>
        <tr>
          <th>#</th>
          <th>Mã SP</th>
          <th>Tên sản phẩm</th>
          <th>Danh mục</th>
          <th>Đơn vị</th>
          <th>Giá nhập</th>
          <th>Giá bán</th>
          <th>Lợi nhuận</th>
          <th>Tồn kho</th>
          <th>Thao tác</th>
        </tr>
      </thead>
      <tbody>
      <?php $i = 1; foreach ($products as $p):
        $pid       = $p['id']         ?? '';
        $pname     = $p['name']       ?? '—';
        $pcat      = $p['category']   ?? '—';
        $punit     = $p['unit']       ?? '';
        $pbuy      = floatval($p['price_buy']  ?? 0);
        $psell     = floatval($p['price_sell'] ?? 0);
        $pstock    = intval($p['stock']  ?? 0);
        $pnote     = $p['note']       ?? '';
        $psku      = $p['sku'] ?? '';
        $profit    = $psell - $pbuy;
      ?>
        <tr>
          <td style="color:#aaa"><?= $i++ ?></td>
          <td>
            <?php if (!empty($psku)): ?>
              <span style="font-size:11px;font-weight:700;background:#F3F4F6;color:#374151;padding:2px 7px;border-radius:5px;font-family:monospace"><?= htmlspecialchars($psku) ?></span>
            <?php else: ?>
              <span style="font-size:11px;color:#d1d5db">—</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="font-weight:600"><?= htmlspecialchars($pname) ?></div>
            <?php if (!empty($pnote)): ?>
              <div style="font-size:11px;color:#aaa"><?= htmlspecialchars($pnote) ?></div>
            <?php endif; ?>
          </td>
          <td><span class="badge badge-info"><?= htmlspecialchars($pcat) ?></span></td>
          <td><?= htmlspecialchars($punit) ?></td>
          <td><?= vnd($pbuy) ?></td>
          <td style="font-weight:700;color:#E85D04"><?= vnd($psell) ?></td>
          <td style="color:#10b981;font-weight:600">+<?= vnd($profit) ?></td>
          <td>
            <span class="badge badge-<?= $pstock <= 3 ? 'danger' : ($pstock <= 10 ? 'warning' : 'success') ?>">
              <?= $pstock ?> <?= htmlspecialchars($punit) ?>
            </span>
          </td>
          <td>
            <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal"
                    data-bs-target="#editModal"
                    onclick="fillEditForm(<?= htmlspecialchars(json_encode($p)) ?>)">
              <i class="bi bi-pencil"></i>
            </button>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= htmlspecialchars($pid) ?>">
              <button class="btn btn-sm btn-outline-danger"
                      onclick="return confirmDelete('Xoá sản phẩm «<?= htmlspecialchars($pname) ?>»?')">
                <i class="bi bi-trash"></i>
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($products)): ?>
        <tr><td colspan="9" style="text-align:center;padding:30px;color:#aaa">Không có sản phẩm nào</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">✏️ Chỉnh sửa sản phẩm</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="editId">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-5">
              <label class="form-label">Tên sản phẩm</label>
              <input name="name" id="editName" class="form-control" required>
            </div>
            <div class="col-md-2">
              <label class="form-label">Mã sản phẩm</label>
              <input name="sku" id="editSku" class="form-control" placeholder="VD: XM-001" oninput="this.value=this.value.toUpperCase()">
            </div>
            <div class="col-md-2">
              <label class="form-label">Danh mục</label>
              <select name="category" id="editCategory" class="form-select">
                <?php foreach ($categories as $c): ?>
                <option><?= $c ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Đơn vị</label>
              <input name="unit" id="editUnit" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Giá nhập</label>
              <input name="price_buy" id="editPriceBuy" type="number" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Giá bán</label>
              <input name="price_sell" id="editPriceSell" type="number" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Ghi chú</label>
              <input name="note" id="editNote" class="form-control">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Huỷ</button>
          <button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$extraJs = '<script>
function fillEditForm(p) {
  document.getElementById("editId").value        = p.id;
  document.getElementById("editName").value      = p.name;
  document.getElementById("editSku").value       = p.sku || "";
  document.getElementById("editUnit").value      = p.unit;
  document.getElementById("editPriceBuy").value  = p.price_buy || 0;
  document.getElementById("editPriceSell").value = p.price_sell;
  document.getElementById("editNote").value      = p.note || "";
  const sel = document.getElementById("editCategory");
  for (let o of sel.options) { if (o.value === p.category) { o.selected = true; break; } }
}
</script>';
include __DIR__ . '/../includes/footer.php';
?>