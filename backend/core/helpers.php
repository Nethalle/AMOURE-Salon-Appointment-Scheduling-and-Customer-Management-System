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

/**
 * Full address of the system, e.g. http://localhost/AMOURE-.../ (used for links in emails).
 * Settings > Salon Profile can set it; otherwise it is worked out from the current page
 * and saved, so emails sent later by the reminder job (no browser) still have it.
 */
function base_url(): string
{
    $saved = trim(setting('site_url'));
    if ($saved !== '') {
        return rtrim($saved, '/') . '/';
    }
    if (empty($_SERVER['HTTP_HOST'])) {
        return '';
    }
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443';
    $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $base   = ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $dir . '/';
    try {
        db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)')
            ->execute(['k' => 'site_url', 'v' => $base]);
    } catch (Throwable $e) {
        // already saved by another request, or no database: fine
    }
    return $base;
}

/** Absolute link to a page, for emails: abs_url('customer/my-appointments', ['appt' => 5]). '' if unknown. */
function abs_url(string $page, array $params = []): string
{
    $base = base_url();
    return $base === '' ? '' : $base . url($page, $params);
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
        'home'     => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
        'bar'      => '<line x1="6" y1="20" x2="6" y2="14"/><line x1="12" y1="20" x2="12" y2="8"/><line x1="18" y1="20" x2="18" y2="4"/>',
        'shield'   => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'bell'     => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
        'chevron'  => '<polyline points="6 9 12 15 18 9"/>',
        'chev-left'  => '<polyline points="15 18 9 12 15 6"/>',
        'chev-right' => '<polyline points="9 18 15 12 9 6"/>',
        'plus'     => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'more'     => '<circle cx="12" cy="12" r="10"/><circle cx="8" cy="12" r=".6" fill="currentColor"/><circle cx="12" cy="12" r=".6" fill="currentColor"/><circle cx="16" cy="12" r=".6" fill="currentColor"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'edit'     => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'power'    => '<path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/>',
        'trend'    => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>',
        'trend-down' => '<polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/>',
        'check'    => '<polyline points="20 6 9 17 4 12"/>',
        'card'     => '<rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>',
        'message'  => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'thumbs'   => '<path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/>',
        'eye'      => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
        'x'        => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'globe'    => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'printer'  => '<polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
        'key'      => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/>',
        'palette'  => '<circle cx="13.5" cy="6.5" r=".8" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".8" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".8" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".8" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.93 0 1.65-.75 1.65-1.69 0-.44-.18-.83-.44-1.12-.29-.29-.44-.65-.44-1.13a1.64 1.64 0 0 1 1.67-1.67h2c3.05 0 5.56-2.5 5.56-5.55C22 6.01 17.46 2 12 2z"/>',
        'droplet'  => '<path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"/><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"/>',
        'waves'    => '<path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5c2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/>',
        'wind'     => '<path d="M17.7 7.7a2.5 2.5 0 1 1 1.8 4.3H2"/><path d="M9.6 4.6A2 2 0 1 1 11 8H2"/><path d="M12.6 19.4A2 2 0 1 0 14 16H2"/>',
        'hand'     => '<path d="M18 11V6a2 2 0 0 0-4 0v1"/><path d="M14 10V4a2 2 0 0 0-4 0v2"/><path d="M10 10.5V6a2 2 0 0 0-4 0v8"/><path d="M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 0 1 2.83-2.82L7 15"/>',
        'moon'     => '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>',
        'sun'      => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>',
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

/** Service categories in menu order => icon. Used by the admin, booking and home pages. */
const SERVICE_CATEGORIES = [
    'Haircut'               => 'scissors',
    'Hair Coloring'         => 'palette',
    'Hair Treatments'       => 'droplet',
    'Perming & Rebonding'   => 'waves',
    'Hair Styling & Makeup' => 'wind',
    'Hair Removal'          => 'sparkle',
    'Nail Care'             => 'hand',
];

/** Categories the admin manages separately (haircuts are priced by stylist level). */
const SEPARATE_CATEGORIES = ['Haircut'];

/** Group service rows by their 'category' column, in menu order (unknown categories go last as "Other"). */
function group_services(array $services): array
{
    $groups = array_fill_keys(array_keys(SERVICE_CATEGORIES), []);
    foreach ($services as $s) {
        $cat = isset(SERVICE_CATEGORIES[$s['category'] ?? '']) ? $s['category'] : 'Other';
        $groups[$cat][] = $s;
    }
    // Inside a category: cheapest first (Deluxe before Premium), then by name.
    foreach ($groups as &$rows) {
        usort($rows, static fn ($a, $b) => [(float) ($a['price'] ?? 0), $a['name']] <=> [(float) ($b['price'] ?? 0), $b['name']]);
    }
    unset($rows);
    return array_filter($groups);
}

/** <option>s for a service <select>, grouped in <optgroup>s by category. */
function service_options(array $services, int $selected = 0, bool $showDuration = false): string
{
    $html = '';
    foreach (group_services($services) as $cat => $rows) {
        $html .= '<optgroup label="' . e($cat) . '">';
        foreach ($rows as $s) {
            $label = $s['name'] . ($showDuration && isset($s['duration_minutes']) ? ' (' . fmt_duration((int) $s['duration_minutes']) . ')' : '');
            $html .= '<option value="' . e($s['id']) . '"' . ((int) $s['id'] === $selected ? ' selected' : '') . '>' . e($label) . '</option>';
        }
        $html .= '</optgroup>';
    }
    return $html;
}

/** <option>s for the category <select> in service forms. */
function category_options(string $selected = ''): string
{
    $html = '<option value="">Choose a category</option>';
    foreach (array_keys(SERVICE_CATEGORIES) as $cat) {
        $html .= '<option value="' . e($cat) . '"' . ($cat === $selected ? ' selected' : '') . '>' . e($cat) . '</option>';
    }
    return $html;
}

/** Service checkboxes grouped by category (stylist forms). $checked = selected ids. */
function service_checkboxes(array $services, array $checked): string
{
    $html = '';
    foreach (group_services($services) as $cat => $rows) {
        $html .= '<fieldset class="svc-checks"><legend>' . icon(category_icon($cat), 'icon icon-sm') . ' ' . e($cat)
               . ' <button type="button" class="link-btn" data-check-all>Select all</button></legend><div class="check-grid">';
        foreach ($rows as $s) {
            $html .= '<label><input type="checkbox" name="services[]" value="' . e($s['id']) . '"'
                   . (in_array((int) $s['id'], $checked, true) ? ' checked' : '') . '> ' . e($s['name']) . '</label>';
        }
        $html .= '</div></fieldset>';
    }
    return $html;
}

/** Photo for a category: services/<category>.jpg, or an older photo with a similar name. */
function category_photo(string $category): string
{
    $own = 'services/' . slugify($category) . '.jpg';
    $fallback = [
        'Hair Coloring'         => 'services/hair-color.jpg',
        'Hair Treatments'       => 'services/hair-treatment.jpg',
        'Perming & Rebonding'   => 'services/blow-dry.jpg',
        'Hair Styling & Makeup' => 'services/makeup.jpg',
        'Hair Removal'   => 'services/waxing.jpg',
        'Nail Care'      => 'services/manicure.jpg',
    ];
    return !photo_exists($own) && isset($fallback[$category]) && photo_exists($fallback[$category]) ? $fallback[$category] : $own;
}

function category_icon(string $category): string
{
    return SERVICE_CATEGORIES[$category] ?? 'sparkle';
}

/** Reference numbers shown to people (the database keeps the plain id). */
function appt_ref(int|string $id): string { return 'APT-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT); }
function cust_ref(int|string $id): string { return 'CUS-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT); }
function user_ref(int|string $id): string { return 'USR-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT); }

/** "APT-00012", "apt12", "#12" or "12" => 12 when the text starts with $prefix (or is only a number); else 0. */
function ref_to_id(string $text, string $prefix): int
{
    return preg_match('/^\s*(?:' . preg_quote($prefix, '/') . '\s*-?\s*|#)?0*(\d{1,9})\s*$/i', $text, $m) ? (int) $m[1] : 0;
}

/** A stylist's photo file under frontend/assets/img/, or null.
 *  Uploaded photos are stylists/stylist-<id>.<ext>; older ones are named after the stylist (stylists/<full-name>.jpg). */
function stylist_photo_file(array $st): ?string
{
    foreach (['jpg', 'png', 'webp'] as $ext) {
        if (isset($st['id']) && photo_exists("stylists/stylist-{$st['id']}.$ext")) {
            return "stylists/stylist-{$st['id']}.$ext";
        }
    }
    $named = 'stylists/' . slugify((string) $st['full_name']) . '.jpg';
    return photo_exists($named) ? $named : null;
}

/** <img> of the stylist, or a circle with their initials when there is no photo yet. */
function stylist_photo(array $st, string $class = ''): string
{
    $file = stylist_photo_file($st);
    if ($file) {
        return photo($file, (string) $st['full_name'], $class);
    }
    return '<div class="photo photo-initials ' . e($class) . '" role="img" aria-label="' . e($st['full_name']) . '"><span>'
        . e(initials((string) $st['full_name'])) . '</span></div>';
}

function photo_exists(string $file): bool
{
    return is_file(__DIR__ . '/../../frontend/assets/img/' . $file);
}

/** 'Maria Dela Cruz' => 'MC' */
function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
}

/** 30 => '30 min', 60 => '1 hr', 120 => '2 hrs', 90 => '1 hr 30 min' */
function fmt_duration(int $minutes): string
{
    if ($minutes < 60) {
        return $minutes . ' min';
    }
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    return $h . ($h === 1 ? ' hr' : ' hrs') . ($m ? ' ' . $m . ' min' : '');
}

/** Small "page 1 2 3 … 9" list for pagination. */
function page_numbers(int $current, int $last): array
{
    if ($last <= 7) {
        return range(1, max(1, $last));
    }
    $pages = [1];
    for ($i = max(2, $current - 1); $i <= min($last - 1, $current + 1); $i++) {
        $pages[] = $i;
    }
    $pages[] = $last;
    $out = [];
    $prev = 0;
    foreach (array_unique($pages) as $p) {
        if ($p - $prev > 1) {
            $out[] = '…';
        }
        $out[] = $p;
        $prev = $p;
    }
    return $out;
}

/** True when a form failed and its values were remembered (used to re-open "Add" dialogs). */
function has_old(): bool
{
    return !empty($_SESSION['old']);
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

/** Render frontend/views/emails/<name>.php inside the email layout and return the HTML. */
/** A picture inside an email (sent attached, so it shows even when the site is on localhost). */
function email_img(string $name, int $width, string $alt = '', string $style = ''): string
{
    return '<img src="cid:' . e($name) . '" width="' . $width . '" alt="' . e($alt) . '" style="display:block;border:0;outline:none;' . e($style) . '">';
}

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
