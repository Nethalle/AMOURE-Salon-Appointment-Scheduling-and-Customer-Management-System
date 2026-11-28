<?php /** @var array $me  @var bool $twoFactor  @var bool $requiredBy  @var int $devices  @var bool $ready */
$active = $twoFactor || $requiredBy;
?>
<div class="book-hero">
    <div class="book-hero-text">
        <p class="book-kicker">Account &amp; security</p>
        <h1>My Account</h1>
        <p>Keep your login safe: change your password and turn on 2-step verification.</p>
    </div>
    <svg class="book-hero-art" viewBox="0 0 520 200" preserveAspectRatio="xMaxYMid slice" aria-hidden="true">
        <g class="bh-petals">
            <path d="M380 96c-18-40-6-72 14-86 14 26 10 58-14 86Z"/><path d="M380 96c34-28 68-26 86-8-24 20-56 22-86 8Z"/>
            <path d="M380 96c40 6 60 32 58 56-30-4-52-24-58-56Z"/><path d="M380 96c-4 40-30 62-56 62 2-30 24-52 56-62Z"/>
            <path d="M380 96c-38-10-56-40-50-62 30 8 48 30 50 62Z"/>
        </g>
        <circle cx="380" cy="96" r="9" class="bh-heart"/>
    </svg>
</div>

<?php $role = (string) ($me['role'] ?? 'customer'); ?>
<section class="acct-profile">
    <span class="acct-avatar"><?= e(initials($me['name'] ?? '?')) ?></span>
    <div class="acct-who">
        <h2><?= e($me['name'] ?? '') ?> <span class="acct-role acct-role-<?= e($role) ?>"><?= e(ucfirst($role)) ?></span></h2>
        <p><?= icon('mail', 'icon icon-xs') ?> <?= e($me['email'] ?? '') ?></p>
        <?php if (!empty($me['created_at'])): ?><p><?= icon('calendar', 'icon icon-xs') ?> Member since <?= e(date('F Y', strtotime($me['created_at']))) ?></p><?php endif; ?>
    </div>
    <ul class="acct-checks">
        <li class="is-ok"><?= icon('check') ?><span><strong>Password</strong> set</span></li>
        <li class="<?= $active ? 'is-ok' : 'is-warn' ?>"><?= icon($active ? 'check' : 'shield') ?><span><strong>2-step verification</strong> <?= $active ? 'on' : 'off' ?></span></li>
    </ul>
</section>

<div class="account-grid">
    <section class="panel acct-card">
        <div class="panel-head"><h2><span class="acct-ic"><?= icon('key') ?></span> Change password</h2></div>
        <form method="post" action="<?= e(url('account/password')) ?>" class="form" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label for="current_password">Current password</label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
            </div>
            <div class="field">
                <label for="new_password">New password</label>
                <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
                <small class="hint">At least 8 characters. Mixing letters, numbers and a symbol makes it stronger.</small>
            </div>
            <div class="field">
                <label for="confirm_password">Confirm new password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary">Change password</button>
        </form>
    </section>

    <section class="panel acct-card">
        <div class="panel-head">
            <h2><span class="acct-ic"><?= icon('shield') ?></span> 2-step verification</h2>
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
