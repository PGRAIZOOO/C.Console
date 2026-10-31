</main>
<?php if (($area ?? 'store') === 'store'): ?>
<?php $signedIn = isset($user) && $user; ?>
<footer class="site-footer">
    <div class="wrap">
        <div class="footer-grid">
            <div class="footer-brand">
                <a class="brand" href="<?= e(url('index.php')) ?>"><img class="logo" src="<?= e(url('images/logo.png')) ?>" alt=""> <?= e(SHOP_NAME) ?></a>
                <p class="footer-tagline">A PlayStation 5 game shop for gamers who like a focused catalogue, clear prices and honest stock levels.</p>
                <div class="footer-icons">
                    <a href="<?= e(url('contact.php')) ?>" aria-label="Contact us" title="Contact us">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                    </a>
                    <a href="<?= e(url('faq.php')) ?>" aria-label="Frequently asked questions" title="FAQ">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.6 2.2c-.7.4-1.1.9-1.1 1.8"/><path d="M12 17h.01"/></svg>
                    </a>
                    <a href="<?= e(url('shop.php')) ?>" aria-label="Browse games" title="Browse games">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 4h12l1.5 4H4.5z"/><path d="M5 8v11a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8"/><path d="M9 12a3 3 0 0 0 6 0"/></svg>
                    </a>
                </div>
            </div>

            <nav class="footer-col" aria-label="<?= e(SHOP_NAME) ?>">
                <h4><?= e(SHOP_NAME) ?></h4>
                <a href="<?= e(url('about.php')) ?>">About Us</a>
                <a href="<?= e(url('contact.php')) ?>">Contact Us</a>
                <a href="<?= e(url('faq.php')) ?>">FAQ</a>
                <span class="footer-info"><?= e(CONTACT_ADDRESS) ?></span>
                <span class="footer-info"><?= e(CONTACT_HOURS) ?></span>
            </nav>

            <nav class="footer-col" aria-label="Ways to shop">
                <h4>Ways to Shop</h4>
                <a href="<?= e(url('index.php')) ?>">Home</a>
                <a href="<?= e(url('shop.php')) ?>">All PS5 Games</a>
                <a href="<?= e(url('shop.php?stock=in')) ?>">In Stock Now</a>
                <a href="<?= e(url('shop.php?sort=new')) ?>">New Arrivals</a>
                <?php if (!is_admin()): ?>
                    <a href="<?= e(url('view_cart.php')) ?>">My Cart</a>
                <?php endif; ?>
            </nav>

            <nav class="footer-col" aria-label="My account">
                <h4>My Account</h4>
                <?php if ($signedIn): ?>
                    <a href="<?= e(url('user/profile.php')) ?>">My Profile</a>
                    <?php if (is_admin()): ?>
                        <a href="<?= e(url('admin/index.php')) ?>">Admin Dashboard</a>
                    <?php else: ?>
                        <a href="<?= e(url('user/myorders.php')) ?>">Orders &amp; Status</a>
                    <?php endif; ?>
                    <a href="<?= e(url('user/logout.php')) ?>">Sign Out</a>
                <?php else: ?>
                    <a href="<?= e(url('user/login.php')) ?>">Sign In</a>
                    <a href="<?= e(url('user/register.php')) ?>">Create an Account</a>
                <?php endif; ?>
            </nav>

            <nav class="footer-col" aria-label="Help and legal">
                <h4>Help &amp; Legal</h4>
                <a href="<?= e(url('faq.php')) ?>">Help &amp; FAQs</a>
                <a href="<?= e(url('privacy.php')) ?>">Privacy Policy</a>
                <a href="<?= e(url('terms.php')) ?>">Terms of Service</a>
                <a href="<?= e(url('disclaimer.php')) ?>">Disclaimer</a>
            </nav>
        </div>

        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> <?= e(SHOP_NAME) ?>. All rights reserved.</span>
            <span>PlayStation 5 games only. Game titles and trademarks belong to their respective owners.</span>
        </div>
    </div>
</footer>
<?php endif; ?>
</body>
</html>
