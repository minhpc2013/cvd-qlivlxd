<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$pageTitle  = 'Nhập / Xuất kho';
$activePage = 'stock';
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'adjust') {
        $pid    = $_POST['product_id'];
        $type   = $_POST['type']; // 'in' hoặc 'out'
        $qty    = intval($_POST['quantity']);
        $reason = trim($_POST['reason']);

        if ($qty <= 0) {
            $flash = ['type'=>'error','msg'=>'❌ Số lượng phải lớn hơn 0!'];
        } else {
            $p = getProductById($pid);
            if ($type === 'out' && $p && $p['stock'] < $qty) {
                $flash = ['type'=>'error','msg'=>"❌ Tồn kho không đủ! Hiện có: {$p['stock']} {$p['unit']}"];
            } else {
                $delta = $type === 'in' ? $qty : -$qty;
                updateStock($pid, $delta, $reason ?: ($type === 'in' ? 'Nhập kho thủ công' : 'Xuất kho thủ công'));

                // Ghi dòng tiền khi nhập kho
                if ($type === 'in' && !empty($_POST['price'])) {
                    $cost = floatval($_POST['price']) * $qty;
                    addCashFlow([
                        'type'     => 'expense',
                        'amount'   => $cost,
                        'category' => 'Nhập kho',
                        'note'     => "Nhập kho {$p['name']} x{$qty}",
                    ]);
                }
                $flash = ['type'=>'success','msg'=>'✅ Cập nhật kho thành công!'];
            }
        }
    }
}

$products  = getAllProducts();
$stockLogs = array_reverse(readJSON(STOCK_LOGS_FILE));
$filterPid = $_GET['pid'] ?? '';
if ($filterPid) $stockLogs = array_filter($stockLogs, fn($l) => $l['product_id'] === $filterPid);
$lowStock  = getLowStockProducts(10);

include __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?>
<div class="flash-msg flash-<?= $flash['type'] ?>"><?= $flash['msg'] ?></div>
<?php endif; ?>

<div class="row g-3 mb-3">
  <!-- FORM NHẬP/XUẤT KHO -->
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-arrow-left-right" style="color:#E85D04"></i> Điều chỉnh kho</div>
      <div style="padding:20px">
        <form method="POST">
          <input type="hidden" name="action" value="adjust">
          <div class="mb-3">
            <label class="form-label">Sản phẩm *</label>
            <select name="product_id" class="form-select" required>
              <option value="">-- Chọn sản phẩm --</option>
              <?php foreach ($products as $p):
                if (empty($p['id'])) continue; // bỏ qua sp không có id
              ?>
              <option value="<?= htmlspecialchars($p['id']) ?>" <?= $filterPid===$p['id']?'selected':'' ?>>
                <?= htmlspecialchars($p['name'] ?? '—') ?> (Tồn: <?= $p['stock'] ?? 0 ?> <?= $p['unit'] ?? '' ?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Loại thao tác *</label>
            <div style="display:flex;gap:10px">
              <label style="flex:1;cursor:pointer">
                <input type="radio" name="type" value="in" checked style="margin-right:6px">
                <span style="color:#10b981;font-weight:600">📥 Nhập kho</span>
              </label>
              <label style="flex:1;cursor:pointer">
                <input type="radio" name="type" value="out" style="margin-right:6px">
                <span style="color:#ef4444;font-weight:600">📤 Xuất kho</span>
              </label>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Số lượng *</label>
            <input name="quantity" type="number" class="form-control" min="1" required placeholder="Nhập số lượng">
          </div>
          <div class="mb-3">
            <label class="form-label">Đơn giá nhập (nếu nhập kho)</label>
            <input name="price" type="number" class="form-control" placeholder="0 đ">
          </div>
          <div class="mb-3">
            <label class="form-label">Lý do / Ghi chú</label>
            <input name="reason" class="form-control" placeholder="VD: Nhập từ NCC Hà Tiên…">
          </div>
          <button class="btn btn-primary w-100"><i class="bi bi-check-circle me-2"></i>Thực hiện</button>
        </form>
      </div>
    </div>
  </div>

  <!-- CẢNH BÁO TỒN KHO THẤP -->
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-exclamation-triangle" style="color:#F59E0B"></i> Hàng sắp hết kho
        <span class="badge badge-warning ms-2"><?= count($lowStock) ?></span>
      </div>
      <div style="padding:12px;overflow-y:auto;max-height:380px">
        <?php foreach ($lowStock as $p):
          $pid    = $p['id']       ?? '';
          $pname  = $p['name']     ?? '—';
          $pcat   = $p['category'] ?? '';
          $pstock = intval($p['stock'] ?? 0);
          $punit  = $p['unit']     ?? '';
        ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;border-radius:8px;margin-bottom:6px;background:#FFF7F0;border:1px solid #FFE4CC">
          <div>
            <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($pname) ?></div>
            <div style="font-size:11px;color:#aaa"><?= htmlspecialchars($pcat) ?></div>
          </div>
          <div style="text-align:right">
            <span class="badge badge-<?= $pstock <= 3 ? 'danger':'warning' ?>"><?= $pstock ?> <?= htmlspecialchars($punit) ?></span>
            <div style="font-size:10px;color:#aaa;margin-top:2px">
              <?php if ($pid): ?>
              <a href="?pid=<?= htmlspecialchars($pid) ?>" style="color:#E85D04">Xem log</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($lowStock)): ?>
          <div style="text-align:center;padding:30px;color:#10b981">
            <i class="bi bi-check-circle" style="font-size:32px;display:block;margin-bottom:8px"></i>
            Tồn kho ổn định!
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- TỒN KHO TỔNG QUAN -->
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-boxes" style="color:#E85D04"></i> Tổng quan kho</div>
      <div style="padding:12px;overflow-y:auto;max-height:380px">
        <?php
        $byCategory = [];
        foreach ($products as $p) {
            $cat   = $p['category'] ?? 'Khác';
            $stock = intval($p['stock'] ?? 0);
            $sell  = floatval($p['price_sell'] ?? 0);
            if (!isset($byCategory[$cat])) $byCategory[$cat] = ['count'=>0,'stock'=>0,'value'=>0];
            $byCategory[$cat]['count']++;
            $byCategory[$cat]['stock'] += $stock;
            $byCategory[$cat]['value'] += $stock * $sell;
        }
        ?>
        <?php foreach ($byCategory as $cat => $info): ?>
        <div style="padding:10px;border-radius:8px;margin-bottom:6px;background:var(--surface-2);border:1px solid var(--border)">
          <div style="display:flex;justify-content:space-between;margin-bottom:4px">
            <span style="font-weight:600;font-size:13px"><?= $cat ?></span>
            <span style="font-size:12px;color:#aaa"><?= $info['count'] ?> SP</span>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:12px">
            <span style="color:#3B82F6">Tồn: <?= number_format($info['stock']) ?></span>
            <span style="color:#10b981;font-weight:600"><?= vnd($info['value']) ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- LỊCH SỬ NHẬP XUẤT KHO -->
<div class="card">
  <div class="card-header">
    <i class="bi bi-clock-history" style="color:#E85D04"></i> Lịch sử kho
    <?php if ($filterPid): ?>
      <span style="font-size:12px;color:#aaa;margin-left:8px">Đang lọc theo sản phẩm</span>
      <a href="stock.php" class="btn btn-sm btn-outline-secondary ms-2">✕ Xoá lọc</a>
    <?php endif; ?>
    <span style="margin-left:auto;font-size:13px;color:#aaa"><?= count($stockLogs) ?> bản ghi</span>
  </div>
  <div class="table-wrapper">
    <table class="table table-hover">
      <thead>
        <tr><th>Thời gian</th><th>Sản phẩm</th><th>Tồn trước</th><th>Thay đổi</th><th>Tồn sau</th><th>Lý do</th><th>Nhân viên</th></tr>
      </thead>
      <tbody>
      <?php foreach (array_slice(array_values($stockLogs), 0, 50) as $log):
        $change  = intval($log['change']  ?? 0);
        $before  = intval($log['before']  ?? 0);
        $after   = intval($log['after']   ?? 0);
        $created = $log['created_at']     ?? date('Y-m-d H:i:s');
      ?>
        <tr>
          <td style="color:#aaa;font-size:12px"><?= date('d/m/Y H:i', strtotime($created)) ?></td>
          <td style="font-weight:600"><?= htmlspecialchars($log['product'] ?? '—') ?></td>
          <td style="text-align:center"><?= $before ?></td>
          <td style="text-align:center;font-weight:700;color:<?= $change >= 0 ? '#10b981':'#ef4444' ?>">
            <?= $change >= 0 ? '+':'' ?><?= $change ?>
          </td>
          <td style="text-align:center;font-weight:700"><?= $after ?></td>
          <td style="font-size:12px;color:#aaa"><?= htmlspecialchars($log['reason'] ?? '') ?></td>
          <td style="font-size:12px"><?= htmlspecialchars($log['user'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($stockLogs)): ?>
        <tr><td colspan="7" style="text-align:center;padding:30px;color:#aaa">Chưa có lịch sử kho</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>