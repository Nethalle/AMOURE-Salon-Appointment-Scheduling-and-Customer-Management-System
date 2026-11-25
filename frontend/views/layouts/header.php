<?php
/** @var string $title */ /** @var array $flashes */ /** @var ?array $user */ /** @var ?string $layout */

// Three layouts: 'auth' (login/register, no menus), 'app' (logged-in dashboard with sidebar), 'site' (public website).
// Admin and staff always get the app layout. Customers get it everywhere except the public home page.
$currentPage = is_string($_GET['page'] ?? null) ? $_GET['page'] : 'home';
$isAuth      = ($layout ?? '') === 'auth';
// Admin and staff always get the app layout. Customers get it everywhere except the public home page.
$isApp       = !$isAuth && $user && ($layout ?? '') !== 'site'
    && (in_array($user['role'], ['admin', 'staff'], true) || $currentPage !== 'home');
$isCust      = false;
$isCustPage  = false;

// route => [label, icon]
$sidebar = [];
if ($user && $user['role'] === 'admin') {
    $sidebar = [
        'admin/dashboard'    => ['Dashboard', 'home'],
        'staff/appointments' => ['Appointments', 'calendar'],
        'staff/customers'    => ['Customers', 'users'],
        'admin/services'     => ['Services', 'scissors'],
        'admin/stylists'     => ['Stylists', 'user'],
        'admin/reviews'      => ['Reviews', 'star'],
        'admin/reports'      => ['Reports', 'bar'],
        'admin/users'        => ['User Accounts', 'shield'],
        'admin/activity'     => ['Activity Log', 'clock'],
    ];
} elseif ($user && $user['role'] === 'staff') {
    $sidebar = [
        'staff/dashboard'    => ['Dashboard', 'home'],
        'staff/appointments' => ['Appointments', 'calendar'],
        'staff/customers'    => ['Customers', 'users'],
    ];
} elseif ($user) {
    $sidebar = [
        'customer/my-appointments' => ['My Appointments', 'calendar'],
        'customer/book'            => ['Book Appointment', 'plus'],
        'account/password'         => ['My Account', 'user'],
    ];
}

/** True when $route is the current page or a sub-page of it (admin/services/edit => admin/services). */
$isActive = static fn (string $route): bool =>
    $currentPage === $route || str_starts_with($currentPage, $route . '/');

// Bell: in-app notifications (see backend/services/NotificationFeed.php).
$notif = ['items' => [], 'unread' => 0];
if ($isApp || $isCust) {
    require_once __DIR__ . '/../../../backend/services/NotificationFeed.php';
    try {
        $notif = NotificationFeed::for($user);
    } catch (Throwable $e) {
        $notif = ['items' => [], 'unread' => 0]; // never break the page because of the bell
    }
}
$roleLabel = $user ? ['admin' => 'Admin', 'staff' => 'Staff', 'customer' => 'Customer'][$user['role']] ?? '' : '';

$bodyClass = $isAuth ? 'layout-auth' : ($isApp ? 'layout-app' : ($isCustPage ? 'layout-app layout-cust' : 'layout-site'));
$phone     = setting('salon_phone');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>try{var t=localStorage.getItem('amoure-theme');if(t==='dark'||t==='light'){document.documentElement.setAttribute('data-theme',t);}}catch(e){}</script>
    <title><?= e($title ?? 'AMOURÉ SALON') ?> · AMOURÉ SALON</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500;1,600&family=Great+Vibes&family=Montserrat:wght@400;500;600&display=swap">
    <?php $css = __DIR__ . '/../../assets/css/'; ?>
    <link rel="stylesheet" href="frontend/assets/css/style.css?v=<?= @filemtime($css . 'style.css') ?>">
    <link rel="stylesheet" href="frontend/assets/css/home.css?v=<?= @filemtime($css . 'home.css') ?>">
    <?php if ($isApp || $isCust): ?>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap">
    <link rel="stylesheet" href="frontend/assets/css/app.css?v=<?= @filemtime($css . 'app.css') ?>">
    <?php endif; ?>
</head>
<body class="<?= e($bodyClass) ?>">

<?php if ($isAuth): ?>
<main class="auth-page">

<?php elseif ($isApp): ?>
<div class="app">
    <aside class="app-side" id="app-side">
        <a class="app-logo" href="<?= e(url(home_for_role($user['role']))) ?>">
            <span class="app-logo-mark" aria-hidden="true">A<i>✦</i></span>
            <span class="app-logo-name">AMOURÉ</span>
            <span class="app-logo-sub">SALON</span>
        </a>

        <nav class="app-nav" aria-label="Main">
            <?php foreach ($sidebar as $route => [$label, $ic]): ?>
                <a href="<?= e(url($route)) ?>" <?= $isActive($route) ? 'aria-current="page"' : '' ?>><?= icon($ic) ?><span><?= e($label) ?></span></a>
            <?php endforeach; ?>
        </nav>

        <div class="app-side-foot">
            <?php if ($user['role'] === 'admin'): ?>
                <a href="<?= e(url('admin/settings')) ?>" class="app-side-link" <?= $isActive('admin/settings') ? 'aria-current="page"' : '' ?>><?= icon('settings') ?><span>Settings</span></a>
            <?php else: ?>
                <a href="<?= e(url('home')) ?>" class="app-side-link"><?= icon('globe') ?><span>View Website</span></a>
            <?php endif; ?>
            <form method="post" action="<?= e(url('logout')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="app-logout"><?= icon('logout') ?> Logout</button>
            </form>
        </div>
    </aside>

    <div class="app-main">
        <header class="app-top">
            <button type="button" class="app-menu-btn" data-toggle-side aria-label="Open menu"><span></span><span></span><span></span></button>
            <button type="button" class="theme-btn" data-theme-toggle aria-label="Switch light / dark mode" title="Light / dark mode">
                <?= icon('moon', 'icon moon') ?><?= icon('sun', 'icon sun') ?>
            </button>
            <?php require __DIR__ . '/_bell.php'; ?>
            <details class="app-user">
                <summary>
                    <span class="app-avatar"><?= e(initials($user['name'])) ?></span>
                    <span class="app-user-text"><strong><?= e($user['name']) ?></strong><small><?= e($roleLabel) ?></small></span>
                    <?= icon('chevron', 'icon icon-sm') ?>
                </summary>
                <div class="menu menu-right">
                    <a href="<?= e(url('account/password')) ?>"><?= icon('key') ?> Change password</a>
                    <a href="<?= e(url('home')) ?>"><?= icon('globe') ?> View website</a>
                    <form method="post" action="<?= e(url('logout')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit"><?= icon('logout') ?> Log out</button>
                    </form>
                </div>
            </details>
        </header>

        <main class="app-page">
            <?php if ($flashes): ?>
                <div class="flash-stack">
                <?php foreach ($flashes as $f): ?>
                    <div class="alert alert-<?= e($f['type']) ?>" role="<?= $f['type'] === 'error' ? 'alert' : 'status' ?>">
                        <?= e($f['message']) ?>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>

<?php else: ?>
<?php if (!$isCustPage): ?>
<div class="promo-bar">
    <p><?= icon('sparkle', 'icon icon-sm') ?> Book online in minutes &nbsp;|&nbsp; Look good. Feel amazing.</p>
    <?php if ($phone !== ''): ?>
        <p class="promo-contact"><?= icon('phone', 'icon icon-sm') ?> Call us: <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= e($phone) ?></a></p>
    <?php endif; ?>
</div>
<?php endif; ?>

<header class="site-header <?= $isCust ? 'cust-top' : '' ?>">
    <a class="brand" href="<?= e(url('home')) ?>">
        <span class="brand-mark" aria-hidden="true">A</span>
        <span class="brand-text">AMOURÉ <small>SALON</small></span>
    </a>

    <nav class="main-nav" aria-label="Main">
        <a href="<?= e(url('home')) ?>" <?= $currentPage === 'home' ? 'aria-current="page"' : '' ?>>Home</a>
        <a href="<?= e(url('home')) ?>#services">Services</a>
        <a href="<?= e(url('home')) ?>#about">About</a>
        <a href="<?= e(url('home')) ?>#gallery">Gallery</a>
        <a href="<?= e(url('home')) ?>#reviews">Reviews</a>
        <a href="<?= e(url('home')) ?>#contact">Contact</a>
    </nav>

    <nav class="site-nav" aria-label="Account">
        <?php if ($isCust): ?>
            <?php require __DIR__ . '/_bell.php'; ?>
            <details class="app-user">
                <summary>
                    <span class="app-avatar"><?= e(initials($user['name'])) ?></span>
                    <span class="app-user-text"><strong><?= e($user['name']) ?></strong></span>
                    <?= icon('chevron', 'icon icon-sm') ?>
                </summary>
                <div class="menu menu-right">
                    <a href="<?= e(url('customer/my-appointments')) ?>"><?= icon('calendar') ?> My appointments</a>
                    <a href="<?= e(url('customer/book')) ?>"><?= icon('plus') ?> Book appointment</a>
                    <a href="<?= e(url('account/password')) ?>"><?= icon('key') ?> Change password</a>
                    <hr>
                    <form method="post" action="<?= e(url('logout')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="danger"><?= icon('logout') ?> Log out</button>
                    </form>
                </div>
            </details>
        <?php elseif ($user): ?>
            <a href="<?= e(url(home_for_role($user['role']))) ?>" class="nav-user"><?= icon('user', 'icon icon-sm') ?> <?= $user['role'] === 'customer' ? 'My appointments' : 'Dashboard' ?></a>
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

<main class="page <?= $currentPage === 'home' ? 'page-home' : '' ?> <?= $isCustPage ? 'app-page cust-page' : '' ?>">
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
