<?php
/**
 * Clicks Leather — Shared Footer Include
 * Contains footer with shop links, company info, support links, and newsletter
 */
?>
<!-- Footer -->
<footer class="site-footer">
    <div class="footer-grid">
        <div class="footer-col">
            <a href="<?= PUBLIC_URL ?>/index.php" class="footer-logo-link">
                <img src="<?= PUBLIC_URL ?>/img/logo/clicks_leather_logo_dark_transparent.png" alt="Clicks Leather" class="footer-logo-img">
            </a>
            <p>Premium handcrafted leather goods.</p>
        </div>
        <div class="footer-col">
            <h4>Shop</h4>
            <ul>
                <li><a href="#">Wallets</a></li>
                <li><a href="#">Bags</a></li>
                <li><a href="#">Accessories</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Company</h4>
            <ul>
                <li><a href="#">About Us</a></li>
                <li><a href="#">Contact</a></li>
                <li><a href="#">Journal</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Support</h4>
            <ul>
                <li><a href="<?= PUBLIC_URL ?>/faq.php">FAQ</a></li>
                <li><a href="#">Shipping & Returns</a></li>
                <li><a href="#">Warranty</a></li>
            </ul>
        </div>
        <div class="footer-col newsletter-col">
            <h4>Newsletter</h4>
            <p>Join our list for 10% off your first order.</p>
            <form class="newsletter-form">
                <input type="email" placeholder="Your email address" required>
                <button type="submit" class="btn btn-primary btn-sm">Subscribe</button>
            </form>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> Clicks Leather. All rights reserved. Handcrafted with ❤️</p>
    </div>
</footer>
