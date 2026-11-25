<?php
// Activity log: a permanent record of what each account did in the system.
// Rows can only be added. The database itself blocks UPDATE and DELETE on this table
// (see database/activity-log.sql), and the app has no screen to change or remove them.

declare(strict_types=1);

/**
 * Record one action.
 * @param string $category  appointments | customers | services | stylists | reviews | users | settings | account | reports
 * @param string $action    short code, e.g. "confirm", "create", "login"
 * @param string $details   readable sentence shown in the log
 * @param array|null $actor who did it (defaults to the logged-in user)
 */
function log_activity(string $category, string $action, string $details, ?array $actor = null): void
{
    $actor ??= current_user();
    try {
        db()->prepare(
            'INSERT INTO activity_logs (user_id, user_name, user_role, category, action, details, ip_address, created_at)
             VALUES (:uid, :name, :role, :cat, :action, :details, :ip, :at)'
        )->execute([
            'uid'     => $actor['id'] ?? null,
            'name'    => $actor['name'] ?? 'Guest',
            'role'    => $actor['role'] ?? 'guest',
            'cat'     => $category,
            'action'  => $action,
            'details' => mb_substr($details, 0, 500),
            'ip'      => substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'cli'), 0, 45),
            'at'      => date('Y-m-d H:i:s'),
        ]);
    } catch (Throwable $e) {
        // Never stop the user's action because logging failed (e.g. table not created yet).
        error_log('[AMOURE] Activity log failed: ' . $e->getMessage());
    }
}

/** Show a success message AND write it to the activity log. */
function logged_flash(string $category, string $action, string $message): void
{
    log_activity($category, $action, $message);
    flash('success', $message);
}
