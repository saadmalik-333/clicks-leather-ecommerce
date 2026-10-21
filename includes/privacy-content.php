<?php
/**
 * Privacy Policy Content Partial
 * Used by privacy-policy.php and other policy pages for cross-page accordion
 */
$legal_name      = '[Clicks Leather]';
$support_email   = '[SUPPORT EMAIL]';
$business_address = '';   // leave empty; if empty, do not show the address line
$last_updated    = '[EFFECTIVE DATE]';
?>
<div class="policy-section standard">
    <h2>Privacy Policy</h2>
    <p>Last updated: <?= htmlspecialchars($last_updated) ?></p>
</div>

<div class="policy-section standard">
    <h2>1. Who we are</h2>
    <p>This policy explains how <?= htmlspecialchars($legal_name) ?> ("Clicks Leather", "we", "us") collects and uses your personal information when you use our website. Contact: <?= htmlspecialchars($support_email) ?>.</p>
</div>

<div class="policy-section standard">
    <h2>2. Information we collect</h2>
    <ul>
        <li><strong>Account information:</strong> your name, email address and password when you create an account. Passwords are stored in hashed form. If you choose "Sign in with Google", we receive and save your name, email address and Google account ID.</li>
        <li><strong>Order information:</strong> the details you enter at checkout (full name, email, phone number, and shipping address including address lines, city, state, country and postal code), the items you order, and your order history.</li>
        <li><strong>Messages:</strong> what you send through our contact form (name, email, optional phone number, type of enquiry and message).</li>
        <li><strong>Reviews:</strong> if you write a review, your name (as saved on your account), your rating, your review text and the date are shown publicly on the product page.</li>
        <li><strong>Cart:</strong> the items in your cart are saved in our database, linked to your account or your browser session.</li>
        <li><strong>Technical information:</strong> a session cookie that keeps you signed in. Sessions end after 30 minutes of inactivity.</li>
        <li><strong>Payment information:</strong> card details are entered on our payment provider's secure page. We do not store your full card number or security code.</li>
    </ul>
</div>

<div class="policy-section standard">
    <h2>3. How we use your information</h2>
    <p>To create and manage your account; to process, ship and support your orders; to reply to your messages; to prevent fraud and keep the website secure; to follow the law; and to improve the website.</p>
</div>

<div class="policy-section standard">
    <h2>4. Who we share it with</h2>
    <p>We share information only when needed to run our business:</p>
    <ul>
        <li>Our payment provider (Safepay), to process your payment</li>
        <li>Delivery and courier companies, to deliver your order</li>
        <li>Google, if you use "Sign in with Google"</li>
        <li>Our website hosting provider (Hostinger), which stores our site and database</li>
        <li>Authorities or other parties when the law requires it</li>
    </ul>
    <p>Some content on our pages is loaded from third-party servers: Google Fonts (on all pages) and a phone-number input library hosted on jsDelivr (on the checkout page). When your browser loads these, the provider can see your IP address and browser details.</p>
    <p>We do not sell your personal information.</p>
</div>

<div class="policy-section standard">
    <h2>5. Cookies</h2>
    <p>We use one necessary session cookie to keep you signed in and to keep your cart working. We do not use advertising or analytics cookies. You can block cookies in your browser, but you will not be able to sign in or buy.</p>
</div>

<div class="policy-section standard">
    <h2>6. How long we keep information</h2>
    <p>We keep your account and order information while your account is active and for as long as needed for orders, returns, warranty, accounting and legal requirements.</p>
</div>

<div class="policy-section warning">
    <h2>7. Security</h2>
    <p>We use reasonable technical measures to protect your information, including storing passwords in hashed form and using a secure (HTTPS) connection. No online system is completely secure, so we cannot guarantee absolute security.</p>
</div>

<div class="policy-section positive">
    <h2>8. Your choices</h2>
    <p>You can email us at <?= htmlspecialchars($support_email) ?> to ask to see, correct or delete the personal information we hold about you. We handle these requests manually, and we may need to keep some records that the law requires us to keep.</p>
</div>

<div class="policy-section standard">
    <h2>9. Children</h2>
    <p>Our website is not intended for people under 18, and we do not knowingly collect their information.</p>
</div>

<div class="policy-section standard">
    <h2>10. Changes</h2>
    <p>We may update this policy. The latest version is always on this page with its "Last updated" date.</p>
</div>

<div class="policy-section standard">
    <h2>11. Contact</h2>
    <p><?= htmlspecialchars($legal_name) ?><?php if (!empty($business_address)): ?><br><?= htmlspecialchars($business_address) ?><?php endif; ?></p>
    <p>Email: <?= htmlspecialchars($support_email) ?></p>
</div>
