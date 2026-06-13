<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$pageTitle  = 'Thu / Chi';
$activePage = 'cashflow';
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        addCashFlow([
            'type'     => $_POST['type'],
            'amount'   => floatval($_POST['amount']),
            'category' => trim($_POST['category']),
            'note'     => trim($_POST['note'] ?? ''),
        ]);
        $flash = ['type'=>'success','msg'=>'✅ Ghi nhận thành công!'];
    }
    if ($action === 'delete') {
        $flows = array_values(array_filter(getAllCashFlow(), fn($f) => $f['id'] !== $_POST['id']));
        writeJSON(CASHFLOW_FILE, $flows);
        $flash = ['type'=>'success','msg'=>'✅ Đã xoá.'];
    }
}

$flows       = array_reverse(getAllCashFlow());
$filterType  = $_GET['type'] ?? '';
$filterDate  = $_GET['date'] ?? '';
$filterMonth = $_GET['month'] ?? '';

if ($filterType)  $flows = array_filter($flows, fn($f) => $f['type'] === $filterType);
if ($filterDate)  $flows = array_filter($flows, fn($f) => str_starts_with($f['created_at'], $filterDate));
if ($filterMonth) $flows = array_filter($flows, fn($f) => str_starts_with($f['created_at'], $filterMonth));

$allFlows = getAllCashFlow();
$totalIn  = array_sum(array_map(fn($f) => $f['type']==='income'  ? $f['amount'] : 0, $allFlows));
$totalOut = array_sum(array_map(fn($f) => $f['type']==='expense' ? $f['amount'] : 0, $allFlows));
$balance  = $totalIn - $totalOut;

$incomeCategories  = ['Bán hàng','Thanh toán công nợ','Vay vốn','Khác'];
$expenseCategories = ['Nhập kho','Lương nhân viên','Thuê mặt bằng','Điện nước','Vận chuyển','Khác'];

include __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?>
<div class="flash-msg flash-<?= $flash['type'] ?>"><?= $flash['msg'] ?></div>
<?php endif; ?>

<!-- SUMMARY -->
<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="stat-card">
      <div class="stat-icon green">💵</div>
      <div>
        <div class="stat-label">Tổng thu</div>
        <div class="stat-value" style="font-size:18px;color:#10b981"><?= vnd($totalIn) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card">
      <div class="stat-icon red">💸</div>
      <div>
        <div class="stat-label">Tổng chi</div>
        <div class="stat-value" style="font-size:18px;color:#ef4444"><?= vnd($totalOut) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card">
      <div class="stat-icon <?= $balance >= 0 ? 'blue':'red' ?>">🏦</div>
      <div>
        <div class="stat-label">Tồn quỹ</div>
        <div class="stat-value" style="font-size:18px;color:<?= $balance >= 0 ? '#3B82F6':'#ef4444' ?>"><?= vnd(abs($balance)) ?></div>
        <div class="stat-sub"><?= $balance >= 0 ? 'Dương quỹ' : 'Âm quỹ' ?></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <!-- ADD FORM -->
  <div class="col-md-4">
    <div class="card">
      <div class="card-header"><i class="bi bi-plus-circle" style="color:#E85D04"></i> Ghi thu / chi</div>
      <div style="padding:20px">
        <form method="POST" id="cashForm">
          <input type="hidden" name="action" value="add">
          <div class="mb-3">
            <label class="form-label">Loại *</label>
            <div style="display:flex;gap:10px">
              <label style="flex:1;cursor:pointer;padding:10px;border:2px solid var(--border);border-radius:8px;text-align:center" id="lblIncome">
                <input type="radio" name="type" value="income" checked onchange="switchType('income')" style="display:none">
                <span style="color:#10b981;font-weight:700">💵 Thu tiền</span>
              </label>
              <label style="flex:1;cursor:pointer;padding:10px;border:2px solid var(--border);border-radius:8px;text-align:center" id="lblExpense">
                <input type="radio" name="type" value="expense" onchange="switchType('expense')" style="display:none">
                <span style="color:#ef4444;font-weight:700">💸 Chi tiền</span>
              </label>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Danh mục *</label>
            <select name="category" id="catSelect" class="form-select" required>
              <?php foreach ($incomeCategories as $c): ?><option><?= $c ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Số tiền *</label>
            <input name="amount" type="number" class="form-control" min="0" required placeholder="0">
          </div>
          <div class="mb-3">
            <label class="form-label">Ghi chú</label>
            <input name="note" class="form-control" placeholder="Mô tả…">
          </div>
          <button class="btn btn-primary w-100">Ghi nhận</button>
        </form>
      </div>
    </div>
  </div>

  <!-- FILTER + TABLE -->
  <div class="col-md-8">
    <div class="card">
      <div style="padding:14px 16px;border-bottom:1px solid var(--border)">
        <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
          <select name="type" class="form-select" style="max-width:140px">
            <option value="">Tất cả</option>
            <option value="income"  <?= $filterType==='income'?'selected':'' ?>>💵 Thu</option>
            <option value="expense" <?= $filterType==='expense'?'selected':'' ?>>💸 Chi</option>
          </select>
          <input name="month" type="month" class="form-control" style="max-width:160px" value="<?= $filterMonth ?>">
          <input name="date"  type="date"  class="form-control" style="max-width:160px" value="<?= $filterDate ?>">
          <button class="btn btn-outline-primary">Lọc</button>
          <?php if ($filterType||$filterDate||$filterMonth): ?><a href="cashflow.php" class="btn btn-outline-secondary">✕</a><?php endif; ?>
        </form>
      </div>
      <div class="table-wrapper" style="max-height:460px;overflow-y:auto">
        <table class="table table-hover">
          <thead style="position:sticky;top:0">
            <tr><th>Thời gian</th><th>Loại</th><th>Danh mục</th><th>Số tiền</th><th>Ghi chú</th><th>NV</th><th></th></tr>
          </thead>
          <tbody>
          <?php foreach (array_values($flows) as $f):
            $fid      = $f['id']       ?? '';
            $ftype    = $f['type']     ?? 'income';
            $fcat     = $f['category'] ?? '—';
            $famount  = floatval($f['amount'] ?? 0);
            $fnote    = $f['note']     ?? '—';
            $fuser    = $f['user']     ?? '';
            $fcreated = $f['created_at'] ?? date('Y-m-d H:i:s');
          ?>
            <tr>
              <td style="color:#aaa;font-size:12px"><?= date('d/m H:i', strtotime($fcreated)) ?></td>
              <td>
                <?php if ($ftype === 'income'): ?>
                  <span class="badge badge-success">💵 Thu</span>
                <?php else: ?>
                  <span class="badge badge-danger">💸 Chi</span>
                <?php endif; ?>
              </td>
              <td style="font-size:13px"><?= htmlspecialchars($fcat) ?></td>
              <td style="font-weight:700;color:<?= $ftype==='income'?'#10b981':'#ef4444' ?>">
                <?= $ftype==='income'?'+':'-' ?><?= vnd($famount) ?>
              </td>
              <td style="font-size:12px;color:#aaa;max-width:200px"><?= htmlspecialchars($fnote) ?></td>
              <td style="font-size:12px"><?= htmlspecialchars($fuser) ?></td>
              <td>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= htmlspecialchars($fid) ?>">
                  <button class="btn btn-sm btn-outline-danger" onclick="return confirmDelete()"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($flows)): ?>
            <tr><td colspan="7" style="text-align:center;padding:30px;color:#aaa">Không có dữ liệu</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php
$incomeJson  = json_encode(array_values($incomeCategories));
$expenseJson = json_encode(array_values($expenseCategories));
$extraJs = "<script>
const incomeCategories  = $incomeJson;
const expenseCategories = $expenseJson;

function switchType(type) {
  const sel = document.getElementById('catSelect');
  const cats = type === 'income' ? incomeCategories : expenseCategories;
  sel.innerHTML = cats.map(c => `<option>\${c}</option>`).join('');
  document.getElementById('lblIncome').style.borderColor  = type==='income'  ? '#10b981' : 'var(--border)';
  document.getElementById('lblExpense').style.borderColor = type==='expense' ? '#ef4444' : 'var(--border)';
}
switchType('income');
</script>";
include __DIR__ . '/../includes/footer.php';
?>
