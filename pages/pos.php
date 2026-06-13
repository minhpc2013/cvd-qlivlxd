<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$pageTitle  = 'Bán hàng (POS)';
$activePage = 'pos';
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = json_decode($_POST['posCartData'] ?? '[]', true);
    if (!empty($items)) {
        $order = addOrder([
            'customer_name' => $_POST['posCustomerName'] ?: 'Khách lẻ',
            'customer_id'   => $_POST['posCustomerId'] ?? '',
            'phone'         => $_POST['posCustomerPhone'] ?? '',
            'items'         => $items,
            'paid'          => floatval($_POST['posPaid'] ?? 0),
            'note'          => $_POST['posNote'] ?? '',
        ]);
        $flash = ['type'=>'success','msg'=>"✅ Đơn hàng #{$order['id']} đã tạo thành công! Tổng: " . vnd($order['total']), 'order_id' => $order['id']];
    }
}

$products  = array_filter(getAllProducts(), fn($p) => $p['stock'] > 0);
$customers = getAllCustomers();
$categories = array_unique(array_column(iterator_to_array((function() use ($products) {
    foreach ($products as $p) yield $p;
})(), false), 'category'));

include __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?>
<div class="flash-msg flash-<?= $flash['type'] ?>" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
  <span><?= $flash['msg'] ?></span>
  <?php if (!empty($flash['order_id'])): ?>
  <a href="<?= BASE_URL ?>/api/export_invoice.php?order_id=<?= urlencode($flash['order_id']) ?>"
     target="_blank"
     style="display:inline-flex;align-items:center;gap:7px;background:#fff;color:#1D4ED8;border:1.5px solid #93C5FD;border-radius:8px;padding:7px 16px;font-size:13px;font-weight:700;text-decoration:none;white-space:nowrap;transition:all .15s"
     onmouseover="this.style.background='#EFF6FF'"
     onmouseout="this.style.background='#fff'">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
      <path d="M5.485 6.879a.5.5 0 1 0-.97.242l1.5 6a.5.5 0 0 0 .97-.242zm2.178.542a.5.5 0 0 0-.933.364l1 2.5a.5.5 0 0 0 .933-.364zm3.59-.364a.5.5 0 0 0-.933.364l1 2.5a.5.5 0 0 0 .933-.364z"/>
      <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5z"/>
    </svg>
    Xuất hóa đơn Word
  </a>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="pos-grid">
  <!-- LEFT: PRODUCTS -->
  <div class="pos-products">
    <!-- Search + Filter -->
    <div style="display:flex;gap:10px;margin-bottom:12px;flex-wrap:wrap">
      <input type="text" class="form-control" style="max-width:280px"
             placeholder="🔍 Tìm sản phẩm…" oninput="filterProducts(this.value)">
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <button class="btn btn-sm btn-outline-secondary cat-btn active" data-cat="" onclick="filterByCategory('')">Tất cả</button>
        <?php foreach ($categories as $cat): ?>
        <button class="btn btn-sm btn-outline-secondary cat-btn" data-cat="<?= $cat ?>" onclick="filterByCategory('<?= $cat ?>')"><?= $cat ?></button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Product Grid -->
    <div class="product-grid">
      <?php foreach ($products as $p):
        $pid    = $p['id']         ?? '';
        $pname  = $p['name']       ?? '—';
        $pcat   = $p['category']   ?? '';
        $psell  = floatval($p['price_sell'] ?? 0);
        $pstock = intval($p['stock']  ?? 0);
        $punit  = $p['unit']       ?? '';
        $emoji  = ['Xi măng'=>'🏗️','Gạch ngói'=>'🧱','Sắt thép'=>'⚙️','Sơn'=>'🎨','Ống nước'=>'🔧','Cát đá'=>'⛏️'][$pcat] ?? '📦';
        if ($pstock <= 0) continue; // Bỏ qua hàng hết kho
      ?>
      <div class="product-card"
           data-name="<?= htmlspecialchars($pname) ?>"
           data-category="<?= htmlspecialchars($pcat) ?>"
           onclick="addToCart('<?= htmlspecialchars($pid) ?>','<?= addslashes($pname) ?>',<?= $psell ?>,<?= $pstock ?>)">
        <div style="font-size:24px;margin-bottom:8px"><?= $emoji ?></div>
        <div class="p-name"><?= htmlspecialchars($pname) ?></div>
        <div class="p-price"><?= vnd($psell) ?>/<?= htmlspecialchars($punit) ?></div>
        <div class="p-stock">Còn: <?= $pstock ?> <?= htmlspecialchars($punit) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- RIGHT: CART -->
  <div class="pos-cart">
    <div style="padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px">
      <span style="font-weight:700;font-size:15px"><i class="bi bi-cart3" style="color:#E85D04"></i> Giỏ hàng</span>
      <span id="cartCount" style="background:#E85D04;color:#fff;border-radius:20px;padding:2px 8px;font-size:12px;font-weight:700">0</span>
      <button onclick="clearCart()" class="btn btn-sm btn-outline-danger ms-auto">🗑 Xoá</button>
    </div>

    <!-- Customer -->
    <div style="padding:12px 16px;border-bottom:1px solid var(--border);background:#FAFAFA">
      <div class="row g-2">
        <div class="col-7">
          <input id="customerName" class="form-control form-control-sm" placeholder="👤 Tên khách hàng" list="customerList">
          <datalist id="customerList">
            <?php foreach ($customers as $c): ?>
            <option value="<?= htmlspecialchars($c['name']) ?>" data-id="<?= $c['id'] ?>"><?= $c['phone'] ?></option>
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="col-5">
          <input id="customerPhone" class="form-control form-control-sm" placeholder="📞 SĐT">
        </div>
        <input type="hidden" id="customerId">
      </div>
    </div>

    <!-- Items -->
    <div class="cart-items" id="cartItems">
      <div style="text-align:center;padding:40px;color:#aaa">
        <i class="bi bi-cart" style="font-size:40px;display:block;margin-bottom:8px"></i>
        Chưa có sản phẩm
      </div>
    </div>

    <!-- Footer -->
    <div class="cart-footer">
      <div style="display:flex;justify-content:space-between;font-size:18px;font-weight:700;margin-bottom:12px">
        <span>Tổng cộng</span>
        <span id="cartTotal" style="color:#E85D04">0 đ</span>
      </div>
      <div class="row g-2 mb-2">
        <div class="col-6">
          <label style="font-size:12px;color:#aaa">Tiền khách trả</label>
          <input id="paidAmount" type="number" class="form-control" value="0" oninput="updateChange()">
        </div>
        <div class="col-6">
          <label style="font-size:12px;color:#aaa">Tiền thừa</label>
          <div id="changeAmount" style="font-size:16px;font-weight:700;padding:8px;color:#10b981">0 đ</div>
        </div>
      </div>
      <input id="orderNote" class="form-control mb-3" style="font-size:13px" placeholder="📝 Ghi chú đơn hàng…">
      <button onclick="submitOrder()" class="btn btn-primary w-100" style="padding:12px;font-size:15px">
        <i class="bi bi-check-circle me-2"></i>Hoàn tất đơn hàng
      </button>
    </div>
  </div>
</div>

<!-- Hidden POST form -->
<form id="posForm" method="POST" style="display:none">
  <input type="hidden" id="posCartData"      name="posCartData">
  <input type="hidden" id="posCustomerName"  name="posCustomerName">
  <input type="hidden" id="posCustomerPhone" name="posCustomerPhone">
  <input type="hidden" id="posCustomerId"    name="posCustomerId">
  <input type="hidden" id="posPaid"          name="posPaid">
  <input type="hidden" id="posNote"          name="posNote">
  <input type="hidden" id="posTotal"         name="posTotal">
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>