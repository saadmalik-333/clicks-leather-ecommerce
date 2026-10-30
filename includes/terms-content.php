<?php
/**
 * Terms & Conditions Content Partial
 * Used by terms-and-conditions.php and other policy pages for cross-page accordion
 */
$legal_name      = 'Clicks Leather';
$support_email   = 'clicksleather@gmail.com';
$business_address = '';   // leave empty; if empty, do not show the address line
$last_updated    = 'October 30, 2026';
?>
<div class="policy-section standard">
    <h2>Terms & Conditions</h2>
    <p>Last updated: <?= htmlspecialchars($last_updated) ?></p>
</div>

<div class="policy-section standard">
    <h2>1. About these terms</h2>
    <p>These Terms & Conditions apply to your use of the Clicks Leather website and to any order you place with us. Clicks Leather is operated by <?= htmlspecialchars($legal_name) ?> ("we", "us"). By using the website or placing an order you agree to these terms. If you do not agree, please do not use the website.</p>
</div>

<div class="policy-section standard">
    <h2>2. Eligibility and your account</h2>
    <p>You must be at least 18 years old to place an order, and you need an account to buy from us. You are responsible for the information you give us, for keeping your account password confidential, and for activity under your account. Please tell us at <?= htmlspecialchars($support_email) ?> if you think someone else has used your account.</p>
</div>

<div class="policy-section standard">
    <h2>3. Products and descriptions</h2>
    <p>We try to describe and photograph our leather products accurately. Leather is a natural material, so grain, texture and shade can vary slightly from item to item, and colors may look different on different screens. Small natural variations are not defects.</p>
</div>

<div class="policy-section standard">
    <h2>4. Prices and shipping fees</h2>
    <p>All prices are shown in US dollars (USD) and may change without notice. The price you pay is the price shown when you place your order. Any shipping fee is shown at checkout; we may offer free shipping for a limited time, as described on our Shipping Info page. Orders shipped internationally may be subject to customs duties, import taxes or handling fees charged by your country. These charges are not included in our prices and are the customer's responsibility.</p>
</div>

<div class="policy-section standard">
    <h2>5. Orders and acceptance</h2>
    <p>Placing an order is an offer to buy. Your order is confirmed when we receive your payment. We may cancel or refuse an order, for example if an item is unavailable, if there is a pricing or description error, or if we suspect fraud. If we cancel an order after payment, we will refund you in full.</p>
</div>

<div class="policy-section standard">
    <h2>6. Payment</h2>
    <p>Card payments are processed by a third-party payment provider (Safepay) over a secure, 3-D Secure connection. You enter your card details on the payment provider's secure page, and we do not store your full card details. By paying you confirm that you are authorized to use the payment method.</p>
</div>

<div class="policy-section warning">
    <h2>7. Made-to-order and custom-size items</h2>
    <p>Each piece is made to order. Once production has started, we cannot accept returns for change of mind. If you choose a custom size, you are responsible for giving accurate measurements. If your item does not fit, we offer a free replacement for fit issues, as described in our Return Policy.</p>
</div>

<div class="policy-section standard">
    <h2>8. Shipping and delivery</h2>
    <p>Shipping fees and delivery timelines are described on our Shipping Info page, which forms part of these terms. Delivery times are estimates and are not guaranteed.</p>
</div>

<div class="policy-section positive">
    <h2>9. Returns, refunds and warranty</h2>
    <p>Returns and exchanges are accepted within 14 days of delivery for damaged items or incorrect sizing, and our products carry a one-year warranty against manufacturing defects. Full details are in our Return Policy and Warranty pages, which form part of these terms. If anything on this page conflicts with those pages, the Return Policy and Warranty pages apply to returns and warranty matters.</p>
</div>

<div class="policy-section standard">
    <h2>10. Reviews and content you submit</h2>
    <p>If you submit a review or message, you confirm that it is honest and that it does not break the law or anyone's rights. Reviews are shown publicly with your name. We may edit or remove reviews that are abusive, false or off-topic.</p>
</div>

<div class="policy-section warning">
    <h2>11. Acceptable use</h2>
    <p>You agree not to misuse the website, including by trying to gain unauthorized access, interfering with its operation, using it for fraud, or copying its content for commercial use without our permission.</p>
</div>

<div class="policy-section warning">
    <h2>12. Intellectual property</h2>
    <p>The Clicks Leather name, logo, photographs, text and design are ours or used with permission, and you may not use them without our written permission.</p>
</div>

<div class="policy-section warning">
    <h2>13. Our responsibility</h2>
    <p>We are responsible for supplying products that match their description. To the fullest extent allowed by law, we are not liable for indirect or consequential losses, or for delays or failures caused by events beyond our reasonable control (such as courier delays, strikes, or natural events). Nothing in these terms limits any right you have under applicable consumer law.</p>
</div>

<div class="policy-section standard">
    <h2>14. Changes</h2>
    <p>We may update these terms from time to time. The version on this page when you place an order is the one that applies to that order.</p>
</div>

<div class="policy-section standard">
    <h2>15. Governing law</h2>
    <p>These terms are governed by the laws of Pakistan. Any dispute will first be handled through our customer service at <?= htmlspecialchars($support_email) ?>; if it cannot be resolved, it will be subject to the courts of Pakistan.</p>
</div>

<div class="policy-section standard">
    <h2>16. Contact us</h2>
    <p><?= htmlspecialchars($legal_name) ?><?php if (!empty($business_address)): ?><br><?= htmlspecialchars($business_address) ?><?php endif; ?></p>
    <p>Email: <?= htmlspecialchars($support_email) ?></p>
</div>
