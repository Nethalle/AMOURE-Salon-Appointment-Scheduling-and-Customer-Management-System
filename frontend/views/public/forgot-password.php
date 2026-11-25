<?php /** @var array $flashes */ ?>
<div class="auth-card-split">
    <?php
    $panelTitle  = 'It happens to';
    $panelScript = 'everyone.';
    $panelText   = 'Enter your email and we\'ll send you a one-time code to set a new password.';
    require __DIR__ . '/_auth-panel.php';
    ?>

    <div class="auth-form-panel">
        <p class="eyebrow eyebrow-gold">Account help</p>
        <h1 class="auth-title">Forgot Password?</h1>
        <p class="muted">Type the email you log in with. We'll email you a 6-digit code.</p>

        <?php require __DIR__ . '/_auth-flashes.php'; ?>

        <form method="post" action="<?= e(url('forgot-password')) ?>" class="form" novalidate>
            <?= csrf_field() ?>
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" value="<?= old('email') ?>" placeholder="Enter your email address" required autocomplete="email" autofocus>
            <button type="submit" class="btn btn-dark btn-block">Send reset code <?= icon('arrow', 'icon icon-sm') ?></button>
        </form>

        <p class="center" style="margin-top:18px"><a href="<?= e(url('login')) ?>" class="strong-link">← Back to sign in</a></p>
    </div>
</div>
