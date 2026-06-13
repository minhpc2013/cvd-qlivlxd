<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
seedDemoData();

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
$stats      = getDashboardStats();
$topProducts = getTopProducts(5);
$lowStock   = getLowStockProducts(10);
$recentOrders = array_slice(array_reverse(getAllOrders()), 0, 6);
$revenueData  = getRevenueByMonth((int)date('Y'));

include __DIR__ . '/../includes/header.php';
?>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon orange">📦</div>
      <div>
        <div class="stat-label">Sản phẩm</div>
        <div class="stat-value"><?= $stats['total_products'] ?></div>
        <div class="stat-sub"><?= $stats['low_stock_count'] ?> sắp hết kho</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon blue">📋</div>
      <div>
        <div class="stat-label">Đơn hôm nay</div>
        <div class="stat-value"><?= $stats['today_orders'] ?></div>
        <div class="stat-sub"><?= vnd($stats['today_revenue']) ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon green">💰</div>
      <div>
        <div class="stat-label">Doanh thu tháng</div>
        <div class="stat-value" style="font-size:17px"><?= vnd($stats['month_revenue']) ?></div>
        <div class="stat-sub">Tháng <?= date('m/Y') ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon red">📒</div>
      <div>
        <div class="stat-label">Công nợ phải thu</div>
        <div class="stat-value" style="font-size:17px;color:#ef4444"><?= vnd($stats['total_debt_recv']) ?></div>
        <div class="stat-sub">Phải trả: <?= vnd($stats['total_debt_pay']) ?></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- CHART -->
  <div class="col-md-7">
    <div class="card">
      <div class="card-header"><i class="bi bi-bar-chart-line" style="color:#E85D04"></i> Doanh thu <?= date('Y') ?></div>
      <div style="padding:16px">
        <canvas id="revenueChart" height="100"></canvas>
      </div>
    </div>
  </div>

  <!-- TOP PRODUCTS -->
  <div class="col-md-5">
    <div class="card">
      <div class="card-header"><i class="bi bi-trophy" style="color:#E85D04"></i> Sản phẩm bán chạy</div>
      <div style="padding:16px">
        <?php foreach ($topProducts as $i => $p): ?>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
          <span style="width:24px;height:24px;border-radius:50%;background:<?= ['#E85D04','#F59E0B','#3B82F6','#10B981','#8B5CF6'][$i] ?? '#aaa' ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0"><?= $i+1 ?></span>
          <div style="flex:1">
            <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($p['name'] ?? '—') ?></div>
            <div style="font-size:11px;color:#aaa">Đã bán: <?= intval($p['qty'] ?? 0) ?></div>
          </div>
          <div style="font-weight:700;color:#E85D04;font-size:13px"><?= vnd(floatval($p['revenue'] ?? 0)) ?></div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($topProducts)): ?>
          <div style="text-align:center;padding:20px;color:#aaa">Chưa có dữ liệu</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- RECENT ORDERS -->
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        <i class="bi bi-receipt" style="color:#E85D04"></i> Đơn hàng gần đây
        <a href="<?= BASE_URL ?>/pages/orders.php" style="margin-left:auto;font-size:12px;color:#E85D04;text-decoration:none">Xem tất cả →</a>
      </div>
      <div class="table-wrapper">
        <table class="table">
          <thead><tr><th>Mã ĐH</th><th>Khách hàng</th><th>Tổng tiền</th><th>Trạng thái</th><th>Ngày</th></tr></thead>
          <tbody>
          <?php foreach ($recentOrders as $o):
            $oid     = $o['id']            ?? $o['order_id']    ?? '—';
            $cname   = $o['customer_name'] ?? $o['customer']    ?? 'Khách lẻ';
            $total   = floatval($o['total']  ?? 0);
            $status  = $o['status']        ?? 'pending';
            $created = $o['created_at']    ?? date('Y-m-d H:i:s');
          ?>
          <tr>
            <td><a href="<?= BASE_URL ?>/pages/orders.php?id=<?= htmlspecialchars($oid) ?>" style="color:#E85D04;font-weight:600">#<?= htmlspecialchars($oid) ?></a></td>
            <td><?= htmlspecialchars($cname) ?></td>
            <td style="font-weight:700"><?= vnd($total) ?></td>
            <td><?= statusBadge($status) ?></td>
            <td style="color:#aaa;font-size:12px"><?= date('d/m H:i', strtotime($created)) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($recentOrders)): ?>
            <tr><td colspan="5" style="text-align:center;padding:20px;color:#aaa">Chưa có đơn hàng</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- LOW STOCK -->
  <div class="col-md-4">
    <div class="card">
      <div class="card-header"><i class="bi bi-exclamation-triangle" style="color:#F59E0B"></i> Cảnh báo tồn kho</div>
      <div style="padding:12px">
        <?php foreach (array_slice($lowStock, 0, 6) as $p): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px;border-radius:8px;margin-bottom:4px;background:#FFF7F0">
          <div>
            <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($p['name'] ?? '—') ?></div>
            <div style="font-size:11px;color:#aaa"><?= htmlspecialchars($p['category'] ?? '') ?></div>
          </div>
          <?php $stk = intval($p['stock'] ?? 0); ?>
          <span class="badge badge-<?= $stk <= 3 ? 'danger' : 'warning' ?>"><?= $stk ?> <?= htmlspecialchars($p['unit'] ?? '') ?></span>
        </div>
        <?php endforeach; ?>
        <?php if (empty($lowStock)): ?>
          <div style="text-align:center;padding:20px;color:#10b981"><i class="bi bi-check-circle" style="font-size:30px;display:block"></i>Tồn kho ổn định</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php
$extraJs = '<script>
const ctx = document.getElementById("revenueChart");
if (ctx) {
  new Chart(ctx, {
    type: "bar",
    data: {
      labels: ["T1","T2","T3","T4","T5","T6","T7","T8","T9","T10","T11","T12"],
      datasets: [{
        label: "Doanh thu (đ)",
        data: ' . json_encode(array_values($revenueData)) . ',
        backgroundColor: "rgba(232,93,4,.85)",
        borderRadius: 6,
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: {
        y: { ticks: { callback: v => new Intl.NumberFormat("vi-VN",{notation:"compact"}).format(v) + "đ" } }
      }
    }
  });
}
</script>';
include __DIR__ . '/../includes/footer.php';
?>