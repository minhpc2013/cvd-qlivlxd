// ============================================================
//  VLXD PRO - MAIN JS
// ============================================================

// ── SIDEBAR TOGGLE ───────────────────────────────────────────
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').style.display =
    document.getElementById('sidebar').classList.contains('open') ? 'block' : 'none';
}

// ── AUTO DISMISS FLASH ───────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const flash = document.querySelector('.flash-msg');
  if (flash) setTimeout(() => flash.style.display = 'none', 4000);
});

// ── FORMAT CURRENCY ─────────────────────────────────────────
function formatVND(n) {
  return new Intl.NumberFormat('vi-VN').format(n) + ' đ';
}

// ── CONFIRM DELETE ───────────────────────────────────────────
function confirmDelete(msg = 'Xác nhận xoá mục này?') {
  return confirm(msg);
}

// ============================================================
//  POS MODULE
// ============================================================
let cart = [];

function addToCart(id, name, price, stock) {
  const existing = cart.find(i => i.id === id);
  if (existing) {
    if (existing.qty >= stock) { alert('Không đủ tồn kho!'); return; }
    existing.qty++;
  } else {
    cart.push({ id, name, price, qty: 1, stock });
  }
  renderCart();
}

function changeQty(id, delta) {
  const item = cart.find(i => i.id === id);
  if (!item) return;
  item.qty += delta;
  if (item.qty <= 0) cart = cart.filter(i => i.id !== id);
  renderCart();
}

function removeFromCart(id) {
  cart = cart.filter(i => i.id !== id);
  renderCart();
}

function clearCart() {
  cart = [];
  renderCart();
}

function renderCart() {
  const el = document.getElementById('cartItems');
  if (!el) return;

  if (cart.length === 0) {
    el.innerHTML = '<div style="text-align:center;padding:40px;color:#aaa"><i class="bi bi-cart" style="font-size:40px;display:block;margin-bottom:8px"></i>Chưa có sản phẩm</div>';
    updateCartTotal();
    return;
  }

  el.innerHTML = cart.map(item => `
    <div class="cart-item">
      <div class="cart-item-name">
        ${item.name}
        <div style="font-size:11px;color:#aaa">${formatVND(item.price)}</div>
      </div>
      <div class="cart-item-qty">
        <button class="qty-btn" onclick="changeQty('${item.id}', -1)">−</button>
        <span style="min-width:24px;text-align:center;font-weight:700">${item.qty}</span>
        <button class="qty-btn" onclick="changeQty('${item.id}', 1)">+</button>
      </div>
      <div class="cart-item-price">${formatVND(item.price * item.qty)}</div>
      <button onclick="removeFromCart('${item.id}')" style="background:none;border:none;color:#ef4444;cursor:pointer;padding:2px 4px">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
  `).join('');

  updateCartTotal();
}

function updateCartTotal() {
  const total = cart.reduce((s, i) => s + i.price * i.qty, 0);
  const el = document.getElementById('cartTotal');
  if (el) el.textContent = formatVND(total);

  const countEl = document.getElementById('cartCount');
  if (countEl) countEl.textContent = cart.reduce((s, i) => s + i.qty, 0);

  // Auto-fill paid
  const paidEl = document.getElementById('paidAmount');
  if (paidEl) paidEl.value = total;
  updateChange();
}

function updateChange() {
  const total = cart.reduce((s, i) => s + i.price * i.qty, 0);
  const paid  = parseFloat(document.getElementById('paidAmount')?.value || 0);
  const changeEl = document.getElementById('changeAmount');
  if (changeEl) {
    const change = paid - total;
    changeEl.textContent = formatVND(Math.max(0, change));
    changeEl.style.color = change < 0 ? '#ef4444' : '#10b981';
  }
}

function submitOrder() {
  if (cart.length === 0) { alert('Vui lòng thêm sản phẩm!'); return; }

  const customerName = document.getElementById('customerName')?.value || 'Khách lẻ';
  const phone        = document.getElementById('customerPhone')?.value || '';
  const customerId   = document.getElementById('customerId')?.value || '';
  const paid         = parseFloat(document.getElementById('paidAmount')?.value || 0);
  const note         = document.getElementById('orderNote')?.value || '';
  const total        = cart.reduce((s, i) => s + i.price * i.qty, 0);

  if (paid < 0) { alert('Số tiền thanh toán không hợp lệ!'); return; }

  document.getElementById('posCartData').value       = JSON.stringify(cart);
  document.getElementById('posCustomerName').value   = customerName;
  document.getElementById('posCustomerPhone').value  = phone;
  document.getElementById('posCustomerId').value     = customerId;
  document.getElementById('posPaid').value           = paid;
  document.getElementById('posNote').value           = note;
  document.getElementById('posTotal').value          = total;
  document.getElementById('posForm').submit();
}

// ── SEARCH PRODUCTS (POS) ────────────────────────────────────
function filterProducts(query) {
  const cards = document.querySelectorAll('.product-card');
  cards.forEach(card => {
    const name = card.dataset.name || '';
    card.style.display = name.toLowerCase().includes(query.toLowerCase()) ? '' : 'none';
  });
}

// ── CATEGORY FILTER ──────────────────────────────────────────
function filterByCategory(cat) {
  const cards = document.querySelectorAll('.product-card');
  cards.forEach(card => {
    card.style.display = (!cat || card.dataset.category === cat) ? '' : 'none';
  });
  document.querySelectorAll('.cat-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.cat === cat);
  });
}
