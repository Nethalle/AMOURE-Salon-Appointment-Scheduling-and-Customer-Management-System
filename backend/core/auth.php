<?php
// Sessions, login state and role guard.

declare(strict_types=1);

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

/** The logged-in user ['id', 'name', 'role'], or null. */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function login_user(array $user): void
{
    session_regenerate_id(true); // prevents session fixation
    $_SESSION['user'] = [
        'id'   => (int) $user['id'],
        'name' => $user['name'],
        'role' => $user['role'],
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Where each role lands after logging in. */
function home_for_role(string $role): string
{
    return match ($role) {
        'admin'  => 'admin/dashboard',
        'staff'  => 'staff/dashboard',
        default  => 'customer/my-appointments',
    };
}

/**
 * Stop here unless someone is logged in. Returns the user.
 * Re-reads the account on every protected page, so a deactivation or role change
 * by the admin takes effect immediately, even for someone already logged in.
 */
function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        // Remember where they were going (e.g. a "View appointment" button in an email).
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $_SESSION['after_login'] = $_GET;
        }
        flash('error', 'Please log in first.');
        redirect('login');
    }

    $stmt = db()->prepare('SELECT name, role, is_active FROM users WHERE id = :id');
    $stmt->execute(['id' => $user['id']]);
    $fresh = $stmt->fetch();

    if (!$fresh || !(int) $fresh['is_active']) {
        logout_user();
        start_session();
        flash('error', 'Your account is no longer active. Please contact the salon.');
        redirect('login');
    }

    $_SESSION['user']['name'] = $fresh['name'];
    $_SESSION['user']['role'] = $fresh['role'];
    return $_SESSION['user'];
}

/** Stop here unless the logged-in user has one of the given roles. */
function require_role(array $roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        view('public/forbidden', ['title' => 'Access denied']);
        exit;
    }
    return $user;
}
