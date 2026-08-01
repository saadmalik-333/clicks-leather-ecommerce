// Cart Drawer JavaScript
(function() {
    const cartDrawer = document.getElementById('cart-drawer');
    const cartDrawerOverlay = document.getElementById('cart-drawer-overlay');
    const cartDrawerClose = document.getElementById('cart-drawer-close');
    const cartDrawerBody = document.getElementById('cart-drawer-body');
    const cartDrawerFooter = document.getElementById('cart-drawer-footer');
    const cartSubtotalAmount = document.getElementById('cart-subtotal-amount');
    const cartCountElements = document.querySelectorAll('.cart-count');
    const cartLinks = document.querySelectorAll('.cart-link');

    // Open cart drawer
    function openCartDrawer() {
        cartDrawer.classList.add('active');
        cartDrawerOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        loadCart();
    }

    // Close cart drawer
    function closeCartDrawer() {
        cartDrawer.classList.remove('active');
        cartDrawerOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Load cart data
    async function loadCart() {
        cartDrawerBody.innerHTML = '<div class="cart-loading">Loading cart...</div>';
        
        try {
            const response = await fetch('cart-get.php');
            const data = await response.json();
            
            if (data.success) {
                renderCartItems(data.items);
                updateCartCount(data.cart_count);
                updateCartItemCount(data.cart_count);
                updateSubtotal(data.subtotal_formatted);
            } else {
                cartDrawerBody.innerHTML = '<div class="cart-empty">Error loading cart</div>';
            }
        } catch (error) {
            console.error('Error loading cart:', error);
            cartDrawerBody.innerHTML = '<div class="cart-empty">Error loading cart</div>';
        }
    }

    // Render cart items
    function renderCartItems(items) {
        if (items.length === 0) {
            cartDrawerBody.innerHTML = `
                <div class="cart-empty">
                    <p>Your cart is empty</p>
                    <a href="index.php" class="btn btn-outline">Continue Shopping</a>
                </div>
            `;
            return;
        }

        let html = '';
        items.forEach(item => {
            const variantInfo = [];
            if (item.color) variantInfo.push(`<span>Color:</span> ${item.color}`);
            if (item.size) variantInfo.push(`<span>Size:</span> ${item.size}`);
            
            // Price display with discount if applicable
            let priceDisplay = '';
            if (item.discounted_price && item.discounted_price < item.price) {
                priceDisplay = `
                    <div class="cart-item-price-container">
                        <span class="cart-item-price-original" style="text-decoration: line-through; color: #999; font-size: 0.85rem;">${item.price_formatted}</span>
                        <span class="cart-item-price-discounted" style="color: var(--color-primary, #e63946); font-weight: 600;">${item.discounted_price_formatted}</span>
                        <span class="cart-item-discount-badge" style="background: #8B7355; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500; margin-left: 0.5rem; white-space: nowrap;">${Math.round(item.discount_percent)}% OFF</span>
                    </div>
                `;
            } else {
                priceDisplay = `<div class="cart-item-price">${item.price_formatted}</div>`;
            }
            
            html += `
                <div class="cart-item" data-cart-item-id="${item.cart_item_id}">
                    <img src="${item.image_url}" alt="${item.product_name}" class="cart-item-image">
                    <div class="cart-item-details">
                        <div class="cart-item-name">${item.product_name}</div>
                        ${variantInfo.length > 0 ? `<div class="cart-item-variant">${variantInfo.join(' • ')}</div>` : ''}
                        <div class="cart-item-bottom">
                            <div class="cart-item-quantity">
                                <button class="cart-quantity-btn" onclick="updateQuantity(${item.cart_item_id}, ${item.quantity - 1})">-</button>
                                <span class="cart-quantity-value">${item.quantity}</span>
                                <button class="cart-quantity-btn" onclick="updateQuantity(${item.cart_item_id}, ${item.quantity + 1})">+</button>
                            </div>
                            ${priceDisplay}
                            <button class="cart-item-remove" onclick="removeCartItem(${item.cart_item_id})" aria-label="Remove item">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });

        cartDrawerBody.innerHTML = html;
    }

    // Update cart count badge
    function updateCartCount(count) {
        cartCountElements.forEach(el => {
            el.textContent = count;
            el.classList.toggle('hidden', count <= 0);
        });
    }

    // Update cart drawer title with item count
    function updateCartItemCount(count) {
        const cartItemCountEl = document.getElementById('cart-item-count');
        if (cartItemCountEl) {
            cartItemCountEl.textContent = `(${count} item${count !== 1 ? 's' : ''})`;
        }
    }

    // Update subtotal
    function updateSubtotal(amount) {
        if (cartSubtotalAmount) {
            cartSubtotalAmount.textContent = amount;
        }
    }

    // Update quantity
    async function updateQuantity(cartItemId, newQuantity) {
        if (newQuantity < 0) return;
        
        try {
            const response = await fetch('cart-update.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cart_item_id: cartItemId,
                    quantity: newQuantity
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                updateCartCount(data.cart_count);
                updateCartItemCount(data.cart_count);
                loadCart(); // Reload cart to show updated items
            } else {
                alert(data.message || 'Error updating quantity');
            }
        } catch (error) {
            console.error('Error updating quantity:', error);
        }
    }

    // Remove cart item
    async function removeCartItem(cartItemId) {
        try {
            const response = await fetch('cart-remove.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cart_item_id: cartItemId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                updateCartCount(data.cart_count);
                updateCartItemCount(data.cart_count);
                loadCart(); // Reload cart to show updated items
            } else {
                alert(data.message || 'Error removing item');
            }
        } catch (error) {
            console.error('Error removing item:', error);
        }
    }

    // Add to cart (for product detail page)
    async function addToCart(productId, color, size, quantity = 1) {
        try {
            const response = await fetch('cart-add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    color: color,
                    size: size,
                    quantity: quantity
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                updateCartCount(data.cart_count);
                updateCartItemCount(data.cart_count);
                openCartDrawer();
                return { success: true };
            } else {
                return { success: false, message: data.message };
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
            return { success: false, message: 'Error adding to cart' };
        }
    }

    // Event listeners
    if (cartDrawerClose) {
        cartDrawerClose.addEventListener('click', closeCartDrawer);
    }

    if (cartDrawerOverlay) {
        cartDrawerOverlay.addEventListener('click', closeCartDrawer);
    }

    cartLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            openCartDrawer();
        });
    });

    // Close on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && cartDrawer.classList.contains('active')) {
            closeCartDrawer();
        }
    });

    // Make functions globally accessible
    window.updateQuantity = updateQuantity;
    window.removeCartItem = removeCartItem;
    window.addToCart = addToCart;
    window.openCartDrawer = openCartDrawer;
    window.closeCartDrawer = closeCartDrawer;

    // Initialize cart count on page load
    document.addEventListener('DOMContentLoaded', () => {
        loadCart();
    });
})();
