<?php
// ============================================================
//  HÀM DÙNG CHUNG - includes/functions.php
// ============================================================

// ── JSON I/O ─────────────────────────────────────────────────
function readJSON(string $file): array {
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function writeJSON(string $file, array $data): bool {
    return file_put_contents(
        $file,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

function generateId(string $prefix = ''): string {
    return $prefix . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

// ── ACTIVITY LOG ─────────────────────────────────────────────
function logActivity(string $action, string $detail): void {
    $logs   = readJSON(ACTIVITY_FILE);
    $logs[] = [
        'id'         => generateId('LOG'),
        'user'       => $_SESSION['user_name'] ?? 'System',
        'action'     => $action,
        'detail'     => $detail,
        'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
        'created_at' => date('Y-m-d H:i:s'),
    ];
    // Giữ 1000 log gần nhất
    if (count($logs) > 1000) $logs = array_slice($logs, -1000);
    writeJSON(ACTIVITY_FILE, $logs);
}

// ── AUTH ─────────────────────────────────────────────────────
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function hasRole(string $role): bool {
    $hierarchy = ['viewer' => 0, 'cashier' => 1, 'warehouse' => 1, 'accountant' => 2, 'admin' => 3];
    $userRole  = $_SESSION['user_role'] ?? 'viewer';
    return ($hierarchy[$userRole] ?? 0) >= ($hierarchy[$role] ?? 99);
}

function currentUser(): array {
    return [
        'id'   => $_SESSION['user_id']   ?? '',
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['user_role'] ?? '',
    ];
}

// ── SẢN PHẨM ─────────────────────────────────────────────────
function getAllProducts(): array { return readJSON(PRODUCTS_FILE); }

function getProductById(string $id): ?array {
    foreach (getAllProducts() as $p) {
        if ($p['id'] === $id) return $p;
    }
    return null;
}

function addProduct(array $data): array {
    $products   = getAllProducts();
    $data['id'] = generateId('SP');
    $data['created_at'] = date('Y-m-d H:i:s');
    $data['stock']      = intval($data['stock'] ?? 0);
    $data['price_buy']  = floatval($data['price_buy'] ?? 0);
    $data['price_sell'] = floatval($data['price_sell'] ?? 0);
    $products[] = $data;
    writeJSON(PRODUCTS_FILE, $products);
    logActivity('ADD_PRODUCT', "Thêm sản phẩm: {$data['name']}");
    return $data;
}

function updateProduct(string $id, array $newData): bool {
    $products = getAllProducts();
    foreach ($products as &$p) {
        if ($p['id'] === $id) {
            $p = array_merge($p, $newData);
            $p['updated_at'] = date('Y-m-d H:i:s');
            writeJSON(PRODUCTS_FILE, $products);
            logActivity('UPDATE_PRODUCT', "Cập nhật sản phẩm: {$p['name']}");
            return true;
        }
    }
    return false;
}

function deleteProduct(string $id): bool {
    $p = getProductById($id);
    $products = array_values(array_filter(getAllProducts(), fn($x) => $x['id'] !== $id));
    writeJSON(PRODUCTS_FILE, $products);
    logActivity('DELETE_PRODUCT', "Xoá sản phẩm: " . ($p['name'] ?? $id));
    return true;
}

function updateStock(string $id, int $qty, string $reason = ''): bool {
    $products = getAllProducts();
    foreach ($products as &$p) {
        if ($p['id'] === $id) {
            $before = $p['stock'];
            $p['stock'] += $qty;
            writeJSON(PRODUCTS_FILE, $products);
            // Ghi log kho
            $logs   = readJSON(STOCK_LOGS_FILE);
            $logs[] = [
                'id'         => generateId('KHO'),
                'product_id' => $id,
                'product'    => $p['name'],
                'before'     => $before,
                'change'     => $qty,
                'after'      => $p['stock'],
                'reason'     => $reason,
                'user'       => $_SESSION['user_name'] ?? 'System',
                'created_at' => date('Y-m-d H:i:s'),
            ];
            writeJSON(STOCK_LOGS_FILE, $logs);
            return true;
        }
    }
    return false;
}

function getLowStockProducts(int $threshold = 10): array {
    return array_values(array_filter(getAllProducts(), fn($p) => $p['stock'] <= $threshold));
}

// ── ĐƠN HÀNG ─────────────────────────────────────────────────
function getAllOrders(): array { return readJSON(ORDERS_FILE); }

function addOrder(array $data): array {
    $orders     = getAllOrders();
    $data['id'] = generateId('DH');
    $data['created_at'] = date('Y-m-d H:i:s');
    $data['status']     = $data['status'] ?? 'pending';
    $data['total']      = array_sum(array_map(fn($i) => $i['qty'] * $i['price'], $data['items']));
    $data['paid']       = floatval($data['paid'] ?? 0);
    $data['debt']       = $data['total'] - $data['paid'];

    // Trừ tồn kho
    foreach ($data['items'] as $item) {
        updateStock($item['product_id'], -intval($item['qty']), "Bán hàng ĐH#{$data['id']}");
    }

    // Ghi công nợ nếu còn nợ
    if ($data['debt'] > 0 && !empty($data['customer_id'])) {
        addDebt([
            'type'        => 'receivable',
            'customer_id' => $data['customer_id'],
            'customer'    => $data['customer_name'],
            'order_id'    => $data['id'],
            'amount'      => $data['debt'],
            'note'        => "Nợ từ đơn hàng #{$data['id']}",
        ]);
    }

    // Ghi dòng tiền
    if ($data['paid'] > 0) {
        addCashFlow([
            'type'      => 'income',
            'amount'    => $data['paid'],
            'category'  => 'Bán hàng',
            'ref_id'    => $data['id'],
            'note'      => "Thu tiền ĐH#{$data['id']} - {$data['customer_name']}",
        ]);
    }

    $orders[] = $data;
    writeJSON(ORDERS_FILE, $orders);
    logActivity('ADD_ORDER', "Tạo đơn hàng #{$data['id']} - KH: {$data['customer_name']}");
    return $data;
}

function updateOrderStatus(string $id, string $status): bool {
    $orders = getAllOrders();
    foreach ($orders as &$o) {
        if ($o['id'] === $id) {
            $o['status']     = $status;
            $o['updated_at'] = date('Y-m-d H:i:s');
            writeJSON(ORDERS_FILE, $orders);
            logActivity('UPDATE_ORDER', "Cập nhật trạng thái ĐH#{$id}: {$status}");
            return true;
        }
    }
    return false;
}

// ── KHÁCH HÀNG ───────────────────────────────────────────────
function getAllCustomers(): array { return readJSON(CUSTOMERS_FILE); }

function getCustomerById(string $id): ?array {
    foreach (getAllCustomers() as $c) {
        if ($c['id'] === $id) return $c;
    }
    return null;
}

function addCustomer(array $data): array {
    $customers  = getAllCustomers();
    $data['id'] = generateId('KH');
    $data['created_at'] = date('Y-m-d H:i:s');
    $data['points']     = 0;
    $data['type']       = $data['type'] ?? 'retail';
    $customers[] = $data;
    writeJSON(CUSTOMERS_FILE, $customers);
    logActivity('ADD_CUSTOMER', "Thêm khách hàng: {$data['name']}");
    return $data;
}

function updateCustomer(string $id, array $newData): bool {
    $customers = getAllCustomers();
    foreach ($customers as &$c) {
        if ($c['id'] === $id) {
            $c = array_merge($c, $newData);
            $c['updated_at'] = date('Y-m-d H:i:s');
            writeJSON(CUSTOMERS_FILE, $customers);
            return true;
        }
    }
    return false;
}

// ── NHÀ CUNG CẤP ─────────────────────────────────────────────
function getAllSuppliers(): array { return readJSON(SUPPLIERS_FILE); }

function addSupplier(array $data): array {
    $suppliers  = getAllSuppliers();
    $data['id'] = generateId('NCC');
    $data['created_at'] = date('Y-m-d H:i:s');
    $suppliers[] = $data;
    writeJSON(SUPPLIERS_FILE, $suppliers);
    logActivity('ADD_SUPPLIER', "Thêm nhà cung cấp: {$data['name']}");
    return $data;
}

// ── CÔNG NỢ ──────────────────────────────────────────────────
function getAllDebts(): array { return readJSON(DEBTS_FILE); }

function addDebt(array $data): array {
    $debts     = getAllDebts();
    $data['id'] = generateId('CN');
    $data['created_at'] = date('Y-m-d H:i:s');
    $data['status']     = 'unpaid';
    $data['paid_amount'] = 0;
    $debts[] = $data;
    writeJSON(DEBTS_FILE, $debts);
    return $data;
}

function payDebt(string $id, float $amount): bool {
    $debts = getAllDebts();
    foreach ($debts as &$d) {
        if ($d['id'] === $id) {
            $d['paid_amount'] += $amount;
            $d['status'] = $d['paid_amount'] >= $d['amount'] ? 'paid' : 'partial';
            $d['paid_at'] = date('Y-m-d H:i:s');
            writeJSON(DEBTS_FILE, $debts);
            logActivity('PAY_DEBT', "Thanh toán công nợ #{$id}: " . number_format($amount));
            return true;
        }
    }
    return false;
}

function getTotalDebt(string $type = 'receivable'): float {
    $total = 0;
    foreach (getAllDebts() as $d) {
        if ($d['type'] === $type && $d['status'] !== 'paid') {
            $total += $d['amount'] - $d['paid_amount'];
        }
    }
    return $total;
}

// ── DÒNG TIỀN ────────────────────────────────────────────────
function getAllCashFlow(): array { return readJSON(CASHFLOW_FILE); }

function addCashFlow(array $data): array {
    $flows     = getAllCashFlow();
    $data['id'] = generateId('CF');
    $data['created_at'] = date('Y-m-d H:i:s');
    $data['user']       = $_SESSION['user_name'] ?? 'System';
    $flows[] = $data;
    writeJSON(CASHFLOW_FILE, $flows);
    return $data;
}

function getCashBalance(): float {
    $balance = 0;
    foreach (getAllCashFlow() as $f) {
        $balance += ($f['type'] === 'income') ? $f['amount'] : -$f['amount'];
    }
    return $balance;
}

// ── THỐNG KÊ / DASHBOARD ─────────────────────────────────────
function getDashboardStats(): array {
    $orders    = getAllOrders();
    $products  = getAllProducts();
    $customers = getAllCustomers();
    $today     = date('Y-m-d');

    $todayOrders   = array_filter($orders, fn($o) => str_starts_with($o['created_at'], $today));
    $todayRevenue  = array_sum(array_column(array_values($todayOrders), 'paid'));
    $monthOrders   = array_filter($orders, fn($o) => str_starts_with($o['created_at'], date('Y-m')));
    $monthRevenue  = array_sum(array_column(array_values($monthOrders), 'paid'));

    return [
        'total_products'    => count($products),
        'total_customers'   => count($customers),
        'total_orders'      => count($orders),
        'today_orders'      => count($todayOrders),
        'today_revenue'     => $todayRevenue,
        'month_revenue'     => $monthRevenue,
        'cash_balance'      => getCashBalance(),
        'total_debt_recv'   => getTotalDebt('receivable'),
        'total_debt_pay'    => getTotalDebt('payable'),
        'low_stock_count'   => count(getLowStockProducts()),
    ];
}

function getRevenueByMonth(int $year): array {
    $data   = array_fill(1, 12, 0);
    foreach (getAllOrders() as $o) {
        $m = (int) date('m', strtotime($o['created_at']));
        $y = (int) date('Y', strtotime($o['created_at']));
        if ($y === $year) $data[$m] += $o['paid'];
    }
    return $data;
}

function getTopProducts(int $limit = 5): array {
    $sales = [];
    foreach (getAllOrders() as $order) {
        foreach ($order['items'] as $item) {
            $pid = $item['product_id'];
            if (!isset($sales[$pid])) {
                $sales[$pid] = ['name' => $item['name'], 'qty' => 0, 'revenue' => 0];
            }
            $sales[$pid]['qty']     += $item['qty'];
            $sales[$pid]['revenue'] += $item['qty'] * $item['price'];
        }
    }
    uasort($sales, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
    return array_slice(array_values($sales), 0, $limit);
}

// ── FORMAT HELPERS ───────────────────────────────────────────
function vnd(float $amount): string {
    return number_format($amount, 0, ',', '.') . ' đ';
}

function statusBadge(string $status): string {
    $map = [
        'pending'   => ['Chờ xử lý',  'badge-warning'],
        'confirmed' => ['Đã xác nhận','badge-info'],
        'delivered' => ['Đã giao',    'badge-success'],
        'cancelled' => ['Đã huỷ',     'badge-danger'],
        'unpaid'    => ['Chưa trả',   'badge-danger'],
        'partial'   => ['Trả 1 phần', 'badge-warning'],
        'paid'      => ['Đã trả',     'badge-success'],
    ];
    [$label, $class] = $map[$status] ?? [$status, 'badge-secondary'];
    return "<span class=\"badge {$class}\">{$label}</span>";
}

// ── SEED DATA (Demo) ─────────────────────────────────────────
function seedDemoData(): void {
    if (count(getAllProducts()) > 0) return;

    $products = [
        ['name'=>'Xi măng Hà Tiên PCB40','category'=>'Xi măng','unit'=>'bao','price_buy'=>88000,'price_sell'=>95000,'stock'=>200],
        ['name'=>'Xi măng INSEE PCB50','category'=>'Xi măng','unit'=>'bao','price_buy'=>95000,'price_sell'=>105000,'stock'=>150],
        ['name'=>'Gạch đặc 4x8x19','category'=>'Gạch','unit'=>'viên','price_buy'=>1200,'price_sell'=>1500,'stock'=>5000],
        ['name'=>'Gạch thẻ ốp lát 30x30','category'=>'Gạch','unit'=>'m²','price_buy'=>85000,'price_sell'=>110000,'stock'=>300],
        ['name'=>'Thép D10 CB300-V','category'=>'Sắt thép','unit'=>'kg','price_buy'=>16500,'price_sell'=>18000,'stock'=>2000],
        ['name'=>'Thép D14 CB400-V','category'=>'Sắt thép','unit'=>'kg','price_buy'=>17000,'price_sell'=>19000,'stock'=>1500],
        ['name'=>'Sơn nội thất Dulux 18L','category'=>'Sơn','unit'=>'thùng','price_buy'=>620000,'price_sell'=>720000,'stock'=>50],
        ['name'=>'Sơn chống thấm Kova 20kg','category'=>'Sơn','unit'=>'thùng','price_buy'=>380000,'price_sell'=>450000,'stock'=>8],
        ['name'=>'Ống nhựa PVC D90','category'=>'Ống nước','unit'=>'cây','price_buy'=>95000,'price_sell'=>115000,'stock'=>120],
        ['name'=>'Cát xây 1m³','category'=>'Cát đá','unit'=>'m³','price_buy'=>180000,'price_sell'=>220000,'stock'=>30],
    ];

    foreach ($products as $p) addProduct($p);

    $customers = [
        ['name'=>'Nguyễn Văn An','phone'=>'0901234567','address'=>'123 Lê Lợi, Q1','type'=>'wholesale'],
        ['name'=>'Trần Thị Bình','phone'=>'0912345678','address'=>'45 Nguyễn Huệ, Q3','type'=>'retail'],
        ['name'=>'Công ty XD Minh Phát','phone'=>'0283456789','address'=>'78 Đinh Tiên Hoàng, Bình Thạnh','type'=>'wholesale'],
    ];
    foreach ($customers as $c) addCustomer($c);

    $suppliers = [
        ['name'=>'Công ty Xi măng Hà Tiên','phone'=>'0281111111','address'=>'Kiên Giang','category'=>'Xi măng','contact'=>'Anh Hùng'],
        ['name'=>'Nhà máy Thép Miền Nam','phone'=>'0282222222','address'=>'Bình Dương','category'=>'Sắt thép','contact'=>'Chị Lan'],
    ];
    foreach ($suppliers as $s) addSupplier($s);
}
