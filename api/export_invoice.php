<?php
/**
 * api/export_invoice.php
 * Xuất hóa đơn bán hàng ra file Word (.docx)
 * Gọi: GET /api/export_invoice.php?order_id=DHXXXXX
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$orderId = $_GET['order_id'] ?? '';
if (empty($orderId)) {
    http_response_code(400);
    exit('Thiếu mã đơn hàng');
}

// Tìm đơn hàng
$orders = getAllOrders();
$order  = null;
foreach ($orders as $o) {
    if (($o['id'] ?? '') === $orderId) { $order = $o; break; }
}
if (!$order) {
    http_response_code(404);
    exit('Không tìm thấy đơn hàng');
}

// Load cài đặt cửa hàng
$settingsFile = __DIR__ . '/../data/settings.json';
$settings = file_exists($settingsFile)
    ? (json_decode(file_get_contents($settingsFile), true) ?: [])
    : [];
$settings = array_merge([
    'store_name'     => 'Cửa hàng Vật liệu Xây dựng',
    'store_address'  => '',
    'store_phone'    => '',
    'store_email'    => '',
    'tax_code'       => '',
    'invoice_footer' => 'Cảm ơn quý khách đã mua hàng!',
], $settings);

// Truyền data sang Node.js qua JSON
$payload = json_encode([
    'order'    => $order,
    'settings' => $settings,
], JSON_UNESCAPED_UNICODE);

// Đường dẫn script Node.js (cùng thư mục api/)
$scriptPath = __DIR__ . '/generate_invoice_docx.js';
if (!file_exists($scriptPath)) {
    http_response_code(500);
    exit('Không tìm thấy script tạo file Word. Vui lòng kiểm tra generate_invoice_docx.js');
}

// Tạo file tạm
$tmpFile = sys_get_temp_dir() . '/invoice_' . $orderId . '_' . time() . '.docx';

// Chạy Node.js — tương thích cả Windows lẫn Linux
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
$nodeCmd   = $isWindows ? 'node.exe' : 'node';

// Ghi JSON ra file tạm thay vì truyền qua arg (tránh lỗi escape trên Windows)
$tmpJson = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'invoice_data_' . $orderId . '_' . time() . '.json';
file_put_contents($tmpJson, $payload);

$escapedScript = escapeshellarg($scriptPath);
$escapedJson   = escapeshellarg($tmpJson);
$escapedOutput = escapeshellarg($tmpFile);
$cmd = "$nodeCmd $escapedScript $escapedJson $escapedOutput 2>&1";
$output = shell_exec($cmd);

// Xóa file JSON tạm
if (file_exists($tmpJson)) unlink($tmpJson);

if (!file_exists($tmpFile) || filesize($tmpFile) === 0) {
    http_response_code(500);
    exit('Lỗi tạo file Word: ' . htmlspecialchars($output));
}

// Stream file về browser
$filename = 'HoaDon_' . $orderId . '_' . date('dmY') . '.docx';
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tmpFile));
header('Cache-Control: no-cache');
readfile($tmpFile);
unlink($tmpFile);
exit;