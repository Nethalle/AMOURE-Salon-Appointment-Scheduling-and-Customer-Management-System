<section class="card center">
    <h1 class="card-title">Something went wrong</h1>
    <p class="muted"><?= e($message ?? 'Please try again.') ?></p>
    <a href="<?= e(url('home')) ?>" class="btn btn-ghost">Go to home</a>
</section>
