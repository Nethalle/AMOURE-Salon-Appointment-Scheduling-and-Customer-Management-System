<?php /** @var array $flashes  @var string $email */ ?>
<div class="auth-card-split">
    <?php
    $panelTitle  = 'A fresh';
    $panelScript = 'start.';
    $panelText   = 'Enter the code from your email and choose a new password. The code works once and expires in 15 minutes.';
    require __DIR__ . '/_auth-panel.php';
    ?>

    <div class="auth-form-panel">
        <p class="eyebrow eyebrow-gold">Account help</p>
        <h1 class="auth-title">Set a New Password</h1>
        <p class="muted">Code sent to <strong><?= e($email) ?></strong> (if it has an account).</p>

        <?php require __DIR__ . '/_auth-flashes.php'; ?>

        <form method="post" action="<?= e(url('reset-password')) ?>" class="form" novalidate>
            <?= csrf_field() ?>
            <label for="code">6-digit code</label>
            <input type="text" id="code" name="code" class="code-input" inputmode="numeric" pattern="[0-9 ]*" maxlength="7"
                   placeholder="••••••" autocomplete="one-time-code" required autofocus>

            <div class="form-two">
                <div class="field">
                    <label for="password">New password</label>
                    <input type="password" id="password" name="password" placeholder="At least 8 characters" required minlength="8" autocomplete="new-password">
                </div>
                <div class="field">
                    <label for="password_confirm">Confirm password</label>
                    <input type="password" id="password_confirm" name="password_confirm" placeholder="Type it again" required minlength="8" autocomplete="new-password">
                </div>
            </div>

            <button type="submit" class="btn btn-dark btn-block">Change password <?= icon('arrow', 'icon icon-sm') ?></button>
        </form>

        <p class="center" style="margin-top:16px"><span class="muted">No email?</span> <a href="<?= e(url('forgot-password')) ?>" class="strong-link">Send a new code</a></p>
        <p class="center"><a href="<?= e(url('login')) ?>" class="muted-link">← Back to sign in</a></p>
    </div>
</div>
