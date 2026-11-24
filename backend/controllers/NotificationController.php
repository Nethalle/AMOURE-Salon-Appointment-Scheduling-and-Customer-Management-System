<?php
// Bell notifications: mark all as read (called when the bell is opened, or by the button).

declare(strict_types=1);

require_once __DIR__ . '/../services/NotificationFeed.php';

class NotificationController
{
    public function read(): void
    {
        $user = require_login();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(home_for_role($user['role']));
        }
        csrf_check();
        NotificationFeed::markRead((int) $user['id']);

        // Opened by the bell in the background: no page needed.
        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') {
            http_response_code(204);
            exit;
        }

        // Button: go back to the page the user was on.
        parse_str((string) ($_POST['back'] ?? ''), $back);
        $page = is_string($back['page'] ?? null) ? $back['page'] : home_for_role($user['role']);
        unset($back['page']);
        redirect($page, $back);
    }
}
