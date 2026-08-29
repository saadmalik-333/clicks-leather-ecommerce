<?php
/**
 * Shipping Info Content Partial
 * Used by shipping-info.php, return-policy.php, warranty.php for cross-page accordion
 * Note: This partial requires $shipping_is_free and $shipping_flat_cost variables to be set
 */
?>
<div class="shipping-note">
    <h3><?= $shipping_is_free === 'yes' ? 'Free Shipping' : 'Shipping' ?> Offer</h3>
    <p><?= $shipping_is_free === 'yes' ? 'We are currently offering free shipping on all orders. This is a limited-time offer and subject to change without notice.' : 'Standard shipping is ' . format_price($shipping_flat_cost) . ' per order, calculated at checkout.' ?></p>
</div>

<div class="shipping-timeline">
    <h2>Delivery Timeline</h2>
                
    <div class="timeline-wrapper">
        <div class="timeline-step">
            <div class="timeline-number">1</div>
            <div class="timeline-content">
                <h3>Order Placed</h3>
                <p>Your order is confirmed and production begins.</p>
            </div>
        </div>

        <div class="timeline-step">
            <div class="timeline-number">2</div>
            <div class="timeline-content">
                <h3>Manufacturing</h3>
                <p>4-6 days — Your item is handcrafted to order.</p>
            </div>
        </div>

        <div class="timeline-step">
            <div class="timeline-number">3</div>
            <div class="timeline-content">
                <h3>International Shipping</h3>
                <p>8-10 days — Your item ships to your location.</p>
            </div>
        </div>

        <div class="timeline-step">
            <div class="timeline-number">4</div>
            <div class="timeline-content">
                <h3>Delivered</h3>
                <p>Total delivery time: 14-15 days.</p>
            </div>
        </div>
    </div>
</div>
