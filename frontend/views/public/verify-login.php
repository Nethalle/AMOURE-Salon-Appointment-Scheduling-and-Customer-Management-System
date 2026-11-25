<?php /** @var array $flashes  @var string $masked */ ?>
<div class="auth-card-split">
    <?php
    $panelTitle  = 'One more';
    $panelScript = 'step.';
    $panelText   = '2-step verification keeps your account safe, even if someone learns your password.';
    require __DIR__ . '/_auth-panel.php';
    ?>

    <div class="auth-form-panel">
        <p class="eyebrow eyebrow-gold">2-step verification</p>
        <h1 class="auth-title">Check your email</h1>
        <p class="muted">We sent a 6-digit code to <strong><?= e($masked) ?></strong>. It expires in 15 minutes.</p>

        <?php require __DIR__ . '/_auth-flashes.php'; ?>

        <form method="post" action="<?= e(url('verify-login')) ?>" class="form" novalidate>
            <?= csrf_field() ?>
            <label for="code">Sign-in code</label>
            <input type="text" id="code" name="code" class="code-input" inputmode="numeric" pattern="[0-9 ]*" maxlength="7"
                   placeholder="••••••" autocomplete="one-time-code" required autofocus>

            <label class="check-row"><input type="checkbox" name="remember" value="1"> Remember this device for 30 days</label>

            <button type="submit" class="btn btn-dark btn-block">Verify and sign in <?= icon('arrow', 'icon icon-sm') ?></button>
        </form>

        <form method="post" action="<?= e(url('verify-login')) ?>" class="center resend-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="resend">
            <span class="muted">Didn't get it? Check Spam, or</span>
            <button type="submit" class="link-button">send a new code</button>
        </form>
        <p class="center"><a href="<?= e(url('login')) ?>" class="muted-link">← Use a different account</a></p>
    </div>
</div>
