<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect nếu đã đăng nhập
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $users    = readJSON(USERS_FILE);

    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            logActivity('LOGIN', "Đăng nhập thành công");
            header('Location: ' . BASE_URL . '/pages/dashboard.php');
            exit;
        }
    }
    $error = 'Tên đăng nhập hoặc mật khẩu không đúng!';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng nhập | VLXD Pro</title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;600;700&family=Oxanium:wght@700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/style.css" rel="stylesheet">
  <link href="<?php echo BASE_URL; ?>assets/style.css" rel="stylesheet">
</head>
<body>
<div class="login-page">
  <div class="login-box">
    <div class="login-logo">
      <div style="font-size:48px;margin-bottom:4px">🧱</div>
      <div class="brand-name" style="font-family:'Oxanium',sans-serif;font-size:28px;font-weight:700;color:#1A1A2E">
        VLXD<span style="color:#E85D04">Pro</span>
      </div>
      <div style="color:#7A8599;font-size:13px;margin-top:4px">Hệ thống quản lý cửa hàng vật liệu xây dựng</div>
    </div>

    <?php if ($error): ?>
      <div class="flash-msg flash-error"><i class="bi bi-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Tên đăng nhập</label>
        <div style="position:relative">
          <i class="bi bi-person" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#aaa"></i>
          <input type="text" name="username" class="form-control" style="padding-left:36px"
                 placeholder="admin" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label">Mật khẩu</label>
        <div style="position:relative">
          <i class="bi bi-lock" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#aaa"></i>
          <input type="password" name="password" class="form-control" style="padding-left:36px"
                 placeholder="••••••••" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary w-100" style="padding:11px">
        <i class="bi bi-box-arrow-in-right me-2"></i>Đăng nhập
      </button>
    </form>

    <div style="text-align:center;margin-top:20px;font-size:12px;color:#aaa">
      Tài khoản demo: <strong>admin</strong> / <strong>admin123</strong>
    </div>
  </div>
</div>
</body>
</html>
