<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$pageTitle  = 'Báo cáo & Thống kê';
$activePage = 'reports';

$year  = intval($_GET['year']  ?? date('Y'));
$month = intval($_GET['month'] ?? date('m'));

$orders    = getAllOrders();
$products  = getAllProducts();
$customers = getAllCustomers();

// Doanh thu theo tháng trong năm
$revenueByMonth = getRevenueByMonth($year);

// Lọc đơn hàng tháng được chọn
$monthOrders = array_filter($orders, function($o) use ($year, $month) {
    return (int)date('Y', strtotime($o['created_at'])) === $year &&
           (int)date('m', strtotime($o['created_at'])) === $month;
});

$monthRevenue = array_sum(array_column(array_values($monthOrders), 'paid'));
$monthOrderCount = count($monthOrders);

// Top sản phẩm tháng
$monthSales = [];
foreach ($monthOrders as $o) {
    foreach (($o['items'] ?? []) as $item) {
        $pid = $item['product_id'] ?? ($item['id'] ?? '');
        if (!$pid) continue;
        if (!isset($monthSales[$pid])) $monthSales[$pid] = ['name'=>($item['name'] ?? '?'),'qty'=>0,'revenue'=>0];
        $qty   = intval($item['qty']   ?? 0);
        $price = floatval($item['price'] ?? 0);
        $monthSales[$pid]['qty']     += $qty;
        $monthSales[$pid]['revenue'] += $qty * $price;
    }
}
uasort($monthSales, fn($a,$b) => $b['revenue'] <=> $a['revenue']);
$topMonthProducts = array_slice(array_values($monthSales), 0, 8);

// Doanh thu theo ngày trong tháng
$revenueByDay = [];
$daysInMonth  = cal_days_in_month(CAL_GREGORIAN, $month, $year);
for ($d = 1; $d <= $daysInMonth; $d++) $revenueByDay[$d] = 0;
foreach ($monthOrders as $o) {
    $d = (int)date('j', strtotime($o['created_at'] ?? 'now'));
    $revenueByDay[$d] += floatval($o['paid'] ?? $o['total'] ?? 0);
}

// Top khách hàng tháng
$monthCustomers = [];
foreach ($monthOrders as $o) {
    $k = $o['customer_name'] ?? ($o['customer'] ?? 'Khách lẻ');
    if (!isset($monthCustomers[$k])) $monthCustomers[$k] = ['orders'=>0,'total'=>0];
    $monthCustomers[$k]['orders']++;
    $monthCustomers[$k]['total'] += floatval($o['total'] ?? 0);
}
uasort($monthCustomers, fn($a,$b) => $b['total'] <=> $a['total']);

// Doanh thu theo danh mục
$catRevenue = [];
foreach ($monthOrders as $o) {
    foreach (($o['items'] ?? []) as $item) {
        $pid = $item['product_id'] ?? '';
        $p   = $pid ? getProductById($pid) : null;
        $cat = $p['category'] ?? 'Khác';
        $qty   = intval($item['qty']   ?? 0);
        $price = floatval($item['price'] ?? 0);
        $catRevenue[$cat] = ($catRevenue[$cat] ?? 0) + $qty * $price;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<!-- FILTER -->
<div class="card mb-3">
  <div style="padding:14px 20px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <form method="GET" style="display:flex;gap:10px;align-items:center">
      <label style="font-weight:600;margin:0">📅 Năm:</label>
      <select name="year" class="form-select" style="max-width:100px" onchange="this.form.submit()">
        <?php for ($y = date('Y'); $y >= date('Y')-3; $y--): ?>
          <option <?= $y===$year?'selected':'' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
      <label style="font-weight:600;margin:0">Tháng:</label>
      <select name="month" class="form-select" style="max-width:130px" onchange="this.form.submit()">
        <?php for ($m = 1; $m <= 12; $m++): ?>
          <option value="<?= $m ?>" <?= $m===$month?'selected':'' ?>>Tháng <?= $m ?></option>
        <?php endfor; ?>
      </select>
    </form>
    <div style="margin-left:auto;font-size:13px;color:#aaa">
      Báo cáo tháng <?= $month ?>/<?= $year ?>
    </div>
  </div>
</div>

<!-- SUMMARY THÁNG -->
<div class="row g-3 mb-3">
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon green">💰</div>
      <div>
        <div class="stat-label">Doanh thu tháng</div>
        <div class="stat-value" style="font-size:16px"><?= vnd($monthRevenue) ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon blue">📋</div>
      <div>
        <div class="stat-label">Số đơn hàng</div>
        <div class="stat-value"><?= $monthOrderCount ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon orange">🛒</div>
      <div>
        <div class="stat-label">Trung bình/đơn</div>
        <div class="stat-value" style="font-size:16px"><?= $monthOrderCount ? vnd($monthRevenue / $monthOrderCount) : '0 đ' ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon purple">📦</div>
      <div>
        <div class="stat-label">Sản phẩm bán</div>
        <div class="stat-value"><?= array_sum(array_column($topMonthProducts,'qty')) ?></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <!-- BIỂU ĐỒ DOANH THU THEO THÁNG -->
  <div class="col-md-8">
    <div class="card">
      <div class="card-header"><i class="bi bi-bar-chart" style="color:#E85D04"></i> Doanh thu theo tháng — Năm <?= $year ?></div>
      <div style="padding:16px"><canvas id="monthlyChart" height="100"></canvas></div>
    </div>
  </div>

  <!-- DOANH THU THEO DANH MỤC -->
  <div class="col-md-4">
    <div class="card">
      <div class="card-header"><i class="bi bi-pie-chart" style="color:#E85D04"></i> Danh mục tháng <?= $month ?></div>
      <div style="padding:16px">
        <canvas id="catChart" height="180"></canvas>
        <?php if (empty($catRevenue)): ?>
          <div style="text-align:center;color:#aaa;padding:20px">Chưa có dữ liệu</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <!-- BIỂU ĐỒ THEO NGÀY -->
  <div class="col-md-7">
    <div class="card">
      <div class="card-header"><i class="bi bi-graph-up" style="color:#E85D04"></i> Doanh thu theo ngày — T<?= $month ?>/<?= $year ?></div>
      <div style="padding:16px"><canvas id="dailyChart" height="100"></canvas></div>
    </div>
  </div>

  <!-- TOP KHÁCH HÀNG -->
  <div class="col-md-5">
    <div class="card">
      <div class="card-header"><i class="bi bi-people" style="color:#E85D04"></i> Top khách hàng</div>
      <div style="padding:12px">
        <?php $rank=1; foreach (array_slice($monthCustomers, 0, 6, true) as $name => $info): ?>
        <div style="display:flex;align-items:center;gap:10px;padding:8px;border-radius:8px;margin-bottom:4px;background:var(--surface-2)">
          <span style="width:22px;height:22px;border-radius:50%;background:<?= ['#E85D04','#F59E0B','#3B82F6','#10B981','#8B5CF6','#6B7280'][$rank-1]??'#aaa' ?>;color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0"><?= $rank++ ?></span>
          <div style="flex:1">
            <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($name) ?></div>
            <div style="font-size:11px;color:#aaa"><?= $info['orders'] ?> đơn</div>
          </div>
          <div style="font-weight:700;color:#E85D04;font-size:13px"><?= vnd($info['total']) ?></div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($monthCustomers)): ?>
          <div style="text-align:center;padding:20px;color:#aaa">Chưa có dữ liệu</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- TOP SẢN PHẨM THÁNG -->
<div class="card">
  <div class="card-header"><i class="bi bi-trophy" style="color:#E85D04"></i> Top sản phẩm bán chạy — Tháng <?= $month ?>/<?= $year ?></div>
  <div class="table-wrapper">
    <table class="table table-hover">
      <thead><tr><th>#</th><th>Sản phẩm</th><th>Số lượng bán</th><th>Doanh thu</th><th>Tỷ trọng</th></tr></thead>
      <tbody>
      <?php foreach ($topMonthProducts as $i => $p):
        $pct = $monthRevenue > 0 ? round($p['revenue']/$monthRevenue*100, 1) : 0;
      ?>
        <tr>
          <td style="color:#aaa"><?= $i+1 ?></td>
          <td style="font-weight:600"><?= htmlspecialchars($p['name']) ?></td>
          <td style="text-align:center"><?= number_format($p['qty']) ?></td>
          <td style="font-weight:700;color:#E85D04"><?= vnd($p['revenue']) ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <div style="flex:1;background:#F3F4F6;border-radius:4px;height:8px;overflow:hidden">
                <div style="width:<?= $pct ?>%;height:100%;background:#E85D04;border-radius:4px"></div>
              </div>
              <span style="font-size:12px;color:#aaa;min-width:36px"><?= $pct ?>%</span>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($topMonthProducts)): ?>
        <tr><td colspan="5" style="text-align:center;padding:30px;color:#aaa">Không có dữ liệu</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$monthlyData = json_encode(array_values($revenueByMonth));
$dailyData   = json_encode(array_values($revenueByDay));
$dailyLabels = json_encode(array_keys($revenueByDay));
$catLabels   = json_encode(array_keys($catRevenue));
$catData     = json_encode(array_values($catRevenue));

$extraJs = "<script>
const COLORS = ['#E85D04','#F59E0B','#3B82F6','#10B981','#8B5CF6','#EF4444','#14B8A6','#F97316'];
const fmtVND = v => new Intl.NumberFormat('vi-VN',{notation:'compact'}).format(v) + 'đ';

new Chart(document.getElementById('monthlyChart'), {
  type: 'bar',
  data: {
    labels: ['T1','T2','T3','T4','T5','T6','T7','T8','T9','T10','T11','T12'],
    datasets: [{ label: 'Doanh thu', data: $monthlyData, backgroundColor: 'rgba(232,93,4,.85)', borderRadius: 6 }]
  },
  options: { plugins:{legend:{display:false}}, scales:{y:{ticks:{callback:fmtVND}}} }
});

new Chart(document.getElementById('dailyChart'), {
  type: 'line',
  data: {
    labels: $dailyLabels,
    datasets: [{ label: 'Doanh thu', data: $dailyData, borderColor:'#E85D04', backgroundColor:'rgba(232,93,4,.1)', fill:true, tension:.4, pointRadius:3 }]
  },
  options: { plugins:{legend:{display:false}}, scales:{y:{ticks:{callback:fmtVND}}} }
});

const catLabels = $catLabels;
if (catLabels.length > 0) {
  new Chart(document.getElementById('catChart'), {
    type: 'doughnut',
    data: { labels: catLabels, datasets:[{ data: $catData, backgroundColor: COLORS, borderWidth:2 }] },
    options: { plugins:{ legend:{ position:'bottom', labels:{ font:{size:11}, padding:8 } } } }
  });
}
</script>";
include __DIR__ . '/../includes/footer.php';
?>
