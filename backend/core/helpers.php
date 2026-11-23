<?php
// Escaping, URLs, redirects, flash messages, views.

declare(strict_types=1);

/** Escape any value for safe HTML output. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Build a link to a route: url('login') => index.php?page=login */
function url(string $page, array $params = []): string
{
    return 'index.php?' . http_build_query(['page' => $page] + $params);
}

/** Send the browser to another route and stop. */
function redirect(string $page, array $params = []): void
{
    header('Location: ' . url($page, $params));
    exit;
}

/** Queue a one-time message: flash('success', 'Saved.') */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Read and clear queued messages. */
function get_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/** Remember form values (never passwords) so a form can be refilled after an error. */
function remember_old(array $values): void
{
    $_SESSION['old'] = $values;
}

/** Read a remembered form value, already escaped. */
function old(string $key): string
{
    return e($_SESSION['old'][$key] ?? '');
}

/** A remembered form value if there is one, otherwise the given default (for edit forms). */
function old_or(string $key, mixed $default): string
{
    return e($_SESSION['old'][$key] ?? $default);
}

/** Format a price in pesos: 1500 => ₱1,500.00 */
function peso(mixed $amount): string
{
    return '₱' . number_format((float) $amount, 2);
}

/** '14:30:00' or '14:30' => '2:30 PM' */
function fmt_time(string $time): string
{
    return date('g:i A', strtotime($time));
}

/** '2026-11-24' => 'Tue, Nov 24, 2026' */
function fmt_date(string $date): string
{
    return date('D, M j, Y', strtotime($date));
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

/** Render frontend/views/emails/<name>.php inside the email layout and return the HTML. */
function render_email(string $name, array $data = []): string
{
    extract($data, EXTR_SKIP);

    ob_start();
    require __DIR__ . '/../../frontend/views/emails/' . $name . '.php';
    $content = ob_get_clean();

    ob_start();
    require __DIR__ . '/../../frontend/views/emails/_layout.php';
    return (string) ob_get_clean();
}

/** Render frontend/views/<name>.php inside the main layout. */
function view(string $name, array $data = []): void
{
    $viewFile = __DIR__ . '/../../frontend/views/' . $name . '.php';
    if (!is_file($viewFile)) {
        throw new RuntimeException("View not found: {$name}");
    }

    extract($data, EXTR_SKIP);
    $flashes = get_flashes();
    $user    = current_user();

    require __DIR__ . '/../../frontend/views/layouts/header.php';
    require $viewFile;
    require __DIR__ . '/../../frontend/views/layouts/footer.php';

    clear_old();
}
