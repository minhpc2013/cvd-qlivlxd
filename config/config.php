<?php
// ============================================================
//  CẤU HÌNH HỆ THỐNG QUẢN LÝ CỬA HÀNG VLXD
// ============================================================
define('SITE_NAME',      'VLXD Manager');
define('VERSION',        '1.0.0');
define('DATA_PATH',      __DIR__ . '/../data/');
define('BASE_URL',       '/store-vlxd');

// --- File paths ---
define('PRODUCTS_FILE',   DATA_PATH . 'products.json');
define('ORDERS_FILE',     DATA_PATH . 'orders.json');
define('CUSTOMERS_FILE',  DATA_PATH . 'customers.json');
define('SUPPLIERS_FILE',  DATA_PATH . 'suppliers.json');
define('EMPLOYEES_FILE',  DATA_PATH . 'employees.json');
define('DEBTS_FILE',      DATA_PATH . 'debts.json');
define('CASHFLOW_FILE',   DATA_PATH . 'cashflow.json');
define('STOCK_LOGS_FILE', DATA_PATH . 'stock_logs.json');
define('ACTIVITY_FILE',   DATA_PATH . 'activity_logs.json');
define('USERS_FILE',      DATA_PATH . 'users.json');

// --- Khởi tạo file JSON nếu chưa tồn tại ---
$jsonFiles = [
    PRODUCTS_FILE, ORDERS_FILE, CUSTOMERS_FILE,
    SUPPLIERS_FILE, EMPLOYEES_FILE, DEBTS_FILE,
    CASHFLOW_FILE, STOCK_LOGS_FILE, ACTIVITY_FILE,
];
foreach ($jsonFiles as $file) {
    if (!file_exists($file)) {
        file_put_contents($file, json_encode([]));
    }
}

// --- Tạo tài khoản admin mặc định nếu chưa có ---
if (!file_exists(USERS_FILE)) {
    $defaultUsers = [[
        'id'         => 'admin001',
        'username'   => 'admin',
        'password'   => password_hash('admin123', PASSWORD_DEFAULT),
        'name'       => 'Quản trị viên',
        'role'       => 'admin',
        'created_at' => date('Y-m-d H:i:s'),
    ]];
    file_put_contents(USERS_FILE, json_encode($defaultUsers, JSON_PRETTY_PRINT));
}

// --- Session ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Timezone ---
date_default_timezone_set('Asia/Ho_Chi_Minh');
