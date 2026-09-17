/**
 * Sakshi Infotech - Storefront Interactions & Dynamic Cart Management
 */

document.addEventListener('DOMContentLoaded', () => {
  initCartDrawer();
  initAddToCartButtons();
  initCheckoutPaymentToggle();
  initCategoriesDropdown();
  initDetailAddToCart();
});

// -------------------------------------------------------------
// Cart Drawer Handling
// -------------------------------------------------------------
function initCartDrawer() {
  const overlay = document.getElementById('cartDrawerOverlay');
  const drawer = document.getElementById('cartDrawer');
  const openBtns = document.querySelectorAll('.trigger-cart-drawer');
  const closeBtns = document.querySelectorAll('.btn-close-drawer, #cartDrawerOverlay');

  openBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      openCartDrawer();
    });
  });

  closeBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      closeCartDrawer();
    });
  });
}

function openCartDrawer() {
  const overlay = document.getElementById('cartDrawerOverlay');
  const drawer = document.getElementById('cartDrawer');
  if (overlay && drawer) {
    overlay.classList.add('active');
    drawer.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

function closeCartDrawer() {
  const overlay = document.getElementById('cartDrawerOverlay');
  const drawer = document.getElementById('cartDrawer');
  if (overlay && drawer) {
    overlay.classList.remove('active');
    drawer.classList.remove('active');
    document.body.style.overflow = '';
  }
}

// Dynamic base URL helper for subfolder (e.g. /sales/)
function getAppUrl(path) {
  if (typeof window.__BASE_URL__ !== 'undefined' && window.__BASE_URL__) {
    return window.__BASE_URL__ + path.replace(/^\//, '');
  }
  const pathname = window.location.pathname;
  const idx = pathname.indexOf('/sales/');
  if (idx !== -1) {
    return pathname.substring(0, idx) + '/sales/' + path.replace(/^\//, '');
  }
  return '/' + path.replace(/^\//, '');
}

// -------------------------------------------------------------
// Categories Dropdown Mega Menu
// -------------------------------------------------------------
function initCategoriesDropdown() {
  const btn = document.getElementById('categoriesDropdownBtn');
  const menu = document.getElementById('categoriesDropdownMenu');
  if (!btn || !menu) return;

  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    const isExpanded = btn.getAttribute('aria-expanded') === 'true';
    btn.setAttribute('aria-expanded', !isExpanded);
    menu.classList.toggle('active');
  });

  document.addEventListener('click', (e) => {
    if (!btn.contains(e.target) && !menu.contains(e.target)) {
      btn.setAttribute('aria-expanded', 'false');
      menu.classList.remove('active');
    }
  });
}

// -------------------------------------------------------------
// AJAX Add to Cart Buttons
// -------------------------------------------------------------
function initAddToCartButtons() {
  document.querySelectorAll('.btn-ajax-add-cart').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      e.stopPropagation();
      const productId = btn.dataset.productId;
      const qty = btn.dataset.qty ? parseInt(btn.dataset.qty) : 1;

      const origText = btn.innerHTML;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
      btn.disabled = true;

      try {
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('quantity', qty);

        const res = await fetch(getAppUrl('cart.php'), {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: formData
        });
        const data = await res.json();

        if (data.success) {
          updateCartBadge(data.cartCount);
          showToast(data.message || 'Product added to cart!', 'success');
          // Reload drawer contents and pop open
          await reloadCartDrawer();
          openCartDrawer();
        } else {
          showToast(data.message || 'Could not add product.', 'error');
        }
      } catch (err) {
        console.error(err);
        showToast('Network error, please try again.', 'error');
      } finally {
        btn.innerHTML = origText;
        btn.disabled = false;
      }
    });
  });
}

// Product detail page quantity & add button
function initDetailAddToCart() {
  const detailBtn = document.getElementById('btnProductDetailAdd');
  if (!detailBtn) return;

  detailBtn.addEventListener('click', async (e) => {
    e.preventDefault();
    const productId = detailBtn.dataset.productId;
    const qtyInput = document.getElementById('itemDetailQty');
    const qty = qtyInput ? parseInt(qtyInput.value) : 1;

    const origText = detailBtn.innerHTML;
    detailBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    detailBtn.disabled = true;

    try {
      const formData = new FormData();
      formData.append('action', 'add');
      formData.append('product_id', productId);
      formData.append('quantity', qty);

      const res = await fetch(getAppUrl('cart.php'), {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
      });
      const data = await res.json();

      if (data.success) {
        updateCartBadge(data.cartCount);
        showToast(data.message || 'Added to shopping cart!', 'success');
        await reloadCartDrawer();
        openCartDrawer();
      } else {
        showToast(data.message || 'Could not add product.', 'error');
      }
    } catch (err) {
      console.error(err);
      showToast('Error connecting to cart server.', 'error');
    } finally {
      detailBtn.innerHTML = origText;
      detailBtn.disabled = false;
    }
  });
}

function updateCartBadge(count) {
  document.querySelectorAll('.cart-count-badge').forEach(badge => {
    badge.textContent = count;
  });
  const headerCount = document.getElementById('cartDrawerCountHeader');
  if (headerCount) {
    headerCount.textContent = count;
  }
}

async function updateCartItemQty(productId, qty) {
  try {
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('product_id', productId);
    formData.append('quantity', qty);

    const res = await fetch(getAppUrl('cart.php'), {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: formData
    });
    const data = await res.json();

    if (data.success) {
      await reloadCartDrawer();
      if (window.location.pathname.includes('cart.php')) {
        window.location.reload();
      }
    }
  } catch (err) {
    console.error(err);
  }
}

async function reloadCartDrawer() {
  const container = document.getElementById('cartDrawerItemsContainer');
  const footer = document.getElementById('cartDrawerFooter');
  const subtotalEl = document.getElementById('cartDrawerSubtotal');
  const taxEl = document.getElementById('cartDrawerTax');
  const totalEl = document.getElementById('cartDrawerTotal');
  const headerCount = document.getElementById('cartDrawerCountHeader');

  if (!container) return;

  try {
    const res = await fetch(getAppUrl('cart.php?ajax=get_drawer'));
    const json = await res.json();

    if (json.html) {
      container.innerHTML = json.html;
    }

    if (footer) {
      footer.style.display = json.has_items ? 'flex' : 'none';
    }

    if (subtotalEl && json.subtotal) {
      subtotalEl.textContent = '₹' + json.subtotal;
    }
    if (taxEl && json.tax_amount) {
      taxEl.textContent = '₹' + json.tax_amount;
    }
    if (totalEl && json.total_amount) {
      totalEl.textContent = '₹' + json.total_amount;
    }
    if (headerCount) {
      headerCount.textContent = json.count || 0;
    }

    updateCartBadge(json.count || 0);
  } catch (e) {
    console.error("reloadCartDrawer error:", e);
  }
}

// -------------------------------------------------------------
// Checkout Payment Method Toggle
// -------------------------------------------------------------
function initCheckoutPaymentToggle() {
  const paymentInputs = document.querySelectorAll('input[name="payment_method"]');
  const upiDetails = document.getElementById('upiPaymentDetails');
  const bankDetails = document.getElementById('bankPaymentDetails');

  paymentInputs.forEach(input => {
    input.addEventListener('change', () => {
      document.querySelectorAll('.payment-option-card').forEach(c => c.classList.remove('selected'));
      input.closest('.payment-option-card').classList.add('selected');

      if (upiDetails) upiDetails.style.display = (input.value === 'upi_qr') ? 'block' : 'none';
      if (bankDetails) bankDetails.style.display = (input.value === 'bank_transfer') ? 'block' : 'none';
    });
  });
}

// Toast Notifications
function showToast(msg, type = 'success') {
  let toast = document.getElementById('siToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'siToast';
    toast.style.position = 'fixed';
    toast.style.bottom = '25px';
    toast.style.right = '25px';
    toast.style.zIndex = '9999';
    toast.style.padding = '14px 22px';
    toast.style.borderRadius = '10px';
    toast.style.fontWeight = '700';
    toast.style.fontSize = '0.9rem';
    toast.style.boxShadow = '0 10px 25px rgba(0,0,0,0.15)';
    toast.style.transition = 'all 0.3s ease';
    toast.style.display = 'flex';
    toast.style.alignItems = 'center';
    toast.style.gap = '10px';
    document.body.appendChild(toast);
  }

  toast.style.background = (type === 'success') ? '#059669' : '#dc2626';
  toast.style.color = '#ffffff';
  toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${msg}`;
  toast.style.opacity = '1';
  toast.style.transform = 'translateY(0)';

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(15px)';
  }, 3500);
}
