<?php
// AMOURÉ SALON: entry point + router.
// Every page is opened as index.php?page=<route>

declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

require __DIR__ . '/backend/core/helpers.php';
require __DIR__ . '/backend/core/db.php';
require __DIR__ . '/backend/core/csrf.php';
require __DIR__ . '/backend/core/auth.php';
require __DIR__ . '/backend/core/settings.php';
require __DIR__ . '/backend/core/activity.php';

// Basic security headers.
header('X-Content-Type-Options: nosniff');          // browsers must not guess file types
header('X-Frame-Options: SAMEORIGIN');              // other sites can't show this system inside a frame
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443') {
    header('Strict-Transport-Security: max-age=31536000'); // only on https: keep using https
}

start_session();

// route => [Controller, method]
$routes = [
    'home'                     => ['HomeController', 'index'],
    'login'                    => ['AuthController', 'login'],
    'register'                 => ['AuthController', 'register'],
    'logout'                   => ['AuthController', 'logout'],
    'admin/dashboard'          => ['DashboardController', 'admin'],
    'admin/services'           => ['ServiceController', 'index'],
    'admin/services/edit'      => ['ServiceController', 'edit'],
    'admin/services/toggle'    => ['ServiceController', 'toggle'],
    'admin/stylists'           => ['StylistController', 'index'],
    'admin/stylists/edit'      => ['StylistController', 'edit'],
    'admin/stylists/toggle'    => ['StylistController', 'toggle'],
    'admin/reports'            => ['ReportController', 'index'],
    'admin/reports/export'     => ['ReportController', 'export'],
    'admin/users'              => ['UserController', 'index'],
    'admin/users/edit'         => ['UserController', 'edit'],
    'admin/users/toggle'       => ['UserController', 'toggle'],
    'account/password'         => ['UserController', 'changePassword'],
    'admin/settings'           => ['SettingsController', 'index'],
    'admin/settings/send-reminders' => ['SettingsController', 'sendReminders'],
    'admin/settings/test-email'     => ['SettingsController', 'testEmail'],
    'staff/dashboard'          => ['DashboardController', 'staff'],
    'staff/appointments'       => ['StaffAppointmentController', 'index'],
    'staff/customers'          => ['CustomerController', 'index'],
    'staff/customers/view'     => ['CustomerController', 'show'],
    'staff/appointments/status'     => ['StaffAppointmentController', 'status'],
    'staff/appointments/new'        => ['StaffAppointmentController', 'create'],
    'staff/appointments/reschedule' => ['StaffAppointmentController', 'reschedule'],
    'customer/my-appointments' => ['AppointmentController', 'mine'],
    'customer/book'            => ['AppointmentController', 'book'],
    'customer/cancel'          => ['AppointmentController', 'cancel'],
    'customer/review'          => ['ReviewController', 'write'],
    'admin/reviews'            => ['ReviewController', 'index'],
    'admin/reviews/moderate'   => ['ReviewController', 'moderate'],
    'notifications/read'       => ['NotificationController', 'read'],
    'setup'                    => ['SetupController', 'index'],
    'admin/activity'           => ['ActivityController', 'index'],
];

$page = $_GET['page'] ?? 'home';

if (!is_string($page) || !isset($routes[$page])) {
    http_response_code(404);
    view('public/not-found', ['title' => 'Page not found']);
    exit;
}

[$controller, $method] = $routes[$page];

try {
    require_once __DIR__ . '/backend/controllers/' . $controller . '.php';
    (new $controller())->$method();
} catch (PDOException $e) {
    error_log('[AMOURE] Database error: ' . $e->getMessage());
    http_response_code(500);
    view('public/error', [
        'title'   => 'Something went wrong',
        'message' => 'The system could not reach the database. Check that MySQL is running in XAMPP and that backend/config/database.php is correct.',
    ]);
} catch (RuntimeException $e) {
    // Setup problems, e.g. a missing config file.
    error_log('[AMOURE] Setup error: ' . $e->getMessage());
    http_response_code(500);
    view('public/error', [
        'title'   => 'Setup problem',
        'message' => $e->getMessage(),
    ]);
}
