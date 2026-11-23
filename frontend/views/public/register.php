<?php /** @var array $flashes */ ?>
<div class="auth-card-split">
    <?php
    $panelTitle  = 'Your best look starts';
    $panelScript = 'here.';
    $panelText   = 'Create an account to book appointments online, get email confirmations and reminders, and see your history.';
    require __DIR__ . '/_auth-panel.php';
    ?>

    <div class="auth-form-panel">
        <p class="eyebrow eyebrow-gold">Join AMOURÉ SALON</p>
        <h1 class="auth-title">Create an Account</h1>
        <p class="muted">It only takes a minute.</p>

        <?php require __DIR__ . '/_auth-flashes.php'; ?>

        <form method="post" action="<?= e(url('register')) ?>" class="form" novalidate>
            <?= csrf_field() ?>

            <label for="name">Full name</label>
            <input type="text" id="name" name="name" value="<?= old('name') ?>" placeholder="Enter your full name" required maxlength="100" autocomplete="name" autofocus>

            <div class="form-two">
                <div class="field">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" value="<?= old('email') ?>" placeholder="you@example.com" required maxlength="150" autocomplete="email">
                </div>
                <div class="field">
                    <label for="phone">Phone (optional)</label>
                    <input type="tel" id="phone" name="phone" value="<?= old('phone') ?>" placeholder="09XX XXX XXXX" maxlength="20" autocomplete="tel">
                </div>
            </div>

            <div class="form-two">
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="At least 8 characters" required minlength="8" autocomplete="new-password">
                </div>
                <div class="field">
                    <label for="password_confirm">Confirm password</label>
                    <input type="password" id="password_confirm" name="password_confirm" placeholder="Type it again" required minlength="8" autocomplete="new-password">
                </div>
            </div>
            <small class="hint">We send booking confirmations and reminders to your email.</small>

            <button type="submit" class="btn btn-dark btn-block">Create account <?= icon('arrow', 'icon icon-sm') ?></button>
        </form>

        <p class="divider-text"><span>Already a client?</span></p>
        <p class="center">Have an account? <a href="<?= e(url('login')) ?>" class="strong-link">Sign in</a></p>
        <p class="center"><a href="<?= e(url('home')) ?>" class="muted-link">← Back to home</a></p>
    </div>
</div>
