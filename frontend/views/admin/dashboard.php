<section class="card">
    <h1 class="card-title">Admin dashboard</h1>
    <p class="muted">Logged in as <?= e($user['name']) ?> (administrator).</p>
    <p>Appointment counts, users, settings, reviews and reports will appear here as each module is built.</p>
    <div class="hero-actions">
        <a href="<?= e(url('admin/services')) ?>" class="btn btn-ghost">Manage services</a>
        <a href="<?= e(url('admin/stylists')) ?>" class="btn btn-ghost">Manage stylists</a>
    </div>
</section>
