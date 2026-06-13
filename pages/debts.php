<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$pageTitle  = 'Quản lý Công nợ';
$activePage = 'debts';
$flash = '';

// ── XỬ LÝ POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // THÊM MỚI
    if ($action === 'add') {
        $amount = floatval($_POST['amount'] ?? 0);
        if ($amount <= 0) {
            $flash = ['type'=>'error','msg'=>'❌ Số tiền phải lớn hơn 0!'];
        } else {
            addDebt([
                'type'        => $_POST['type']        ?? 'receivable',
                'customer_id' => $_POST['customer_id'] ?? '',
                'customer'    => trim($_POST['customer']),
                'amount'      => $amount,
                'due_date'    => $_POST['due_date']    ?? '',
                'note'        => trim($_POST['note']   ?? ''),
            ]);
            $flash = ['type'=>'success','msg'=>'✅ Thêm công nợ thành công!'];
        }
    }

    // THANH TOÁN
    if ($action === 'pay') {
        $amount = floatval($_POST['pay_amount'] ?? 0);
        $id     = $_POST['id'] ?? '';
        $debt   = getDebtById($id);
        if ($amount <= 0) {
            $flash = ['type'=>'error','msg'=>'❌ Số tiền thanh toán phải lớn hơn 0!'];
        } elseif (!$debt) {
            $flash = ['type'=>'error','msg'=>'❌ Không tìm thấy công nợ!'];
        } else {
            $remaining = floatval($debt['amount'] ?? 0) - floatval($debt['paid_amount'] ?? 0);
            if ($amount > $remaining) $amount = $remaining;
            payDebt($id, $amount);
            addDebtHistory($id, $amount, trim($_POST['pay_note'] ?? ''));
            addCashFlow([
                'type'     => ($debt['type'] ?? '') === 'receivable' ? 'income' : 'expense',
                'amount'   => $amount,
                'category' => 'Thanh toán công nợ',
                'note'     => "TT CN #{$id} - " . ($debt['customer'] ?? '') . (!empty($_POST['pay_note']) ? ' — ' . $_POST['pay_note'] : ''),
            ]);
            $flash = ['type'=>'success','msg'=>'✅ Ghi nhận thanh toán ' . vnd($amount) . ' thành công!'];
        }
    }

    // CHỈNH SỬA
    if ($action === 'edit') {
        $id = $_POST['id'] ?? '';
        updateDebt($id, [
            'customer'  => trim($_POST['customer']),
            'amount'    => floatval($_POST['amount'] ?? 0),
            'due_date'  => $_POST['due_date'] ?? '',
            'note'      => trim($_POST['note'] ?? ''),
            'type'      => $_POST['type'] ?? 'receivable',
        ]);
        $flash = ['type'=>'success','msg'=>'✅ Cập nhật công nợ thành công!'];
    }

    // XOÁ
    if ($action === 'delete') {
        $id   = $_POST['id'] ?? '';
        $debt = getDebtById($id);
        if ($debt && ($debt['status'] ?? '') !== 'paid') {
            deleteDebt($id);
            $flash = ['type'=>'success','msg'=>'✅ Đã xoá công nợ.'];
        } else {
            $flash = ['type'=>'error','msg'=>'❌ Không thể xoá công nợ đã thanh toán!'];
        }
    }

    // ĐÁNH DẤU ĐÃ XỬ LÝ (bypass)
    if ($action === 'mark_paid') {
        $id = $_POST['id'] ?? '';
        updateDebt($id, ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')]);
        $flash = ['type'=>'success','msg'=>'✅ Đã đánh dấu thanh toán đủ!'];
    }
}

// ── DỮ LIỆU ─────────────────────────────────────────────────
$allDebts = array_reverse(getAllDebts());
$customers = getAllCustomers();

// Filter & Search
$filterType   = $_GET['type']   ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterDue    = $_GET['due']    ?? '';   // overdue | this_week | this_month
$search       = $_GET['q']      ?? '';

$debts = $allDebts;
if ($filterType)   $debts = array_filter($debts, fn($d) => ($d['type']   ?? '') === $filterType);
if ($filterStatus) $debts = array_filter($debts, fn($d) => ($d['status'] ?? '') === $filterStatus);
if ($search)       $debts = searchDebts($search);

// Filter theo hạn
$today = date('Y-m-d');
if ($filterDue === 'overdue') {
    $debts = array_filter($debts, fn($d) =>
        !empty($d['due_date']) && ($d['status'] ?? '') !== 'paid' && $d['due_date'] < $today);
} elseif ($filterDue === 'this_week') {
    $endWeek = date('Y-m-d', strtotime('+7 days'));
    $debts = array_filter($debts, fn($d) =>
        !empty($d['due_date']) && ($d['status'] ?? '') !== 'paid'
        && $d['due_date'] >= $today && $d['due_date'] <= $endWeek);
} elseif ($filterDue === 'this_month') {
    $endMonth = date('Y-m-t');
    $debts = array_filter($debts, fn($d) =>
        !empty($d['due_date']) && ($d['status'] ?? '') !== 'paid'
        && str_starts_with($d['due_date'] ?? '', date('Y-m')));
}

$stats     = getDebtStats();
$totalRecv = $stats['total_recv'];
$totalPay  = $stats['total_pay'];

include __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?>
<div class="flash-msg flash-<?= $flash['type'] ?>"><?= $flash['msg'] ?></div>
<?php endif; ?>

<!-- ── STAT CARDS ─────────────────────────────────────────── -->
<div class="row g-3 mb-3">
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon red">📥</div>
      <div>
        <div class="stat-label">Phải thu</div>
        <div class="stat-value" style="font-size:16px;color:#ef4444"><?= vnd($totalRecv) ?></div>
        <div class="stat-sub">KH nợ cửa hàng</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon orange">📤</div>
      <div>
        <div class="stat-label">Phải trả</div>
        <div class="stat-value" style="font-size:16px;color:#F59E0B"><?= vnd($totalPay) ?></div>
        <div class="stat-sub">Nợ nhà cung cấp</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon red">⚠️</div>
      <div>
        <div class="stat-label">Quá hạn</div>
        <div class="stat-value" style="font-size:16px;color:#ef4444"><?= $stats['count_overdue'] ?> khoản</div>
        <div class="stat-sub"><?= vnd($stats['overdue_recv'] + $stats['overdue_pay']) ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon green">✅</div>
      <div>
        <div class="stat-label">Đã thanh toán</div>
        <div class="stat-value"><?= $stats['count_paid'] ?></div>
        <div class="stat-sub">Còn lại: <?= $stats['count_unpaid'] + $stats['count_partial'] ?> khoản</div>
      </div>
    </div>
  </div>
</div>

<!-- ── THÊM CÔNG NỢ ──────────────────────────────────────── -->
<div class="card mb-3">
  <div class="card-header">
    <i class="bi bi-plus-circle" style="color:#E85D04"></i> Thêm công nợ thủ công
    <button class="btn btn-sm btn-primary ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#addForm">
      <i class="bi bi-plus-lg"></i> Thêm mới
    </button>
  </div>
  <div class="collapse" id="addForm">
    <div style="padding:20px">
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="row g-3">
          <div class="col-md-2">
            <label class="form-label">Loại <span style="color:red">*</span></label>
            <select name="type" class="form-select" id="addType" onchange="toggleCustomerSelect(this.value)">
              <option value="receivable">📥 Phải thu (KH nợ)</option>
              <option value="payable">📤 Phải trả (NCC)</option>
            </select>
          </div>
          <div class="col-md-3" id="customerSelectWrap">
            <label class="form-label">Chọn từ danh sách</label>
            <select class="form-select" onchange="fillCustomer(this)">
              <option value="">-- Chọn khách hàng --</option>
              <?php foreach ($customers as $c): ?>
              <option value="<?= htmlspecialchars($c['id']) ?>" data-name="<?= htmlspecialchars($c['name']) ?>">
                <?= htmlspecialchars($c['name']) ?> (<?= $c['phone'] ?? '' ?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Tên đối tác <span style="color:red">*</span></label>
            <input name="customer" id="customerName" class="form-control" required placeholder="Tên KH / NCC...">
            <input type="hidden" name="customer_id" id="customerId">
          </div>
          <div class="col-md-2">
            <label class="form-label">Số tiền (đ) <span style="color:red">*</span></label>
            <input name="amount" type="number" class="form-control" required min="1" placeholder="0">
          </div>
          <div class="col-md-2">
            <label class="form-label">Hạn trả</label>
            <input name="due_date" type="date" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Ghi chú</label>
            <input name="note" class="form-control" placeholder="Lý do, mô tả khoản nợ...">
          </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:16px">
          <button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Thêm công nợ</button>
          <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#addForm">Huỷ</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── FILTER / SEARCH ───────────────────────────────────── -->
<div class="card mb-3">
  <div style="padding:14px 20px">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <input name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" style="max-width:220px" placeholder="🔍 Tìm tên, mã CN...">
      <select name="type" class="form-select" style="max-width:170px">
        <option value="">Tất cả loại</option>
        <option value="receivable" <?= $filterType==='receivable'?'selected':'' ?>>📥 Phải thu</option>
        <option value="payable"    <?= $filterType==='payable'?'selected':'' ?>>📤 Phải trả</option>
      </select>
      <select name="status" class="form-select" style="max-width:160px">
        <option value="">Tất cả trạng thái</option>
        <option value="unpaid"  <?= $filterStatus==='unpaid'?'selected':'' ?>>Chưa trả</option>
        <option value="partial" <?= $filterStatus==='partial'?'selected':'' ?>>Trả 1 phần</option>
        <option value="paid"    <?= $filterStatus==='paid'?'selected':'' ?>>Đã trả</option>
      </select>
      <select name="due" class="form-select" style="max-width:160px">
        <option value="">Tất cả hạn</option>
        <option value="overdue"     <?= $filterDue==='overdue'?'selected':'' ?>>⚠️ Quá hạn</option>
        <option value="this_week"   <?= $filterDue==='this_week'?'selected':'' ?>>📅 7 ngày tới</option>
        <option value="this_month"  <?= $filterDue==='this_month'?'selected':'' ?>>🗓 Tháng này</option>
      </select>
      <button class="btn btn-outline-primary">Lọc</button>
      <?php if ($filterType||$filterStatus||$filterDue||$search): ?>
        <a href="debts.php" class="btn btn-outline-secondary">✕ Xoá lọc</a>
      <?php endif; ?>
      <span style="margin-left:auto;color:#aaa;font-size:13px"><?= count($debts) ?> khoản nợ</span>
    </form>
  </div>
</div>

<!-- ── BẢNG CÔNG NỢ ──────────────────────────────────────── -->
<div class="card">
  <div class="table-wrapper">
    <table class="table table-hover">
      <thead>
        <tr>
          <th>Mã CN</th><th>Loại</th><th>Đối tác</th>
          <th>Tổng nợ</th><th>Đã trả</th><th>Còn lại</th>
          <th>Hạn trả</th><th>Trạng thái</th><th>Ghi chú</th>
          <th style="min-width:140px">Thao tác</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach (array_values($debts) as $d):
        $did       = $d['id']          ?? '—';
        $dtype     = $d['type']        ?? 'receivable';
        $dcustomer = $d['customer']    ?? '—';
        $damount   = floatval($d['amount']      ?? 0);
        $dpaid     = floatval($d['paid_amount'] ?? 0);
        $dstatus   = $d['status']      ?? 'unpaid';
        $ddue      = $d['due_date']    ?? '';
        $dnote     = $d['note']        ?? '';
        $remaining = $damount - $dpaid;
        $pct       = $damount > 0 ? min(100, round($dpaid / $damount * 100)) : 0;
        $overdue   = !empty($ddue) && $dstatus !== 'paid' && $ddue < $today;
        $dueIn7    = !empty($ddue) && $dstatus !== 'paid' && $ddue >= $today && $ddue <= date('Y-m-d', strtotime('+7 days'));
      ?>
        <tr style="<?= $overdue ? 'background:#FEF2F2' : ($dueIn7 ? 'background:#FFFBEB' : '') ?>">
          <td>
            <a href="#" style="color:#E85D04;font-weight:700;text-decoration:none"
               onclick="showHistory('<?= htmlspecialchars($did) ?>','<?= htmlspecialchars($dcustomer) ?>')"
               title="Xem lịch sử thanh toán">
              #<?= htmlspecialchars($did) ?>
            </a>
          </td>
          <td>
            <?php if ($dtype === 'receivable'): ?>
              <span class="badge badge-danger">📥 Phải thu</span>
            <?php else: ?>
              <span class="badge badge-warning">📤 Phải trả</span>
            <?php endif; ?>
          </td>
          <td style="font-weight:600"><?= htmlspecialchars($dcustomer) ?></td>
          <td style="font-weight:700"><?= vnd($damount) ?></td>
          <td>
            <div style="color:#10b981;font-weight:600"><?= vnd($dpaid) ?></div>
            <?php if ($dpaid > 0 && $dstatus !== 'paid'): ?>
            <div style="background:#E5E7EB;border-radius:4px;height:4px;margin-top:3px;overflow:hidden">
              <div style="width:<?= $pct ?>%;height:100%;background:#10b981;border-radius:4px"></div>
            </div>
            <div style="font-size:10px;color:#aaa"><?= $pct ?>%</div>
            <?php endif; ?>
          </td>
          <td style="font-weight:700;color:<?= $remaining > 0 ? '#ef4444':'#10b981' ?>"><?= vnd($remaining) ?></td>
          <td>
            <?php if (!empty($ddue)): ?>
              <span style="font-size:12px;font-weight:<?= $overdue||$dueIn7?'700':'400' ?>;color:<?= $overdue?'#ef4444':($dueIn7?'#F59E0B':'#aaa') ?>">
                <?= $overdue?'⚠️ ':($dueIn7?'🔔 ':'') ?><?= date('d/m/Y', strtotime($ddue)) ?>
              </span>
            <?php else: ?>
              <span style="color:#aaa;font-size:12px">—</span>
            <?php endif; ?>
          </td>
          <td><?= statusBadge($dstatus) ?></td>
          <td style="font-size:12px;color:#aaa;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
              title="<?= htmlspecialchars($dnote) ?>">
            <?= $dnote ? htmlspecialchars($dnote) : '—' ?>
          </td>
          <td>
            <div style="display:flex;gap:4px;flex-wrap:wrap">
              <?php if ($dstatus !== 'paid'): ?>
              <!-- Thanh toán -->
              <button class="btn btn-sm btn-primary" title="Ghi nhận thanh toán"
                      data-bs-toggle="modal" data-bs-target="#payModal"
                      onclick="setPayData('<?= htmlspecialchars($did) ?>','<?= htmlspecialchars($dtype) ?>','<?= htmlspecialchars($dcustomer) ?>',<?= $remaining ?>)">
                💳
              </button>
              <!-- Sửa -->
              <button class="btn btn-sm btn-outline-primary" title="Chỉnh sửa"
                      data-bs-toggle="modal" data-bs-target="#editModal"
                      onclick="setEditData(<?= htmlspecialchars(json_encode($d)) ?>)">
                <i class="bi bi-pencil"></i>
              </button>
              <!-- Đánh dấu trả đủ -->
              <form method="POST" style="display:inline" onsubmit="return confirm('Đánh dấu đã thanh toán đủ?')">
                <input type="hidden" name="action" value="mark_paid">
                <input type="hidden" name="id" value="<?= htmlspecialchars($did) ?>">
                <button class="btn btn-sm btn-outline-success" title="Đánh dấu trả đủ">✓</button>
              </form>
              <?php endif; ?>
              <!-- Lịch sử -->
              <button class="btn btn-sm btn-outline-secondary" title="Lịch sử thanh toán"
                      onclick="showHistory('<?= htmlspecialchars($did) ?>','<?= htmlspecialchars($dcustomer) ?>')">
                <i class="bi bi-clock-history"></i>
              </button>
              <!-- Xoá -->
              <?php if ($dstatus !== 'paid'): ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Xác nhận xoá công nợ này?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= htmlspecialchars($did) ?>">
                <button class="btn btn-sm btn-outline-danger" title="Xoá"><i class="bi bi-trash"></i></button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($debts)): ?>
        <tr><td colspan="10" style="text-align:center;padding:40px;color:#aaa">
          <i class="bi bi-journal-check" style="font-size:40px;display:block;margin-bottom:8px"></i>
          Không có công nợ nào
        </td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── MODAL: THANH TOÁN ─────────────────────────────────── -->
<div class="modal fade" id="payModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">💳 Ghi nhận thanh toán</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="pay">
        <input type="hidden" name="id" id="payId">
        <input type="hidden" name="debt_type" id="payDebtType">
        <input type="hidden" name="customer" id="payCustomer">
        <div class="modal-body">
          <div style="padding:14px;background:var(--surface-2);border-radius:10px;margin-bottom:16px">
            <div style="font-size:12px;color:#aaa">Đối tác</div>
            <div id="payCustomerName" style="font-weight:700;font-size:15px"></div>
            <div style="display:flex;gap:24px;margin-top:10px">
              <div>
                <div style="font-size:12px;color:#aaa">Tổng nợ</div>
                <div id="payTotal" style="font-weight:700;font-size:16px"></div>
              </div>
              <div>
                <div style="font-size:12px;color:#aaa">Còn lại</div>
                <div id="payRemaining" style="font-weight:700;font-size:16px;color:#ef4444"></div>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Số tiền thanh toán <span style="color:red">*</span></label>
            <input name="pay_amount" id="payAmount" type="number" class="form-control" required min="1"
                   oninput="updatePayChange()">
            <div id="payChangeInfo" style="margin-top:6px;font-size:13px;color:#10b981;font-weight:600"></div>
          </div>
          <div class="mb-3">
            <label class="form-label">Hình thức thanh toán</label>
            <select name="pay_method" class="form-select">
              <option value="cash">💵 Tiền mặt</option>
              <option value="transfer">🏦 Chuyển khoản</option>
              <option value="other">Khác</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Ghi chú thanh toán</label>
            <input name="pay_note" class="form-control" placeholder="VD: Thanh toán đợt 1, chuyển khoản VCB...">
          </div>
          <!-- Nhanh trả đủ -->
          <div style="display:flex;gap:8px">
            <button type="button" class="btn btn-sm btn-outline-success" onclick="setPayFull()">✓ Trả đủ toàn bộ</button>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="setPayHalf()">½ Trả nửa</button>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Huỷ</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Xác nhận</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── MODAL: CHỈNH SỬA ─────────────────────────────────── -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">✏️ Chỉnh sửa công nợ</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="editId">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Loại công nợ</label>
              <select name="type" id="editType" class="form-select">
                <option value="receivable">📥 Phải thu</option>
                <option value="payable">📤 Phải trả</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Tên đối tác</label>
              <input name="customer" id="editCustomer" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Số tiền (đ)</label>
              <input name="amount" id="editAmount" type="number" class="form-control" required min="1">
            </div>
            <div class="col-md-6">
              <label class="form-label">Hạn trả</label>
              <input name="due_date" id="editDueDate" type="date" class="form-control">
            </div>
            <div class="col-12">
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

<!-- ── MODAL: LỊCH SỬ THANH TOÁN ────────────────────────── -->
<div class="modal fade" id="historyModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="historyTitle">📋 Lịch sử thanh toán</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="historyBody">
        <div style="text-align:center;padding:30px;color:#aaa">Đang tải...</div>
      </div>
    </div>
  </div>
</div>

<?php
// Chuẩn bị dữ liệu lịch sử cho JS
$historyFile = DATA_PATH . 'debt_history.json';
$allHistory  = file_exists($historyFile)
    ? (json_decode(file_get_contents($historyFile), true) ?? [])
    : [];
$historyByDebt = [];
foreach ($allHistory as $h) {
    $historyByDebt[$h['debt_id'] ?? ''][] = $h;
}

$extraJs = '<script>
// ── PAY MODAL ───────────────────────────────────────────────
let _payRemaining = 0;
function setPayData(id, type, customer, remaining) {
  _payRemaining = remaining;
  document.getElementById("payId").value       = id;
  document.getElementById("payDebtType").value = type;
  document.getElementById("payCustomer").value = customer;
  document.getElementById("payCustomerName").textContent = customer;
  document.getElementById("payTotal").textContent     = new Intl.NumberFormat("vi-VN").format(remaining) + " đ";
  document.getElementById("payRemaining").textContent = new Intl.NumberFormat("vi-VN").format(remaining) + " đ";
  document.getElementById("payAmount").value = remaining;
  document.getElementById("payAmount").max   = remaining;
  updatePayChange();
}
function updatePayChange() {
  const amt = parseFloat(document.getElementById("payAmount").value) || 0;
  const info = document.getElementById("payChangeInfo");
  if (amt <= 0) { info.textContent = ""; return; }
  if (amt >= _payRemaining) {
    info.textContent = "✓ Thanh toán toàn bộ";
    info.style.color = "#10b981";
  } else {
    const left = _payRemaining - amt;
    info.textContent = "Còn lại sau khi trả: " + new Intl.NumberFormat("vi-VN").format(left) + " đ";
    info.style.color = "#F59E0B";
  }
}
function setPayFull()  { document.getElementById("payAmount").value = _payRemaining; updatePayChange(); }
function setPayHalf()  { document.getElementById("payAmount").value = Math.round(_payRemaining / 2); updatePayChange(); }

// ── EDIT MODAL ──────────────────────────────────────────────
function setEditData(d) {
  document.getElementById("editId").value       = d.id       ?? "";
  document.getElementById("editCustomer").value = d.customer ?? "";
  document.getElementById("editAmount").value   = d.amount   ?? 0;
  document.getElementById("editDueDate").value  = d.due_date ?? "";
  document.getElementById("editNote").value     = d.note     ?? "";
  const sel = document.getElementById("editType");
  for (let o of sel.options) { if (o.value === (d.type ?? "receivable")) { o.selected = true; break; } }
}

// ── HISTORY MODAL ───────────────────────────────────────────
const allHistory = ' . json_encode($historyByDebt) . ';
function showHistory(id, customer) {
  document.getElementById("historyTitle").textContent = "📋 Lịch sử thanh toán — " + customer;
  const logs = allHistory[id] || [];
  const modal = new bootstrap.Modal(document.getElementById("historyModal"));
  if (logs.length === 0) {
    document.getElementById("historyBody").innerHTML = "<div style=\"text-align:center;padding:30px;color:#aaa\"><i class=\"bi bi-clock\" style=\"font-size:32px;display:block;margin-bottom:8px\"></i>Chưa có lịch sử thanh toán</div>";
  } else {
    const fmtVND = v => new Intl.NumberFormat("vi-VN").format(v) + " đ";
    const rows = logs.slice().reverse().map(h => `
      <tr>
        <td style="color:#aaa;font-size:12px">${h.created_at ?? ""}</td>
        <td style="font-weight:700;color:#10b981">+${fmtVND(h.amount ?? 0)}</td>
        <td>${h.note || "—"}</td>
        <td style="font-size:12px;color:#aaa">${h.user ?? ""}</td>
      </tr>`).join("");
    document.getElementById("historyBody").innerHTML = `
      <table class="table table-sm">
        <thead><tr><th>Thời gian</th><th>Số tiền</th><th>Ghi chú</th><th>Nhân viên</th></tr></thead>
        <tbody>${rows}</tbody>
      </table>`;
  }
  modal.show();
}

// ── ADD FORM: chọn KH từ danh sách ─────────────────────────
function fillCustomer(sel) {
  const opt = sel.options[sel.selectedIndex];
  if (opt.value) {
    document.getElementById("customerName").value = opt.dataset.name || opt.text.split("(")[0].trim();
    document.getElementById("customerId").value   = opt.value;
  }
}
function toggleCustomerSelect(type) {
  const wrap = document.getElementById("customerSelectWrap");
  wrap.style.display = type === "receivable" ? "" : "none";
}
toggleCustomerSelect("receivable");
</script>';
include __DIR__ . '/../includes/footer.php';
?>