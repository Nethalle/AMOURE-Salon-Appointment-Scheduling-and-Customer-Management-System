<?php /** @var string $panelTitle */ /** @var string $panelScript */ /** @var string $panelText */ ?>
<div class="auth-brand-panel">
    <a class="brand brand-light" href="<?= e(url('home')) ?>">
        <span class="brand-mark" aria-hidden="true">A</span>
        <span class="brand-text">AMOURÉ <small>SALON</small></span>
    </a>

    <div class="auth-brand-copy">
        <p class="eyebrow eyebrow-gold">Welcome to AMOURÉ SALON</p>
        <h2 class="auth-headline"><?= e($panelTitle) ?> <em><?= e($panelScript) ?></em></h2>
        <p><?= e($panelText) ?></p>
    </div>

    <p class="eyebrow eyebrow-gold auth-tagline">Beauty. Elegance. Confidence.</p>
    <span class="auth-rings" aria-hidden="true"></span>
</div>
