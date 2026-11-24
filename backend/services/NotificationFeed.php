<?php
// In-app notifications (the bell). Built from existing data, so no extra table is needed:
//   admin/staff: new online booking requests, cancellations, new reviews (admin)
//   customer:    confirmed / cancelled / completed / no-show updates and tomorrow's reminder
// "Read" = the time the user last opened the bell, saved in the settings table as notif_seen_<user id>.

declare(strict_types=1);

class NotificationFeed
{
    private const DAYS  = 14;   // how far back to look
    private const LIMIT = 15;   // how many to show

    /**
     * @return array{items: array<int, array{icon:string, text:string, time:string, link:string, unread:bool}>, unread:int}
     */
    public static function for(array $user): array
    {
        $since = date('Y-m-d H:i:s', strtotime('-' . self::DAYS . ' days'));
        $seen  = self::seenAt((int) $user['id']);

        $items = $user['role'] === 'customer'
            ? self::customerItems((int) $user['id'], $since)
            : self::staffItems($user['role'] === 'admin', $since);

        usort($items, static fn ($a, $b) => strcmp($b['time'], $a['time']));
        $items = array_slice($items, 0, self::LIMIT);

        $unread = 0;
        foreach ($items as &$item) {
            $item['unread'] = $seen === '' || $item['time'] > $seen;
            $unread += $item['unread'] ? 1 : 0;
        }
        unset($item);

        return ['items' => $items, 'unread' => $unread];
    }

    /** Mark everything up to now as read. */
    public static function markRead(int $userId): void
    {
        $key = 'notif_seen_' . $userId;
        $now = date('Y-m-d H:i:s');
        $pdo = db();
        $exists = $pdo->prepare('SELECT 1 FROM settings WHERE setting_key = :k');
        $exists->execute(['k' => $key]);
        if ($exists->fetchColumn()) {
            $pdo->prepare('UPDATE settings SET setting_value = :v WHERE setting_key = :k')->execute(['k' => $key, 'v' => $now]);
        } else {
            $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)')->execute(['k' => $key, 'v' => $now]);
        }
    }

    /** "5 min ago", "3 hrs ago", "Yesterday", "Nov 20" */
    public static function ago(string $time): string
    {
        $diff = time() - strtotime($time);
        return match (true) {
            $diff < 60     => 'Just now',
            $diff < 3600   => floor($diff / 60) . ' min ago',
            $diff < 86400  => floor($diff / 3600) . (floor($diff / 3600) == 1 ? ' hr ago' : ' hrs ago'),
            $diff < 172800 => 'Yesterday',
            default        => date('M j', strtotime($time)),
        };
    }

    // ---------- feeds ----------

    private static function staffItems(bool $isAdmin, string $since): array
    {
        $items = [];
        $base = 'SELECT a.id, a.appt_date, a.start_time, a.status, a.created_at, a.updated_at,
                        c.full_name AS customer, s.name AS service
                   FROM appointments a
                   JOIN customers c ON c.id = a.customer_id
                   JOIN services  s ON s.id = a.service_id ';

        // New online booking requests.
        $stmt = db()->prepare($base . "WHERE a.source = 'online' AND a.created_at >= :since ORDER BY a.created_at DESC LIMIT 20");
        $stmt->execute(['since' => $since]);
        foreach ($stmt->fetchAll() as $a) {
            $still = $a['status'] === 'pending';
            $items[] = [
                'icon' => 'calendar',
                'text' => "<b>" . e($a['customer']) . "</b> requested " . e($a['service']) . ' on ' . e(self::when($a))
                        . ($still ? ' · <em>needs confirming</em>' : ''),
                'time' => $a['created_at'],
                'link' => url('staff/appointments', $still ? ['tab' => 'pending'] : ['date' => $a['appt_date']]),
            ];
        }

        // Cancellations.
        $stmt = db()->prepare($base . "WHERE a.status = 'cancelled' AND a.updated_at >= :since ORDER BY a.updated_at DESC LIMIT 20");
        $stmt->execute(['since' => $since]);
        foreach ($stmt->fetchAll() as $a) {
            $items[] = [
                'icon' => 'x',
                'text' => "<b>" . e($a['customer']) . "</b>'s " . e($a['service']) . ' on ' . e(self::when($a)) . ' was cancelled',
                'time' => $a['updated_at'],
                'link' => url('staff/appointments', ['tab' => 'cancelled']),
            ];
        }

        // New reviews (admin only).
        if ($isAdmin) {
            $stmt = db()->prepare(
                "SELECT r.rating, r.status, r.created_at, c.full_name AS customer
                   FROM reviews r
                   JOIN appointments a ON a.id = r.appointment_id
                   JOIN customers c    ON c.id = a.customer_id
                  WHERE r.created_at >= :since ORDER BY r.created_at DESC LIMIT 20"
            );
            $stmt->execute(['since' => $since]);
            foreach ($stmt->fetchAll() as $r) {
                $items[] = [
                    'icon' => 'star',
                    'text' => "<b>" . e($r['customer']) . '</b> left a ' . (int) $r['rating'] . '★ review'
                            . ($r['status'] === 'pending' ? ' · <em>waiting for approval</em>' : ''),
                    'time' => $r['created_at'],
                    'link' => url('admin/reviews', $r['status'] === 'pending' ? ['status' => 'pending'] : []),
                ];
            }
        }

        return $items;
    }

    private static function customerItems(int $userId, string $since): array
    {
        $items = [];
        $stmt = db()->prepare(
            "SELECT a.id, a.appt_date, a.start_time, a.status, a.created_at, a.updated_at, s.name AS service,
                    r.id AS review_id
               FROM appointments a
               JOIN customers c ON c.id = a.customer_id
               JOIN services  s ON s.id = a.service_id
               LEFT JOIN reviews r ON r.appointment_id = a.id
              WHERE c.user_id = :u AND (a.updated_at >= :since1 OR a.created_at >= :since2)
              ORDER BY a.updated_at DESC
              LIMIT 30"
        );
        $stmt->execute(['u' => $userId, 'since1' => $since, 'since2' => $since]);

        foreach ($stmt->fetchAll() as $a) {
            $svc  = e($a['service']);
            $when = e(self::when($a));
            $link = url('customer/my-appointments');

            // Status changes made by the salon (updated after it was booked).
            $changed = strtotime($a['updated_at']) - strtotime($a['created_at']) > 5;
            [$icon, $text] = match (true) {
                $a['status'] === 'pending'               => ['clock', "Your request for <b>$svc</b> on $when was received. We'll confirm it soon."],
                $a['status'] === 'confirmed' && $changed => ['check', "Your <b>$svc</b> on $when is <b>confirmed</b>. See you!"],
                $a['status'] === 'confirmed'             => ['check', "Your <b>$svc</b> on $when is booked."],
                $a['status'] === 'cancelled'             => ['x', "Your <b>$svc</b> on $when was <b>cancelled</b>."],
                $a['status'] === 'completed'             => ['star', "Thanks for visiting! How was your <b>$svc</b>?" . ($a['review_id'] ? '' : ' <em>Leave a review</em>')],
                default                                  => ['clock', "You missed your <b>$svc</b> on $when."],
            };
            if ($a['status'] === 'completed' && !$a['review_id']) {
                $link = url('customer/review', ['id' => $a['id']]);
            }
            $items[] = [
                'icon' => $icon,
                'text' => $text,
                'time' => $a['status'] === 'pending' ? $a['created_at'] : $a['updated_at'],
                'link' => $link,
            ];
        }

        // Reminder for confirmed visits today or tomorrow.
        $stmt = db()->prepare(
            "SELECT a.appt_date, a.start_time, s.name AS service
               FROM appointments a
               JOIN customers c ON c.id = a.customer_id
               JOIN services  s ON s.id = a.service_id
              WHERE c.user_id = :u AND a.status = 'confirmed' AND a.appt_date IN (:d1, :d2)"
        );
        $today = date('Y-m-d');
        $stmt->execute(['u' => $userId, 'd1' => $today, 'd2' => date('Y-m-d', strtotime('+1 day'))]);
        foreach ($stmt->fetchAll() as $a) {
            if (strtotime("{$a['appt_date']} {$a['start_time']}") < time()) {
                continue;
            }
            $day = $a['appt_date'] === $today ? 'today' : 'tomorrow';
            $items[] = [
                'icon' => 'bell',
                'text' => 'Reminder: your <b>' . e($a['service']) . "</b> is $day at " . e(fmt_time($a['start_time'])) . '.',
                'time' => date('Y-m-d H:i:s', strtotime($a['appt_date'] . ' -1 day 08:00')),
                'link' => url('customer/my-appointments'),
            ];
        }

        return $items;
    }

    private static function seenAt(int $userId): string
    {
        $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = :k');
        $stmt->execute(['k' => 'notif_seen_' . $userId]);
        return (string) ($stmt->fetchColumn() ?: '');
    }

    private static function when(array $a): string
    {
        return date('M j', strtotime($a['appt_date'])) . ', ' . fmt_time($a['start_time']);
    }
}
