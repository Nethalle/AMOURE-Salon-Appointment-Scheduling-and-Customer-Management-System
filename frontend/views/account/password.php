<?php /** @var array $me  @var bool $twoFactor  @var bool $requiredBy  @var int $devices  @var bool $ready */
$active = $twoFactor || $requiredBy;
?>
<div class="ph">
    <div>
        <h1>My Account</h1>
        <p><?= e($me['name'] ?? '') ?> · <?= e($me['email'] ?? '') ?></p>
    </div>
</div>

<div class="account-grid">
    <section class="panel">
        <div class="panel-head"><h2><?= icon('key', 'icon icon-sm') ?> Change password</h2></div>
        <form method="post" action="<?= e(url('account/password')) ?>" class="form" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label for="current_password">Current password</label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
            </div>
            <div class="field">
                <label for="new_password">New password</label>
                <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
                <small class="hint">At least 8 characters.</small>
            </div>
            <div class="field">
                <label for="confirm_password">Confirm new password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary">Change password</button>
        </form>
    </section>

    <section class="panel">
        <div class="panel-head">
            <h2><?= icon('shield', 'icon icon-sm') ?> 2-step verification</h2>
            <span class="badge <?= $active ? 'badge-active' : 'badge-inactive' ?>"><?= $active ? 'On' : 'Off' ?></span>
        </div>
        <p class="muted" style="margin-top:0">When it's on, logging in needs your password <strong>and</strong> a 6-digit code we email you. Even if someone learns your password, they can't get in.</p>

        <?php if (!$ready): ?>
            <div class="notice">Not available yet: the admin needs to import <strong>database/auth-codes.sql</strong>.</div>
        <?php elseif ($requiredBy): ?>
            <div class="notice">The salon requires 2-step verification for admin and staff accounts, so it's always on for you.</div>
        <?php else: ?>
            <form method="post" action="<?= e(url('account/two-factor')) ?>" class="form" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="<?= $twoFactor ? 'disable' : 'enable' ?>">
                <div class="field">
                    <label for="tf_password">Confirm with your password</label>
                    <input type="password" id="tf_password" name="current_password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn <?= $twoFactor ? 'btn-ghost' : 'btn-primary' ?>">
                    <?= $twoFactor ? 'Turn off 2-step verification' : 'Turn on 2-step verification' ?>
                </button>
            </form>
        <?php endif; ?>

        <?php if ($ready && $active): ?>
            <div class="set-section">
                <h3>Remembered devices</h3>
                <p><?= $devices ?> browser<?= $devices === 1 ? '' : 's' ?> can log in without a code for now ("Remember this device").</p>
                <?php if ($devices > 0): ?>
                    <form method="post" action="<?= e(url('account/two-factor')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="forget_devices">
                        <button type="submit" class="btn btn-ghost btn-small">Sign out remembered devices</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
