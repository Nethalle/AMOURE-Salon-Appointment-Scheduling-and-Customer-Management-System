<section class="card auth-card">
    <h1 class="card-title">Log in</h1>
    <p class="muted">Welcome back to AMOURÉ SALON.</p>

    <form method="post" action="<?= e(url('login')) ?>" class="form" novalidate>
        <?= csrf_field() ?>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= old('email') ?>" required autocomplete="email" autofocus>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">

        <button type="submit" class="btn btn-primary btn-block">Log in</button>
    </form>

    <p class="muted center">No account yet? <a href="<?= e(url('register')) ?>">Create one</a></p>
</section>
