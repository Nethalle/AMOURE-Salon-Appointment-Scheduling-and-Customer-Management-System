<section class="card auth-card">
    <h1 class="card-title">Create an account</h1>
    <p class="muted">Book and manage your appointments online.</p>

    <form method="post" action="<?= e(url('register')) ?>" class="form" novalidate>
        <?= csrf_field() ?>

        <label for="name">Full name</label>
        <input type="text" id="name" name="name" value="<?= old('name') ?>" required maxlength="100" autocomplete="name">

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= old('email') ?>" required maxlength="150" autocomplete="email">
        <small class="hint">We send booking confirmations and reminders here.</small>

        <label for="phone">Phone (optional)</label>
        <input type="tel" id="phone" name="phone" value="<?= old('phone') ?>" maxlength="20" autocomplete="tel">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
        <small class="hint">At least 8 characters.</small>

        <label for="password_confirm">Confirm password</label>
        <input type="password" id="password_confirm" name="password_confirm" required minlength="8" autocomplete="new-password">

        <button type="submit" class="btn btn-primary btn-block">Create account</button>
    </form>

    <p class="muted center">Already have an account? <a href="<?= e(url('login')) ?>">Log in</a></p>
</section>
