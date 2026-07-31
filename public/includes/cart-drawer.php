<!-- Cart Drawer -->
<div class="cart-drawer-overlay" id="cart-drawer-overlay"></div>
<div class="cart-drawer" id="cart-drawer">
    <div class="cart-drawer-header">
        <h2 class="cart-drawer-title">
            <span class="cart-title-text">Your Cart</span>
            <span id="cart-item-count" class="cart-title-count">(0 items)</span>
        </h2>
        <button class="cart-drawer-close" id="cart-drawer-close" aria-label="Close cart">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
    
    <div class="cart-drawer-body" id="cart-drawer-body">
        <!-- Cart items will be loaded here via JavaScript -->
        <div class="cart-loading">Loading cart...</div>
    </div>
    
    <div class="cart-drawer-footer" id="cart-drawer-footer">
        <div class="cart-subtotal">
            <span class="cart-subtotal-label">Subtotal</span>
            <span class="cart-subtotal-amount" id="cart-subtotal-amount">$0.00</span>
        </div>
        <a href="<?= PUBLIC_URL ?>/checkout.php" class="btn btn-primary btn-full checkout-btn">Checkout</a>
    </div>
</div>
