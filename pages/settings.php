<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$pageTitle  = 'Cài đặt hệ thống';
$activePage = 'settings';

// Load settings
$settingsFile = __DIR__ . '/../data/settings.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
$settings = array_merge([
    'store_name'     => 'Cửa hàng Vật liệu Xây dựng',
    'store_address'  => '',
    'store_phone'    => '',
    'store_email'    => '',
    'tax_code'       => '',
    'invoice_footer' => 'Cảm ơn quý khách đã mua hàng!',
    'currency'       => 'VND',
    'timezone'       => 'Asia/Ho_Chi_Minh',
    'low_stock_threshold' => 10,
], $settings);

// Load users
$usersFile = __DIR__ . '/../data/users.json';
$users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];

// Load activity logs
$logsFile = __DIR__ . '/../data/activity_logs.json';
$allLogs  = file_exists($logsFile) ? json_decode(file_get_contents($logsFile), true) : [];
$logs     = array_slice(array_reverse($allLogs), 0, 50);

$currentUser = [
    'id'   => $_SESSION['user_id']   ?? null,
    'name' => $_SESSION['user_name'] ?? 'Admin',
    'role' => $_SESSION['user_role'] ?? 'staff',
];

$isAdmin = ($currentUser['role'] === 'admin');

include __DIR__ . '/../includes/header.php';
?>

<style>
.settings-nav { display:flex; gap:8px; margin-bottom:24px; flex-wrap:wrap; }
.settings-nav .snav-btn {
  display:flex; align-items:center; gap:8px;
  padding:9px 18px; border-radius:10px; border:1.5px solid #e5e7eb;
  background:#fff; cursor:pointer; font-size:13px; font-weight:600;
  color:#6b7280; transition:all .18s; text-decoration:none;
}
.settings-nav .snav-btn:hover { border-color:#E85D04; color:#E85D04; background:#fff7f0; }
.settings-nav .snav-btn.active { background:#E85D04; border-color:#E85D04; color:#fff; }
.settings-nav .snav-btn i { font-size:16px; }

.tab-pane { display:none; }
.tab-pane.active { display:block; }

.form-section { margin-bottom:28px; }
.form-section-title {
  font-size:13px; font-weight:700; color:#E85D04;
  text-transform:uppercase; letter-spacing:.5px;
  margin-bottom:14px; padding-bottom:8px;
  border-bottom:1.5px solid #FFF0E6;
}

.form-label { font-size:13px; font-weight:600; color:#374151; margin-bottom:5px; }
.form-control, .form-select {
  border-radius:8px; border:1.5px solid #e5e7eb;
  font-size:13px; padding:9px 12px; transition:border .15s;
}
.form-control:focus, .form-select:focus {
  border-color:#E85D04; box-shadow:0 0 0 3px rgba(232,93,4,.1);
}

.user-row {
  display:flex; align-items:center; gap:12px;
  padding:12px 14px; border-radius:10px;
  border:1.5px solid #f3f4f6; margin-bottom:8px;
  background:#fff; transition:border .15s;
}
.user-row:hover { border-color:#f0d0b8; }
.user-avatar {
  width:38px; height:38px; border-radius:50%;
  display:flex; align-items:center; justify-content:center;
  font-size:13px; font-weight:700; flex-shrink:0;
}
.avatar-admin { background:#FFF0E6; color:#E85D04; }
.avatar-staff { background:#EFF6FF; color:#3B82F6; }
.role-badge {
  font-size:11px; font-weight:700; padding:3px 9px;
  border-radius:20px; flex-shrink:0;
}
.role-admin { background:#FFF0E6; color:#E85D04; }
.role-staff { background:#EFF6FF; color:#3B82F6; }

.log-row {
  display:flex; align-items:flex-start; gap:10px;
  padding:9px 0; border-bottom:1px solid #f3f4f6; font-size:12.5px;
}
.log-row:last-child { border-bottom:none; }
.log-action {
  font-size:10.5px; font-weight:700; padding:2px 8px;
  border-radius:20px; white-space:nowrap; flex-shrink:0;
}
.log-login    { background:#ECFDF5; color:#059669; }
.log-logout   { background:#F3F4F6; color:#6B7280; }
.log-add      { background:#EFF6FF; color:#3B82F6; }
.log-update   { background:#FFF7ED; color:#D97706; }
.log-delete   { background:#FEF2F2; color:#DC2626; }
.log-other    { background:#F5F3FF; color:#7C3AED; }

.btn-save {
  background:#E85D04; border:none; color:#fff;
  padding:10px 28px; border-radius:8px;
  font-size:13px; font-weight:700; cursor:pointer;
  transition:background .15s; display:inline-flex; align-items:center; gap:6px;
}
.btn-save:hover { background:#c44e03; }
.btn-danger-outline {
  background:#fff; border:1.5px solid #fca5a5; color:#dc2626;
  padding:7px 14px; border-radius:8px;
  font-size:12px; font-weight:600; cursor:pointer; transition:all .15s;
}
.btn-danger-outline:hover { background:#fef2f2; }
.btn-outline-sm {
  background:#fff; border:1.5px solid #e5e7eb; color:#374151;
  padding:6px 14px; border-radius:8px;
  font-size:12px; font-weight:600; cursor:pointer; transition:all .15s;
}
.btn-outline-sm:hover { border-color:#E85D04; color:#E85D04; }

.alert-success-inline {
  display:none; background:#ECFDF5; border:1px solid #6ee7b7;
  color:#047857; border-radius:8px; padding:10px 14px;
  font-size:13px; font-weight:600; margin-bottom:16px;
  align-items:center; gap:8px;
}
.alert-success-inline.show { display:flex; }
.alert-error-inline {
  display:none; background:#FEF2F2; border:1px solid #fca5a5;
  color:#dc2626; border-radius:8px; padding:10px 14px;
  font-size:13px; font-weight:600; margin-bottom:16px;
  align-items:center; gap:8px;
}
.alert-error-inline.show { display:flex; }

/* Modal */
.modal-overlay {
  display:none; position:fixed; inset:0;
  background:rgba(0,0,0,.45); z-index:9999;
  align-items:center; justify-content:center;
}
.modal-overlay.show { display:flex; }
.modal-box {
  background:#fff; border-radius:16px;
  padding:28px; width:100%; max-width:440px;
  box-shadow:0 20px 60px rgba(0,0,0,.15);
}
.modal-title { font-size:16px; font-weight:700; margin-bottom:20px; }
</style>

<!-- Page Header -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <div>
    <h4 style="margin:0;font-weight:700">⚙️ Cài đặt hệ thống</h4>
    <div style="font-size:13px;color:#aaa;margin-top:2px"><?= $isAdmin ? 'Quản lý tài khoản, thông tin cửa hàng và hệ thống' : 'Đổi mật khẩu tài khoản của bạn' ?></div>
  </div>
</div>

<!-- Tab Navigation -->
<div class="settings-nav">
  <?php if ($isAdmin): ?>
  <a class="snav-btn active" onclick="switchTab('store')" id="tab-store">
    <i class="bi bi-shop"></i> Thông tin cửa hàng
  </a>
  <a class="snav-btn" onclick="switchTab('accounts')" id="tab-accounts">
    <i class="bi bi-people"></i> Tài khoản
  </a>
  <?php endif; ?>
  <a class="snav-btn <?= $isAdmin ? '' : 'active' ?>" onclick="switchTab('password')" id="tab-password">
    <i class="bi bi-shield-lock"></i> Đổi mật khẩu
  </a>
  <?php if ($isAdmin): ?>
  <a class="snav-btn" onclick="switchTab('logs')" id="tab-logs">
    <i class="bi bi-journal-text"></i> Nhật ký hoạt động
  </a>
  <?php endif; ?>
</div>

<!-- ========== TAB 1: THÔNG TIN CỬA HÀNG ========== -->
<?php if ($isAdmin): ?>
<div class="tab-pane active" id="pane-store">
  <div class="card">
    <div class="card-header"><i class="bi bi-shop" style="color:#E85D04"></i> Thông tin cửa hàng</div>
    <div style="padding:24px">
      <div id="alert-store-ok" class="alert-success-inline"><i class="bi bi-check-circle-fill"></i> Lưu thành công!</div>
      <div id="alert-store-err" class="alert-error-inline"><i class="bi bi-x-circle-fill"></i> <span id="alert-store-msg">Lỗi lưu dữ liệu</span></div>

      <div class="row g-3">
        <div class="col-md-6">
          <div class="form-section-title">Thông tin cơ bản</div>
          <div class="mb-3">
            <label class="form-label">Tên cửa hàng <span style="color:red">*</span></label>
            <input type="text" class="form-control" id="store_name" value="<?= htmlspecialchars($settings['store_name']) ?>" placeholder="VD: Cửa hàng VLXD Minh Phát">
          </div>
          <div class="mb-3">
            <label class="form-label">Địa chỉ</label>
            <input type="text" class="form-control" id="store_address" value="<?= htmlspecialchars($settings['store_address']) ?>" placeholder="Số nhà, đường, phường/xã, quận/huyện">
          </div>
          <div class="mb-3">
            <label class="form-label">Số điện thoại</label>
            <input type="text" class="form-control" id="store_phone" value="<?= htmlspecialchars($settings['store_phone']) ?>" placeholder="0xxxxxxxxx">
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" id="store_email" value="<?= htmlspecialchars($settings['store_email']) ?>" placeholder="email@cuahang.com">
          </div>
          <div class="mb-3">
            <label class="form-label">Mã số thuế</label>
            <input type="text" class="form-control" id="tax_code" value="<?= htmlspecialchars($settings['tax_code']) ?>" placeholder="0000000000">
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-section-title">Cấu hình hiển thị</div>
          <div class="mb-3">
            <label class="form-label">Đơn vị tiền tệ</label>
            <select class="form-select" id="currency">
              <option value="VND" <?= $settings['currency']==='VND'?'selected':'' ?>>VND — Việt Nam Đồng</option>
              <option value="USD" <?= $settings['currency']==='USD'?'selected':'' ?>>USD — Đô la Mỹ</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Múi giờ</label>
            <select class="form-select" id="timezone">
              <option value="Asia/Ho_Chi_Minh" <?= $settings['timezone']==='Asia/Ho_Chi_Minh'?'selected':'' ?>>Asia/Ho_Chi_Minh (GMT+7)</option>
              <option value="Asia/Bangkok"      <?= $settings['timezone']==='Asia/Bangkok'?'selected':'' ?>>Asia/Bangkok (GMT+7)</option>
              <option value="UTC"               <?= $settings['timezone']==='UTC'?'selected':'' ?>>UTC (GMT+0)</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Ngưỡng cảnh báo tồn kho thấp</label>
            <div style="display:flex;align-items:center;gap:8px">
              <input type="number" class="form-control" id="low_stock_threshold" value="<?= intval($settings['low_stock_threshold']) ?>" min="1" max="999" style="width:110px">
              <span style="font-size:13px;color:#6b7280">sản phẩm trở xuống</span>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Chú thích cuối hóa đơn</label>
            <textarea class="form-control" id="invoice_footer" rows="3" placeholder="VD: Cảm ơn quý khách đã mua hàng!"><?= htmlspecialchars($settings['invoice_footer']) ?></textarea>
          </div>
        </div>
      </div>

      <div style="margin-top:8px;padding-top:16px;border-top:1px solid #f3f4f6">
        <button class="btn-save" onclick="saveStore()">
          <i class="bi bi-check-lg"></i> Lưu thay đổi
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ========== TAB 2: TÀI KHOẢN ========== -->
<div class="tab-pane" id="pane-accounts">
  <div class="row g-3">
    <div class="col-md-7">
      <div class="card">
        <div class="card-header" style="display:flex;align-items:center">
          <span><i class="bi bi-people" style="color:#E85D04"></i> Danh sách tài khoản</span>
          <button class="btn-save" style="margin-left:auto;padding:7px 16px;font-size:12px" onclick="openAddUser()">
            <i class="bi bi-plus-lg"></i> Thêm tài khoản
          </button>
        </div>
        <div style="padding:16px" id="user-list">
          <?php foreach ($users as $u): ?>
          <div class="user-row" id="urow-<?= htmlspecialchars($u['id']) ?>">
            <div class="user-avatar <?= $u['role']==='admin'?'avatar-admin':'avatar-staff' ?>">
              <?= mb_strtoupper(mb_substr($u['name'] ?? $u['username'], 0, 1)) ?>
            </div>
            <div style="flex:1;min-width:0">
              <div style="font-weight:700;font-size:13px"><?= htmlspecialchars($u['name'] ?? $u['username']) ?></div>
              <div style="font-size:11px;color:#aaa">@<?= htmlspecialchars($u['username']) ?></div>
            </div>
            <span class="role-badge <?= $u['role']==='admin'?'role-admin':'role-staff' ?>">
              <?= $u['role']==='admin'?'Admin':'Nhân viên' ?>
            </span>
            <div style="display:flex;gap:6px;margin-left:4px">
              <?php if ($u['id'] !== ($currentUser['id'] ?? 'admin001')): ?>
              <button class="btn-danger-outline" style="padding:5px 10px" onclick="deleteUser('<?= $u['id'] ?>','<?= htmlspecialchars($u['name']??$u['username']) ?>')">
                <i class="bi bi-trash3"></i>
              </button>
              <?php else: ?>
              <span style="font-size:11px;color:#aaa;padding:5px 10px">(Bạn)</span>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if (empty($users)): ?>
            <div style="text-align:center;padding:20px;color:#aaa">Không có tài khoản nào</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-md-5">
      <div class="card">
        <div class="card-header"><i class="bi bi-info-circle" style="color:#3B82F6"></i> Phân quyền</div>
        <div style="padding:16px">
          <div style="margin-bottom:16px">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
              <span class="role-badge role-admin">Admin</span>
              <span style="font-size:13px;font-weight:600">Quản trị viên</span>
            </div>
            <ul style="font-size:12.5px;color:#6b7280;padding-left:18px;margin:0;line-height:1.9">
              <li>Toàn quyền trên hệ thống</li>
              <li>Xem báo cáo doanh thu &amp; tài chính</li>
              <li>Quản lý tài khoản người dùng</li>
              <li>Thay đổi cài đặt hệ thống</li>
              <li>Xóa dữ liệu &amp; sao lưu</li>
            </ul>
          </div>
          <div style="padding-top:14px;border-top:1px solid #f3f4f6">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
              <span class="role-badge role-staff">Nhân viên</span>
              <span style="font-size:13px;font-weight:600">Staff</span>
            </div>
            <ul style="font-size:12.5px;color:#6b7280;padding-left:18px;margin:0;line-height:1.9">
              <li>Tạo &amp; xử lý đơn hàng</li>
              <li>Xem &amp; cập nhật tồn kho</li>
              <li>Quản lý khách hàng &amp; nhà cung cấp</li>
              <li><s>Không xem doanh thu tổng</s></li>
              <li><s>Không vào trang Cài đặt</s></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ========== TAB 3: ĐỔI MẬT KHẨU ========== -->
<div class="tab-pane <?= $isAdmin ? '' : 'active' ?>" id="pane-password">
  <div class="row">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header"><i class="bi bi-shield-lock" style="color:#E85D04"></i> Đổi mật khẩu</div>
        <div style="padding:24px">
          <div id="alert-pw-ok"  class="alert-success-inline"><i class="bi bi-check-circle-fill"></i> Đổi mật khẩu thành công!</div>
          <div id="alert-pw-err" class="alert-error-inline"><i class="bi bi-x-circle-fill"></i> <span id="alert-pw-msg">Lỗi</span></div>

          <div class="mb-3">
            <label class="form-label">Mật khẩu hiện tại <span style="color:red">*</span></label>
            <div style="position:relative">
              <input type="password" class="form-control" id="pw_current" placeholder="Nhập mật khẩu hiện tại" style="padding-right:40px">
              <i class="bi bi-eye-slash" onclick="togglePw('pw_current',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:#aaa;font-size:15px"></i>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Mật khẩu mới <span style="color:red">*</span></label>
            <div style="position:relative">
              <input type="password" class="form-control" id="pw_new" placeholder="Tối thiểu 6 ký tự" style="padding-right:40px" oninput="checkPwStrength(this.value)">
              <i class="bi bi-eye-slash" onclick="togglePw('pw_new',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:#aaa;font-size:15px"></i>
            </div>
            <!-- Strength bar -->
            <div style="margin-top:8px">
              <div style="height:4px;border-radius:2px;background:#f3f4f6;overflow:hidden">
                <div id="pw-strength-bar" style="height:100%;width:0%;border-radius:2px;transition:all .3s;background:#ef4444"></div>
              </div>
              <div id="pw-strength-txt" style="font-size:11px;color:#aaa;margin-top:4px"></div>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label">Xác nhận mật khẩu mới <span style="color:red">*</span></label>
            <div style="position:relative">
              <input type="password" class="form-control" id="pw_confirm" placeholder="Nhập lại mật khẩu mới" style="padding-right:40px">
              <i class="bi bi-eye-slash" onclick="togglePw('pw_confirm',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:#aaa;font-size:15px"></i>
            </div>
          </div>
          <button class="btn-save" onclick="changePassword()">
            <i class="bi bi-shield-check"></i> Cập nhật mật khẩu
          </button>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card" style="background:#FFF7F0;border:1.5px solid #FDDCBC">
        <div class="card-header" style="background:transparent;border-bottom:1px solid #FDDCBC">
          <i class="bi bi-lightbulb" style="color:#E85D04"></i> Lưu ý bảo mật
        </div>
        <div style="padding:16px;font-size:13px;color:#6b7280;line-height:1.85">
          <div style="margin-bottom:10px">🔒 <strong>Mật khẩu mạnh</strong> nên có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt.</div>
          <div style="margin-bottom:10px">🚫 <strong>Không dùng lại</strong> mật khẩu từ các tài khoản khác.</div>
          <div style="margin-bottom:10px">📱 <strong>Không chia sẻ</strong> mật khẩu với người khác, kể cả đồng nghiệp.</div>
          <div>🔄 <strong>Thay đổi định kỳ</strong> mật khẩu mỗi 3–6 tháng một lần.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ========== TAB 4: NHẬT KÝ HOẠT ĐỘNG ========== -->
<?php if ($isAdmin): ?>
<div class="tab-pane" id="pane-logs">
  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
      <span><i class="bi bi-journal-text" style="color:#E85D04"></i> Nhật ký hoạt động</span>
      <div style="margin-left:auto;display:flex;gap:8px;align-items:center">
        <select class="form-select" id="log-filter-action" onchange="filterLogs()" style="width:160px;font-size:12px;padding:6px 10px">
          <option value="">Tất cả hành động</option>
          <option value="LOGIN">Đăng nhập</option>
          <option value="LOGOUT">Đăng xuất</option>
          <option value="ADD_ORDER">Tạo đơn hàng</option>
          <option value="UPDATE_ORDER">Cập nhật đơn</option>
          <option value="ADD_PRODUCT">Thêm sản phẩm</option>
          <option value="ADD_CUSTOMER">Thêm khách hàng</option>
          <option value="ADD_SUPPLIER">Thêm NCC</option>
        </select>
        <input type="text" class="form-control" id="log-search" onkeyup="filterLogs()" placeholder="Tìm kiếm..." style="width:180px;font-size:12px;padding:6px 10px">
      </div>
    </div>
    <div style="padding:16px" id="log-container">
      <?php foreach ($logs as $log):
        $action = $log['action'] ?? '';
        $cls = 'log-other';
        if ($action === 'LOGIN')  $cls = 'log-login';
        elseif ($action === 'LOGOUT') $cls = 'log-logout';
        elseif (str_starts_with($action, 'ADD_')) $cls = 'log-add';
        elseif (str_starts_with($action, 'UPDATE_')) $cls = 'log-update';
        elseif (str_starts_with($action, 'DELETE_')) $cls = 'log-delete';
        $dt = $log['created_at'] ?? '';
        $ts = $dt ? date('d/m/Y H:i:s', strtotime($dt)) : '—';
      ?>
      <div class="log-row" data-action="<?= htmlspecialchars($action) ?>" data-detail="<?= htmlspecialchars(strtolower($log['detail'] ?? '')) ?>">
        <span class="log-action <?= $cls ?>"><?= htmlspecialchars($action) ?></span>
        <div style="flex:1;min-width:0">
          <div style="font-weight:600;color:#374151"><?= htmlspecialchars($log['detail'] ?? '—') ?></div>
          <div style="color:#aaa;font-size:11px;margin-top:2px">
            👤 <?= htmlspecialchars($log['user'] ?? '—') ?> &nbsp;·&nbsp; 🕐 <?= $ts ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($logs)): ?>
        <div style="text-align:center;padding:30px;color:#aaa">Chưa có nhật ký nào</div>
      <?php endif; ?>
    </div>
    <div style="padding:10px 16px;border-top:1px solid #f3f4f6;font-size:12px;color:#aaa">
      Hiển thị <?= min(50, count($allLogs)) ?> / <?= count($allLogs) ?> bản ghi gần nhất
    </div>
  </div>
</div>
<?php endif; ?>


<!-- ========== MODAL THÊM TÀI KHOẢN ========== -->
<?php if ($isAdmin): ?>
<div class="modal-overlay" id="modal-add-user">
  <div class="modal-box">
    <div class="modal-title"><i class="bi bi-person-plus" style="color:#E85D04"></i> Thêm tài khoản mới</div>
    <div id="alert-adduser-err" class="alert-error-inline" style="margin-bottom:16px"><i class="bi bi-x-circle-fill"></i> <span id="alert-adduser-msg"></span></div>
    <div class="mb-3">
      <label class="form-label">Tên hiển thị <span style="color:red">*</span></label>
      <input type="text" class="form-control" id="new_name" placeholder="VD: Nguyễn Văn B">
    </div>
    <div class="mb-3">
      <label class="form-label">Tên đăng nhập <span style="color:red">*</span></label>
      <input type="text" class="form-control" id="new_username" placeholder="Không dấu, không khoảng trắng" oninput="this.value=this.value.replace(/\s/g,'')">
    </div>
    <div class="mb-3">
      <label class="form-label">Mật khẩu <span style="color:red">*</span></label>
      <input type="password" class="form-control" id="new_password" placeholder="Tối thiểu 6 ký tự">
    </div>
    <div class="mb-4">
      <label class="form-label">Phân quyền</label>
      <select class="form-select" id="new_role">
        <option value="staff">Nhân viên (Staff)</option>
        <option value="admin">Quản trị viên (Admin)</option>
      </select>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <button class="btn-outline-sm" onclick="closeModal('modal-add-user')">Hủy</button>
      <button class="btn-save" onclick="addUser()"><i class="bi bi-check-lg"></i> Tạo tài khoản</button>
    </div>
  </div>
</div>

<!-- ========== MODAL XÁC NHẬN XÓA ========== -->
<div class="modal-overlay" id="modal-confirm-delete">
  <div class="modal-box" style="max-width:380px">
    <div style="text-align:center;margin-bottom:20px">
      <div style="font-size:48px;margin-bottom:12px">🗑️</div>
      <div class="modal-title" style="margin:0">Xóa tài khoản?</div>
      <div style="font-size:13px;color:#6b7280;margin-top:8px">
        Tài khoản <strong id="delete-user-name"></strong> sẽ bị xóa vĩnh viễn và không thể khôi phục.
      </div>
    </div>
    <input type="hidden" id="delete-user-id">
    <div style="display:flex;gap:10px;justify-content:center">
      <button class="btn-outline-sm" style="padding:10px 24px" onclick="closeModal('modal-confirm-delete')">Hủy</button>
      <button class="btn-danger-outline" style="padding:10px 24px;font-size:13px" onclick="confirmDelete()">Xóa tài khoản</button>
    </div>
  </div>
</div>
<?php endif; ?>


<?php
$extraJs = '<script>
// ---- Tab switching ----
function switchTab(tab) {
  document.querySelectorAll(".tab-pane").forEach(p => p.classList.remove("active"));
  document.querySelectorAll(".snav-btn").forEach(b => b.classList.remove("active"));
  document.getElementById("pane-" + tab).classList.add("active");
  document.getElementById("tab-" + tab).classList.add("active");
}

// ---- Show/hide password ----
function togglePw(id, icon) {
  const el = document.getElementById(id);
  if (el.type === "password") {
    el.type = "text";
    icon.classList.replace("bi-eye-slash", "bi-eye");
  } else {
    el.type = "password";
    icon.classList.replace("bi-eye", "bi-eye-slash");
  }
}

// ---- Password strength ----
function checkPwStrength(v) {
  const bar = document.getElementById("pw-strength-bar");
  const txt = document.getElementById("pw-strength-txt");
  if (!v) { bar.style.width = "0%"; txt.textContent = ""; return; }
  let score = 0;
  if (v.length >= 6) score++;
  if (v.length >= 10) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^A-Za-z0-9]/.test(v)) score++;
  const levels = [
    {pct:"20%", color:"#ef4444", label:"Rất yếu"},
    {pct:"40%", color:"#f97316", label:"Yếu"},
    {pct:"60%", color:"#eab308", label:"Trung bình"},
    {pct:"80%", color:"#3b82f6", label:"Mạnh"},
    {pct:"100%",color:"#10b981", label:"Rất mạnh"},
  ];
  const lvl = levels[Math.min(score - 1, 4)] || levels[0];
  bar.style.width = lvl.pct;
  bar.style.background = lvl.color;
  txt.textContent = lvl.label;
  txt.style.color = lvl.color;
}

// ---- Alert helpers ----
function showAlert(okId, errId, msgId, success, msg) {
  const ok  = document.getElementById(okId);
  const err = document.getElementById(errId);
  if (ok)  ok.classList.remove("show");
  if (err) err.classList.remove("show");
  if (success) {
    if (ok) ok.classList.add("show");
    setTimeout(() => ok && ok.classList.remove("show"), 3500);
  } else {
    if (msgId && msg) document.getElementById(msgId).textContent = msg;
    if (err) { err.classList.add("show"); setTimeout(() => err.classList.remove("show"), 4000); }
  }
}

// ---- Modal helpers ----
function openModal(id)  { document.getElementById(id).classList.add("show"); }
function closeModal(id) { document.getElementById(id).classList.remove("show"); }
function openAddUser() {
  ["new_name","new_username","new_password"].forEach(id => document.getElementById(id).value = "");
  document.getElementById("new_role").value = "staff";
  document.getElementById("alert-adduser-err").classList.remove("show");
  openModal("modal-add-user");
}
document.querySelectorAll(".modal-overlay").forEach(m => m.addEventListener("click", e => { if (e.target === m) m.classList.remove("show"); }));

// ---- Save store settings ----
async function saveStore() {
  const data = {
    store_name:           document.getElementById("store_name").value.trim(),
    store_address:        document.getElementById("store_address").value.trim(),
    store_phone:          document.getElementById("store_phone").value.trim(),
    store_email:          document.getElementById("store_email").value.trim(),
    tax_code:             document.getElementById("tax_code").value.trim(),
    invoice_footer:       document.getElementById("invoice_footer").value.trim(),
    currency:             document.getElementById("currency").value,
    timezone:             document.getElementById("timezone").value,
    low_stock_threshold:  parseInt(document.getElementById("low_stock_threshold").value) || 10,
  };
  if (!data.store_name) { showAlert("alert-store-ok","alert-store-err","alert-store-msg",false,"Tên cửa hàng không được để trống"); return; }
  try {
    const res = await fetch("' . BASE_URL . '/api/settings.php", {
      method:"POST", headers:{"Content-Type":"application/json"},
      body: JSON.stringify({action:"save_store", ...data})
    });
    const json = await res.json();
    showAlert("alert-store-ok","alert-store-err","alert-store-msg", json.success, json.message || "Lỗi không xác định");
  } catch(e) { showAlert("alert-store-ok","alert-store-err","alert-store-msg", false, "Lỗi kết nối server"); }
}

// ---- Change password ----
async function changePassword() {
  const cur  = document.getElementById("pw_current").value;
  const nw   = document.getElementById("pw_new").value;
  const conf = document.getElementById("pw_confirm").value;
  if (!cur || !nw || !conf) { showAlert("alert-pw-ok","alert-pw-err","alert-pw-msg",false,"Vui lòng điền đầy đủ các trường"); return; }
  if (nw.length < 6)        { showAlert("alert-pw-ok","alert-pw-err","alert-pw-msg",false,"Mật khẩu mới tối thiểu 6 ký tự"); return; }
  if (nw !== conf)           { showAlert("alert-pw-ok","alert-pw-err","alert-pw-msg",false,"Xác nhận mật khẩu không khớp"); return; }
  try {
    const res  = await fetch("' . BASE_URL . '/api/account.php", {
      method:"POST", headers:{"Content-Type":"application/json"},
      body: JSON.stringify({action:"change_password", current_password: cur, new_password: nw})
    });
    const json = await res.json();
    showAlert("alert-pw-ok","alert-pw-err","alert-pw-msg", json.success, json.message || "Lỗi không xác định");
    if (json.success) { ["pw_current","pw_new","pw_confirm"].forEach(id => document.getElementById(id).value = ""); checkPwStrength(""); }
  } catch(e) { showAlert("alert-pw-ok","alert-pw-err","alert-pw-msg",false,"Lỗi kết nối server"); }
}

// ---- Add user ----
async function addUser() {
  const name     = document.getElementById("new_name").value.trim();
  const username = document.getElementById("new_username").value.trim();
  const password = document.getElementById("new_password").value;
  const role     = document.getElementById("new_role").value;
  if (!name || !username || !password) {
    document.getElementById("alert-adduser-msg").textContent = "Vui lòng điền đầy đủ thông tin";
    document.getElementById("alert-adduser-err").classList.add("show"); return;
  }
  if (password.length < 6) {
    document.getElementById("alert-adduser-msg").textContent = "Mật khẩu tối thiểu 6 ký tự";
    document.getElementById("alert-adduser-err").classList.add("show"); return;
  }
  try {
    const res  = await fetch("' . BASE_URL . '/api/account.php", {
      method:"POST", headers:{"Content-Type":"application/json"},
      body: JSON.stringify({action:"add_user", name, username, password, role})
    });
    const json = await res.json();
    if (json.success) {
      closeModal("modal-add-user");
      const avatar_cls = role === "admin" ? "avatar-admin" : "avatar-staff";
      const role_cls   = role === "admin" ? "role-admin" : "role-staff";
      const role_lbl   = role === "admin" ? "Admin" : "Nhân viên";
      const initial    = name.trim().charAt(0).toUpperCase();
      const html = `<div class="user-row" id="urow-${json.id}">
        <div class="user-avatar ${avatar_cls}">${initial}</div>
        <div style="flex:1;min-width:0">
          <div style="font-weight:700;font-size:13px">${name}</div>
          <div style="font-size:11px;color:#aaa">@${username}</div>
        </div>
        <span class="role-badge ${role_cls}">${role_lbl}</span>
        <div style="display:flex;gap:6px;margin-left:4px">
          <button class="btn-danger-outline" style="padding:5px 10px" onclick="deleteUser(\'${json.id}\',\'${name}\')">
            <i class="bi bi-trash3"></i>
          </button>
        </div>
      </div>`;
      document.getElementById("user-list").insertAdjacentHTML("beforeend", html);
    } else {
      document.getElementById("alert-adduser-msg").textContent = json.message || "Lỗi không xác định";
      document.getElementById("alert-adduser-err").classList.add("show");
    }
  } catch(e) {
    document.getElementById("alert-adduser-msg").textContent = "Lỗi kết nối server";
    document.getElementById("alert-adduser-err").classList.add("show");
  }
}

// ---- Delete user ----
function deleteUser(id, name) {
  document.getElementById("delete-user-id").value   = id;
  document.getElementById("delete-user-name").textContent = name;
  openModal("modal-confirm-delete");
}
async function confirmDelete() {
  const id = document.getElementById("delete-user-id").value;
  try {
    const res  = await fetch("' . BASE_URL . '/api/account.php", {
      method:"POST", headers:{"Content-Type":"application/json"},
      body: JSON.stringify({action:"delete_user", id})
    });
    const json = await res.json();
    if (json.success) {
      closeModal("modal-confirm-delete");
      const row = document.getElementById("urow-" + id);
      if (row) { row.style.opacity="0"; row.style.transition="opacity .3s"; setTimeout(() => row.remove(), 300); }
    } else { alert(json.message || "Không thể xóa tài khoản"); }
  } catch(e) { alert("Lỗi kết nối server"); }
}

// ---- Log filter ----
function filterLogs() {
  const action = document.getElementById("log-filter-action").value;
  const search = document.getElementById("log-search").value.toLowerCase();
  document.querySelectorAll(".log-row").forEach(row => {
    const matchAction = !action || row.dataset.action === action;
    const matchSearch = !search || row.dataset.detail.includes(search);
    row.style.display = (matchAction && matchSearch) ? "flex" : "none";
  });
}
</script>';
include __DIR__ . '/../includes/footer.php';
?>