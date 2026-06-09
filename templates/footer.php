<?php
/**
 * Footer Template
 */
$theme = $theme ?? get_theme();
$siteName = $theme['site_name'] ?? DEFAULT_SITE_NAME;
$year = date('Y');
?>
<footer class="site-footer">
    <div class="footer-grid">
        <div class="footer-col">
            <h4>About</h4>
            <ul>
                <li><a href="/">Contact Us</a></li>
                <li><a href="/">About Us</a></li>
                <li><a href="/">Careers</a></li>
                <li><a href="/">Press</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Help</h4>
            <ul>
                <li><a href="/">Payments</a></li>
                <li><a href="/">Shipping</a></li>
                <li><a href="/">Returns</a></li>
                <li><a href="/">FAQ</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Policy</h4>
            <ul>
                <li><a href="/">Return Policy</a></li>
                <li><a href="/">Terms of Use</a></li>
                <li><a href="/">Privacy</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Stay Connected</h4>
            <p>Get exclusive deals and updates delivered to your inbox.</p>
        </div>
    </div>
    <div class="footer-bottom">
        <span>&copy; <?= $year ?> <?= e($siteName) ?>. All rights reserved.</span>
        <span>Built on a fully dynamic, admin-controlled platform.</span>
    </div>
</footer>
</body>
</html>
