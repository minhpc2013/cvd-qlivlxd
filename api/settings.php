<?php
/**
 * api/settings.php
 * Xử lý lưu/đọc cài đặt cửa hàng
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Chỉ cho phép POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Yêu cầu đăng nhập và quyền admin
requireLogin();

// Login set: $_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role']
$currentUser = [
    'id'   => $_SESSION['user_id']   ?? null,
    'name' => $_SESSION['user_name'] ?? 'Admin',
    'role' => $_SESSION['user_role'] ?? 'staff',
];

if ($currentUser['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này']);
    exit;
}


$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$action = $input['action'] ?? '';

// ---- Đường dẫn file ----
$settingsFile = __DIR__ . '/../data/settings.json';
$dataDir      = __DIR__ . '/../data';

// Tạo thư mục data nếu chưa có
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// ---- ACTION: save_store ----
if ($action === 'save_store') {
    $allowed = [
        'store_name', 'store_address', 'store_phone', 'store_email',
        'tax_code', 'invoice_footer', 'currency', 'timezone', 'low_stock_threshold',
    ];

    // Validate
    $name = trim($input['store_name'] ?? '');
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Tên cửa hàng không được để trống']);
        exit;
    }

    // Load settings hiện tại (để giữ các key khác nếu có)
    $current = [];
    if (file_exists($settingsFile)) {
        $current = json_decode(file_get_contents($settingsFile), true) ?: [];
    }

    // Ghi đè các field được phép
    foreach ($allowed as $key) {
        if (isset($input[$key])) {
            $val = $input[$key];
            if ($key === 'low_stock_threshold') {
                $val = max(1, intval($val));
            } else {
                $val = htmlspecialchars(trim((string)$val), ENT_QUOTES, 'UTF-8');
            }
            $current[$key] = $val;
        }
    }
    $current['updated_at'] = date('Y-m-d H:i:s');
    $current['updated_by'] = $currentUser['name'] ?? $currentUser['username'] ?? 'admin';

    // Ghi file
    if (file_put_contents($settingsFile, json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        echo json_encode(['success' => false, 'message' => 'Không thể ghi file cài đặt. Kiểm tra quyền thư mục data/']);
        exit;
    }

    // Ghi log
    logActivity('UPDATE_SETTINGS', 'Cập nhật thông tin cửa hàng: ' . $name);

    echo json_encode(['success' => true, 'message' => 'Lưu cài đặt thành công']);
    exit;
}

// ---- ACTION: get_settings (GET đọc settings) ----
if ($action === 'get_settings') {
    $settings = [];
    if (file_exists($settingsFile)) {
        $settings = json_decode(file_get_contents($settingsFile), true) ?: [];
    }
    // Loại bỏ thông tin nhạy cảm trước khi trả về
    unset($settings['updated_by']);
    echo json_encode(['success' => true, 'data' => $settings]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
exit;
