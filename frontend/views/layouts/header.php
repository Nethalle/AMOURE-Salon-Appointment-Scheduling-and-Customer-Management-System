<?php /** @var string $title  @var array $flashes  @var ?array $user */ ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'AMOURÉ SALON') ?> · AMOURÉ SALON</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;1,400&family=Montserrat:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="frontend/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="<?= e(url('home')) ?>">
        <span class="brand-mark" aria-hidden="true">A</span>
        <span class="brand-text">AMOURÉ <small>SALON</small></span>
    </a>

    <nav class="site-nav" aria-label="Main">
        <?php if ($user): ?>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="<?= e(url('admin/dashboard')) ?>">Dashboard</a>
            <?php elseif ($user['role'] === 'staff'): ?>
                <a href="<?= e(url('staff/dashboard')) ?>">Dashboard</a>
            <?php else: ?>
                <a href="<?= e(url('customer/my-appointments')) ?>">My appointments</a>
            <?php endif; ?>
            <span class="nav-user"><?= e($user['name']) ?></span>
            <form method="post" action="<?= e(url('logout')) ?>" class="inline-form">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-ghost">Log out</button>
            </form>
        <?php else: ?>
            <a href="<?= e(url('login')) ?>">Log in</a>
            <a href="<?= e(url('register')) ?>" class="btn btn-primary">Book an appointment</a>
        <?php endif; ?>
    </nav>
</header>

<main class="page">
    <?php foreach ($flashes as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>" role="<?= $f['type'] === 'error' ? 'alert' : 'status' ?>">
            <?= e($f['message']) ?>
        </div>
    <?php endforeach; ?>
