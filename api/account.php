<?php
/**
 * api/account.php
 * Xử lý quản lý tài khoản: đổi mật khẩu, thêm / xóa user
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}


requireLogin();

// Login set: $_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role']
$currentUser = [
    'id'   => $_SESSION['user_id']   ?? null,
    'name' => $_SESSION['user_name'] ?? 'Admin',
    'role' => $_SESSION['user_role'] ?? 'staff',
];
$currentRole = $currentUser['role'];


$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$action    = $input['action'] ?? '';
$usersFile = __DIR__ . '/../data/users.json';

/**
 * Helper: đọc danh sách users
 */
function loadUsers(string $file): array {
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

/**
 * Helper: ghi danh sách users
 */
function saveUsers(string $file, array $users): bool {
    return file_put_contents(
        $file,
        json_encode(array_values($users), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

// ============================================================
// ACTION: change_password
// Yêu cầu: mọi user đã đăng nhập đều được đổi mật khẩu của mình
// ============================================================
if ($action === 'change_password') {
    $currentPw = $input['current_password'] ?? '';
    $newPw     = $input['new_password']     ?? '';

    if (empty($currentPw) || empty($newPw)) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin mật khẩu']);
        exit;
    }
    if (strlen($newPw) < 6) {
        echo json_encode(['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự']);
        exit;
    }

    $users = loadUsers($usersFile);
    $uid   = $currentUser['id'] ?? null;
    $found = false;

    foreach ($users as &$u) {
        if ($u['id'] !== $uid) continue;
        $found = true;

        // Verify mật khẩu hiện tại — hỗ trợ cả $2y$ (PHP bcrypt) và $2a$ (bcrypt chung)
        if (!password_verify($currentPw, $u['password'])) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng']);
            exit;
        }

        // Hash mật khẩu mới với bcrypt
        $u['password']   = password_hash($newPw, PASSWORD_BCRYPT);
        $u['updated_at'] = date('Y-m-d H:i:s');
        break;
    }
    unset($u);

    if (!$found) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản']);
        exit;
    }

    if (!saveUsers($usersFile, $users)) {
        echo json_encode(['success' => false, 'message' => 'Không thể lưu dữ liệu. Kiểm tra quyền thư mục data/']);
        exit;
    }

    logActivity('CHANGE_PASSWORD', 'Đổi mật khẩu tài khoản: ' . ($currentUser['username'] ?? ''));
    echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công']);
    exit;
}

// ============================================================
// ACTION: add_user   (chỉ admin)
// ============================================================
if ($action === 'add_user') {
    if ($currentRole !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thêm tài khoản']);
        exit;
    }

    $name     = trim($input['name']     ?? '');
    $username = trim($input['username'] ?? '');
    $password = $input['password']      ?? '';
    $role     = $input['role']          ?? 'staff';

    // Validate
    if (empty($name) || empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
        exit;
    }
    if (!preg_match('/^[a-zA-Z0-9_\.]+$/', $username)) {
        echo json_encode(['success' => false, 'message' => 'Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới']);
        exit;
    }
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Mật khẩu tối thiểu 6 ký tự']);
        exit;
    }
    if (!in_array($role, ['admin', 'staff'])) {
        $role = 'staff';
    }

    $users = loadUsers($usersFile);

    // Kiểm tra username trùng
    foreach ($users as $u) {
        if (strtolower($u['username']) === strtolower($username)) {
            echo json_encode(['success' => false, 'message' => "Tên đăng nhập \"$username\" đã tồn tại"]);
            exit;
        }
    }

    // Tạo user mới
    $newId = 'USR' . strtoupper(substr(md5(uniqid($username, true)), 0, 8));
    $newUser = [
        'id'         => $newId,
        'username'   => $username,
        'password'   => password_hash($password, PASSWORD_BCRYPT),
        'name'       => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
        'role'       => $role,
        'created_at' => date('Y-m-d H:i:s'),
        'created_by' => $currentUser['name'] ?? 'admin',
    ];
    $users[] = $newUser;

    if (!saveUsers($usersFile, $users)) {
        echo json_encode(['success' => false, 'message' => 'Không thể lưu dữ liệu. Kiểm tra quyền thư mục data/']);
        exit;
    }

    logActivity('ADD_USER', "Thêm tài khoản: $name (@$username) - quyền: $role");
    echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Tạo tài khoản thành công']);
    exit;
}

// ============================================================
// ACTION: delete_user   (chỉ admin)
// ============================================================
if ($action === 'delete_user') {
    if ($currentRole !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa tài khoản']);
        exit;
    }

    $targetId = $input['id'] ?? '';
    if (empty($targetId)) {
        echo json_encode(['success' => false, 'message' => 'Thiếu ID tài khoản']);
        exit;
    }

    // Không cho xóa chính mình
    if ($targetId === ($currentUser['id'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa tài khoản của chính bạn']);
        exit;
    }

    $users   = loadUsers($usersFile);
    $admins  = array_filter($users, fn($u) => $u['role'] === 'admin');
    $target  = null;

    foreach ($users as $u) {
        if ($u['id'] === $targetId) { $target = $u; break; }
    }

    if (!$target) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản']);
        exit;
    }

    // Không cho xóa admin duy nhất còn lại
    if ($target['role'] === 'admin' && count($admins) <= 1) {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa admin duy nhất của hệ thống']);
        exit;
    }

    $users = array_filter($users, fn($u) => $u['id'] !== $targetId);

    if (!saveUsers($usersFile, array_values($users))) {
        echo json_encode(['success' => false, 'message' => 'Không thể lưu dữ liệu']);
        exit;
    }

    $deletedName = $target['name'] ?? $target['username'] ?? $targetId;
    logActivity('DELETE_USER', "Xóa tài khoản: $deletedName");
    echo json_encode(['success' => true, 'message' => 'Đã xóa tài khoản']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
exit;
