<section class="card auth-card">
    <h1 class="card-title">Change password</h1>
    <p class="muted">Use at least 8 characters.</p>

    <form method="post" action="<?= e(url('account/password')) ?>" class="form" novalidate>
        <?= csrf_field() ?>

        <label for="current_password">Current password</label>
        <input type="password" id="current_password" name="current_password" required autocomplete="current-password">

        <label for="new_password">New password</label>
        <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">

        <label for="confirm_password">Confirm new password</label>
        <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">

        <button type="submit" class="btn btn-primary btn-block">Change password</button>
    </form>
</section>
