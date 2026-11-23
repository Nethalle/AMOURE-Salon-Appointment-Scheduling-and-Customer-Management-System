<?php
/** @var string $title */ /** @var array $flashes */ /** @var ?array $user */ /** @var ?string $layout */

// Three layouts: 'auth' (login/register, no menus), 'app' (admin & staff, sidebar), 'site' (public website).
$isAuth      = ($layout ?? '') === 'auth';
$isApp       = !$isAuth && $user && in_array($user['role'], ['admin', 'staff'], true);
$currentPage = is_string($_GET['page'] ?? null) ? $_GET['page'] : 'home';

$sidebar = [];
if ($user && $user['role'] === 'admin') {
    $sidebar = [
        'admin/dashboard'    => 'Dashboard',
        'staff/appointments' => 'Appointments',
        'staff/customers'    => 'Customers',
        'admin/services'     => 'Services',
        'admin/stylists'     => 'Stylists',
        'admin/reviews'      => 'Reviews',
        'admin/reports'      => 'Reports',
        'admin/users'        => 'User accounts',
        'admin/settings'     => 'Settings',
    ];
} elseif ($user && $user['role'] === 'staff') {
    $sidebar = [
        'staff/dashboard'    => 'Dashboard',
        'staff/appointments' => 'Appointments',
        'staff/customers'    => 'Customers',
    ];
}

/** True when $route is the current page or a sub-page of it (admin/services/edit => admin/services). */
$isActive = static fn (string $route): bool =>
    $currentPage === $route || str_starts_with($currentPage, $route . '/');

$bodyClass = $isAuth ? 'layout-auth' : ($isApp ? 'layout-app' : 'layout-site');
$phone     = setting('salon_phone');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'AMOURÉ SALON') ?> · AMOURÉ SALON</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500;1,600&family=Great+Vibes&family=Montserrat:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="frontend/assets/css/style.css">
</head>
<body class="<?= e($bodyClass) ?>">

<?php if ($isAuth): ?>
<main class="auth-page">

<?php else: ?>

<?php if (!$isApp): ?>
<div class="promo-bar">
    <p><?= icon('sparkle', 'icon icon-sm') ?> Book online in minutes &nbsp;|&nbsp; Look good. Feel amazing.</p>
    <?php if ($phone !== ''): ?>
        <p class="promo-contact"><?= icon('phone', 'icon icon-sm') ?> Call us: <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= e($phone) ?></a></p>
    <?php endif; ?>
</div>
<?php endif; ?>

<header class="site-header">
    <a class="brand" href="<?= e(url($isApp ? home_for_role($user['role']) : 'home')) ?>">
        <span class="brand-mark" aria-hidden="true">A</span>
        <span class="brand-text">AMOURÉ <small>SALON</small></span>
    </a>

    <?php if (!$isApp): ?>
        <nav class="main-nav" aria-label="Main">
            <a href="<?= e(url('home')) ?>" <?= $currentPage === 'home' ? 'aria-current="page"' : '' ?>>Home</a>
            <a href="<?= e(url('home')) ?>#services">Services</a>
            <a href="<?= e(url('home')) ?>#about">About</a>
            <a href="<?= e(url('home')) ?>#gallery">Gallery</a>
            <a href="<?= e(url('home')) ?>#reviews">Reviews</a>
            <a href="<?= e(url('home')) ?>#contact">Contact</a>
        </nav>
    <?php endif; ?>

    <nav class="site-nav" aria-label="Account">
        <?php if ($user): ?>
            <?php if ($user['role'] === 'customer'): ?>
                <a href="<?= e(url('customer/my-appointments')) ?>" <?= $isActive('customer/my-appointments') ? 'aria-current="page"' : '' ?>>My appointments</a>
            <?php elseif (!$isApp): ?>
                <a href="<?= e(url(home_for_role($user['role']))) ?>">Dashboard</a>
            <?php endif; ?>
            <a href="<?= e(url('account/password')) ?>" class="nav-user" title="Change password" <?= $isActive('account/password') ? 'aria-current="page"' : '' ?>>
                <?= icon('user', 'icon icon-sm') ?> <?= e($user['name']) ?>
                <?php if ($isApp): ?><span class="role-tag"><?= e($user['role']) ?></span><?php endif; ?>
            </a>
            <form method="post" action="<?= e(url('logout')) ?>" class="inline-form">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-ghost btn-small">Log out</button>
            </form>
            <?php if ($user['role'] === 'customer'): ?>
                <a href="<?= e(url('customer/book')) ?>" class="btn btn-primary btn-small"><?= icon('calendar', 'icon icon-sm') ?> Book appointment</a>
            <?php endif; ?>
        <?php else: ?>
            <a href="<?= e(url('login')) ?>" class="nav-user"><?= icon('user', 'icon icon-sm') ?> Log in</a>
            <a href="<?= e(url('register')) ?>" class="btn btn-primary btn-small"><?= icon('calendar', 'icon icon-sm') ?> Book appointment</a>
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

<main class="page <?= $currentPage === 'home' ? 'page-home' : '' ?>">
    <?php if ($flashes): ?>
        <div class="flash-stack">
        <?php foreach ($flashes as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?>" role="<?= $f['type'] === 'error' ? 'alert' : 'status' ?>">
                <?= e($f['message']) ?>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
