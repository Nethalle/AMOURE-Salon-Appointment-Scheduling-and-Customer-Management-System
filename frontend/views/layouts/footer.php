<?php /** @var bool $isApp */ ?>
</main>
<?php if ($isApp): ?>
</div><!-- /.shell -->
<?php else: ?>
<footer class="site-footer">
    <p class="footer-brand">AMOURÉ <span>SALON</span></p>
    <p>&copy; <?= date('Y') ?> AMOURÉ SALON. All rights reserved.</p>
</footer>
<?php endif; ?>

<script src="frontend/assets/js/app.js"></script>
</body>
</html>
