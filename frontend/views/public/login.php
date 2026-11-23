<?php /** @var array $flashes */ ?>
<div class="auth-card-split">
    <?php
    $panelTitle  = 'Beauty begins with';
    $panelScript = 'you.';
    $panelText   = 'Personalized beauty services and easy appointment booking, all in one elegant place.';
    require __DIR__ . '/_auth-panel.php';
    ?>

    <div class="auth-form-panel">
        <p class="eyebrow eyebrow-gold">Your salon experience</p>
        <h1 class="auth-title">Welcome Back</h1>
        <p class="muted">Please enter your account details to continue.</p>

        <?php require __DIR__ . '/_auth-flashes.php'; ?>

        <form method="post" action="<?= e(url('login')) ?>" class="form" novalidate>
            <?= csrf_field() ?>

            <label for="email">Email address</label>
            <input type="email" id="email" name="email" value="<?= old('email') ?>" placeholder="Enter your email address" required autocomplete="email" autofocus>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">

            <button type="submit" class="btn btn-dark btn-block">Sign in <?= icon('arrow', 'icon icon-sm') ?></button>
        </form>

        <p class="divider-text"><span>New to AMOURÉ SALON?</span></p>
        <p class="center">Don't have an account? <a href="<?= e(url('register')) ?>" class="strong-link">Create an account</a></p>
        <p class="center"><a href="<?= e(url('home')) ?>" class="muted-link">← Back to home</a></p>

        <p class="auth-copy">&copy; <?= date('Y') ?> AMOURÉ SALON. All rights reserved.</p>
    </div>
</div>
