<?php
/** @var string $title  @var array $flashes  @var ?array $user */

// Admin and staff get the dashboard layout with a sidebar; everyone else gets the website layout.
$isApp       = $user && in_array($user['role'], ['admin', 'staff'], true);
$currentPage = is_string($_GET['page'] ?? null) ? $_GET['page'] : 'home';

$sidebar = [];
if ($user && $user['role'] === 'admin') {
    $sidebar = [
        'admin/dashboard'    => 'Dashboard',
        'staff/appointments' => 'Appointments',
        'admin/services'     => 'Services',
        'admin/stylists'     => 'Stylists',
        'admin/settings'     => 'Settings',
    ];
} elseif ($user && $user['role'] === 'staff') {
    $sidebar = [
        'staff/dashboard'    => 'Dashboard',
        'staff/appointments' => 'Appointments',
    ];
}

/** True when $route is the current page or a sub-page of it (admin/services/edit => admin/services). */
$isActive = static fn (string $route): bool =>
    $currentPage === $route || str_starts_with($currentPage, $route . '/');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'AMOURÉ SALON') ?> · AMOURÉ SALON</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Montserrat:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="frontend/assets/css/style.css">
</head>
<body class="<?= $isApp ? 'layout-app' : 'layout-site' ?>">

<header class="site-header">
    <a class="brand" href="<?= e(url($isApp ? home_for_role($user['role']) : 'home')) ?>">
        <span class="brand-mark" aria-hidden="true">A</span>
        <span class="brand-text">AMOURÉ <small>SALON</small></span>
    </a>

    <nav class="site-nav" aria-label="Account">
        <?php if ($user): ?>
            <?php if ($user['role'] === 'customer'): ?>
                <a href="<?= e(url('customer/my-appointments')) ?>" <?= $isActive('customer/my-appointments') ? 'aria-current="page"' : '' ?>>My appointments</a>
                <a href="<?= e(url('customer/book')) ?>" class="btn btn-primary btn-small">Book appointment</a>
            <?php endif; ?>
            <span class="nav-user">
                <?= e($user['name']) ?>
                <?php if ($isApp): ?><span class="role-tag"><?= e($user['role']) ?></span><?php endif; ?>
            </span>
            <form method="post" action="<?= e(url('logout')) ?>" class="inline-form">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-ghost btn-small">Log out</button>
            </form>
        <?php else: ?>
            <a href="<?= e(url('login')) ?>" <?= $isActive('login') ? 'aria-current="page"' : '' ?>>Log in</a>
            <a href="<?= e(url('register')) ?>" class="btn btn-primary btn-small">Book appointment</a>
        <?php endif; ?>
    </nav>
</header>

<?php if ($isApp): ?>
<div class="shell">
    <aside class="sidebar">
        <nav aria-label="Main">
            <p class="sidebar-label">Menu</p>
            <?php foreach ($sidebar as $route => $label): ?>
                <a href="<?= e(url($route)) ?>" class="sidebar-link" <?= $isActive($route) ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
    </aside>
<?php endif; ?>

<main class="page">
    <?php foreach ($flashes as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>" role="<?= $f['type'] === 'error' ? 'alert' : 'status' ?>">
            <?= e($f['message']) ?>
        </div>
    <?php endforeach; ?>
