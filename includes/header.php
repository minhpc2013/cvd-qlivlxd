<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? 'VLXD Manager' ?> | <?= SITE_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700&family=Oxanium:wght@600;700&display=swap" rel="stylesheet">
  <!-- LOCAL: Bootstrap CSS -->
  <link href="<?= BASE_URL ?>/assets/css/bootstrap.min.css" rel="stylesheet">
  <!-- LOCAL: Bootstrap Icons CSS -->
  <link href="<?= BASE_URL ?>/assets/css/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <span class="brand-icon">🧱</span>
    <span class="brand-name">VLXD<span>Pro</span></span>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section">TỔNG QUAN</div>
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="nav-item <?= ($activePage??'')==='dashboard'?'active':'' ?>">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section">BÁN HÀNG</div>
    <a href="<?= BASE_URL ?>/pages/pos.php" class="nav-item <?= ($activePage??'')==='pos'?'active':'' ?>">
      <i class="bi bi-cart3"></i> Bán hàng (POS)
    </a>
    <a href="<?= BASE_URL ?>/pages/orders.php" class="nav-item <?= ($activePage??'')==='orders'?'active':'' ?>">
      <i class="bi bi-receipt"></i> Đơn hàng
    </a>

    <div class="nav-section">KHO & HÀNG HOÁ</div>
    <a href="<?= BASE_URL ?>/pages/products.php" class="nav-item <?= ($activePage??'')==='products'?'active':'' ?>">
      <i class="bi bi-boxes"></i> Sản phẩm
    </a>
    <a href="<?= BASE_URL ?>/pages/stock.php" class="nav-item <?= ($activePage??'')==='stock'?'active':'' ?>">
      <i class="bi bi-arrow-left-right"></i> Nhập/Xuất kho
    </a>

    <div class="nav-section">ĐỐI TÁC</div>
    <a href="<?= BASE_URL ?>/pages/customers.php" class="nav-item <?= ($activePage??'')==='customers'?'active':'' ?>">
      <i class="bi bi-people"></i> Khách hàng
    </a>
    <a href="<?= BASE_URL ?>/pages/suppliers.php" class="nav-item <?= ($activePage??'')==='suppliers'?'active':'' ?>">
      <i class="bi bi-truck"></i> Nhà cung cấp
    </a>

    <div class="nav-section">TÀI CHÍNH</div>
    <a href="<?= BASE_URL ?>/pages/debts.php" class="nav-item <?= ($activePage??'')==='debts'?'active':'' ?>">
      <i class="bi bi-journal-text"></i> Công nợ
    </a>
    <a href="<?= BASE_URL ?>/pages/cashflow.php" class="nav-item <?= ($activePage??'')==='cashflow'?'active':'' ?>">
      <i class="bi bi-cash-stack"></i> Thu / Chi
    </a>

    <div class="nav-section">BÁO CÁO</div>
    <a href="<?= BASE_URL ?>/pages/reports.php" class="nav-item <?= ($activePage??'')==='reports'?'active':'' ?>">
      <i class="bi bi-bar-chart-line"></i> Báo cáo
    </a>
    <a href="<?= BASE_URL ?>/pages/activity.php" class="nav-item <?= ($activePage??'')==='activity'?'active':'' ?>">
      <i class="bi bi-clock-history"></i> Nhật ký
    </a>
    <a href="<?= BASE_URL ?>/pages/settings.php" class="nav-item <?= ($activePage??'')==='settings'?'active':'' ?>">
      <i class="bi bi-gear"></i> Cài đặt
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name']??'A', 0, 1)) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></div>
        <div class="user-role"><?= ucfirst($_SESSION['user_role'] ?? '') ?></div>
      </div>
    </div>
    <a href="<?= BASE_URL ?>/logout.php" class="btn-logout" title="Đăng xuất">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </div>
</div>

<!-- OVERLAY (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- MAIN CONTENT -->
<div class="main-content" id="mainContent">
  <header class="topbar">
    <button class="btn-toggle-sidebar" onclick="toggleSidebar()">
      <i class="bi bi-list"></i>
    </button>
    <h1 class="page-title"><?= $pageTitle ?? '' ?></h1>
    <div class="topbar-right">
      <?php $lowStock = getLowStockProducts(); ?>
      <?php if (count($lowStock) > 0): ?>
      <a href="<?= BASE_URL ?>/pages/stock.php" class="alert-badge" title="Hàng sắp hết kho">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span><?= count($lowStock) ?></span>
      </a>
      <?php endif; ?>
      <span class="topbar-date"><?= date('d/m/Y H:i') ?></span>
    </div>
  </header>
  <div class="content-area">