<?php /** @var array $flashes */ ?>
<div class="auth-card-split">
    <?php
    $panelTitle  = 'Welcome to your';
    $panelScript = 'salon.';
    $panelText   = 'This one-time setup creates the owner\'s admin account. After that, this page closes and everyone logs in normally.';
    require __DIR__ . '/_auth-panel.php';
    ?>

    <div class="auth-form-panel">
        <p class="eyebrow eyebrow-gold">First-time setup</p>
        <h1 class="auth-title">Create the Admin Account</h1>
        <p class="muted">You'll use this to manage bookings, staff, services and settings.</p>

        <?php require __DIR__ . '/_auth-flashes.php'; ?>

        <form method="post" action="<?= e(url('setup')) ?>" class="form" novalidate>
            <?= csrf_field() ?>

            <label for="name">Your name</label>
            <input type="text" id="name" name="name" value="<?= old('name') ?>" placeholder="e.g. Salon Owner" required maxlength="100" autocomplete="name" autofocus>

            <label for="email">Email address (your login)</label>
            <input type="email" id="email" name="email" value="<?= old('email') ?>" placeholder="you@example.com" required maxlength="150" autocomplete="email">

            <div class="form-two">
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="At least 10 characters" required minlength="10" autocomplete="new-password">
                </div>
                <div class="field">
                    <label for="password_confirm">Confirm password</label>
                    <input type="password" id="password_confirm" name="password_confirm" placeholder="Type it again" required minlength="10" autocomplete="new-password">
                </div>
            </div>
            <small class="hint">Use letters and numbers. Don't reuse a password from another site.</small>

            <button type="submit" class="btn btn-dark btn-block">Create admin account <?= icon('arrow', 'icon icon-sm') ?></button>
        </form>
    </div>
</div>
