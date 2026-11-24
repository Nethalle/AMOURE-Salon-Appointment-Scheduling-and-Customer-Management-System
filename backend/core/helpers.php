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

/** 'Jessica Ramos Cruz' => 'Jessica C.' */
function short_name(string $fullName): string
{
    $parts = preg_split('/\s+/', trim($fullName)) ?: [];
    if (count($parts) < 2) {
        return $parts[0] ?? '';
    }
    return $parts[0] . ' ' . mb_strtoupper(mb_substr(end($parts), 0, 1)) . '.';
}

/** 4 => '★★★★☆' */
function stars(int $rating): string
{
    $rating = max(0, min(5, $rating));
    return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
}

/**
 * A photo from frontend/assets/img/, or a labelled placeholder if the file isn't there yet.
 * Drop a photo with that file name into the folder and it appears automatically.
 */
function photo(string $file, string $alt, string $class = ''): string
{
    $path = __DIR__ . '/../../frontend/assets/img/' . $file;
    if (is_file($path)) {
        return '<img src="frontend/assets/img/' . e($file) . '?v=' . filemtime($path) . '" alt="' . e($alt) . '" class="photo ' . e($class) . '" loading="lazy">';
    }
    return '<div class="photo photo-placeholder ' . e($class) . '" role="img" aria-label="' . e($alt) . '">'
        . icon('image') . '<span>' . e($alt) . '</span><small>' . e('frontend/assets/img/' . $file) . '</small></div>';
}

/** Small inline line icons (24×24, stroke = text color). */
function icon(string $name, string $class = 'icon'): string
{
    $paths = [
        'scissors' => '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/>',
        'sparkle'  => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/><path d="M19 15l.7 2 2 .7-2 .7-.7 2-.7-2-2-.7 2-.7z"/>',
        'heart'    => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78z"/>',
        'gem'      => '<path d="M6 3h12l4 6-10 12L2 9z"/><path d="M2 9h20"/><path d="M12 21 8 9l4-6 4 6z"/>',
        'leaf'     => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
        'flower'   => '<path d="M12 21c-4-2-7-5-7-9 2 0 5 1 7 4 2-3 5-4 7-4 0 4-3 7-7 9z"/><path d="M12 16c-1.5-2-2-4-2-6s1-4 2-5c1 1 2 3 2 5s-.5 4-2 6z"/>',
        'face'     => '<circle cx="12" cy="12" r="9"/><path d="M8.5 14.5s1.3 1.5 3.5 1.5 3.5-1.5 3.5-1.5"/><line x1="9" y1="9.5" x2="9.01" y2="9.5"/><line x1="15" y1="9.5" x2="15.01" y2="9.5"/>',
        'polish'   => '<rect x="10" y="2" width="4" height="6" rx="1"/><path d="M8 10h8a1 1 0 0 1 1 1v9a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2v-9a1 1 0 0 1 1-1z"/>',
        'brush'    => '<path d="M9.06 11.9 17.5 3.5a2.12 2.12 0 0 1 3 3l-8.4 8.44"/><path d="M7.07 14.94c-1.66 0-3 1.35-3 3.02 0 1.33-2.5 1.52-2 2.02 1.08 1.1 2.49 2.02 4 2.02 2.2 0 4-1.8 4-4.04a3.01 3.01 0 0 0-3-3.02z"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'user'     => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'users'    => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'phone'    => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'mail'     => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/>',
        'pin'      => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'clock'    => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'arrow'    => '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
        'image'    => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>',
        'award'    => '<circle cx="12" cy="8" r="6"/><path d="M15.48 12.89 17 22l-5-3-5 3 1.52-9.11"/>',
        'star'     => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        'instagram'=> '<rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>',
        'facebook' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
        'tiktok'   => '<path d="M16 3c.4 2.3 1.9 3.8 4 4v3.2c-1.5 0-2.9-.4-4-1.2V15a6 6 0 1 1-6-6v3.2A2.8 2.8 0 1 0 12.8 15V3z"/>',
    ];
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . ($paths[$name] ?? $paths['sparkle']) . '</svg>';
}

/** Pick an icon for a service from its name. */
function service_icon(string $name): string
{
    $n = mb_strtolower($name);
    return match (true) {
        str_contains($n, 'nail') || str_contains($n, 'manicure') || str_contains($n, 'pedicure') => 'polish',
        str_contains($n, 'facial') || str_contains($n, 'skin')                                   => 'face',
        str_contains($n, 'makeup') || str_contains($n, 'make-up') || str_contains($n, 'brow') || str_contains($n, 'lash') => 'brush',
        str_contains($n, 'wax')                                                                  => 'leaf',
        str_contains($n, 'spa') || str_contains($n, 'massage')                                   => 'flower',
        str_contains($n, 'hair') || str_contains($n, 'cut') || str_contains($n, 'color') || str_contains($n, 'blow') => 'scissors',
        default                                                                                  => 'sparkle',
    };
}

/** 'Hair Color' => 'hair-color' (for photo file names) */
function slugify(string $text): string
{
    return trim(preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($text)) ?? '', '-');
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
