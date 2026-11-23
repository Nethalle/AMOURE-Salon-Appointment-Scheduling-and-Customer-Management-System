<?php /** @var bool $isApp */ /** @var bool $isAuth */ ?>
</main>

<?php if ($isApp): ?>
</div><!-- /.shell -->
<?php elseif (!$isAuth):
    try {
        $footerServices = db()->query('SELECT name FROM services WHERE is_active = 1 ORDER BY name LIMIT 6')->fetchAll();
    } catch (Throwable $e) {
        $footerServices = []; // database down: still show the rest of the footer
    }
    $info = [
        'phone'   => setting('salon_phone'),
        'email'   => setting('salon_email'),
        'address' => setting('salon_address'),
    ];
    $social = array_filter([
        'instagram' => setting('instagram_url'),
        'facebook'  => setting('facebook_url'),
        'tiktok'    => setting('tiktok_url'),
    ]);
?>
<footer class="site-footer" id="contact">
    <div class="footer-grid">
        <div class="footer-col footer-about">
            <p class="footer-brand">AMOURÉ <span>SALON</span></p>
            <p>Personalized beauty services and easy online booking, all in one elegant place.</p>
            <?php if ($social): ?>
                <div class="footer-social">
                    <?php foreach ($social as $name => $link): ?>
                        <a href="<?= e($link) ?>" target="_blank" rel="noopener" aria-label="<?= e(ucfirst($name)) ?>"><?= icon($name) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer-col">
            <h2>Quick links</h2>
            <a href="<?= e(url('home')) ?>">Home</a>
            <a href="<?= e(url('home')) ?>#services">Services</a>
            <a href="<?= e(url('home')) ?>#about">About</a>
            <a href="<?= e(url('home')) ?>#reviews">Reviews</a>
            <a href="<?= e(url('login')) ?>">Log in</a>
        </div>

        <div class="footer-col">
            <h2>Our services</h2>
            <?php foreach ($footerServices as $s): ?>
                <a href="<?= e(url('home')) ?>#services"><?= e($s['name']) ?></a>
            <?php endforeach; ?>
        </div>

        <div class="footer-col">
            <h2>Contact us</h2>
            <?php if ($info['address'] !== ''): ?><p><?= icon('pin', 'icon icon-sm') ?> <?= e($info['address']) ?></p><?php endif; ?>
            <?php if ($info['phone'] !== ''): ?><p><?= icon('phone', 'icon icon-sm') ?> <?= e($info['phone']) ?></p><?php endif; ?>
            <?php if ($info['email'] !== ''): ?><p><?= icon('mail', 'icon icon-sm') ?> <a href="mailto:<?= e($info['email']) ?>"><?= e($info['email']) ?></a></p><?php endif; ?>
            <p><?= icon('clock', 'icon icon-sm') ?> Open daily, <?= e(fmt_time(setting('opening_time'))) ?> – <?= e(fmt_time(setting('closing_time'))) ?></p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> AMOURÉ SALON. All rights reserved.</p>
    </div>
</footer>
<?php endif; ?>

<script src="frontend/assets/js/app.js"></script>
</body>
</html>
