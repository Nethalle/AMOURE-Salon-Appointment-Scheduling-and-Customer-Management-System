<?php
// Activity Log (admin only): read-only list of everything accounts did in the system.
// There is no edit or delete here on purpose, and the database blocks both.

declare(strict_types=1);

class ActivityController
{
    public const CATEGORIES = [
        'appointments' => 'Appointments',
        'customers'    => 'Customers',
        'services'     => 'Services',
        'stylists'     => 'Stylists',
        'reviews'      => 'Reviews',
        'users'        => 'User accounts',
        'settings'     => 'Settings',
        'reports'      => 'Reports',
        'account'      => 'Login & password',
    ];

    private const PER_PAGE = 25;

    public function index(): void
    {
        require_role(['admin']);

        $f = [
            'role'     => (string) ($_GET['role'] ?? ''),
            'category' => (string) ($_GET['category'] ?? ''),
            'q'        => trim((string) ($_GET['q'] ?? '')),
            'from'     => (string) ($_GET['from'] ?? ''),
            'to'       => (string) ($_GET['to'] ?? ''),
            'p'        => max(1, (int) ($_GET['p'] ?? 1)),
        ];

        $where  = [];
        $params = [];
        if (in_array($f['role'], ['admin', 'staff', 'customer', 'guest'], true)) {
            $where[] = 'user_role = :role';
            $params['role'] = $f['role'];
        } else {
            $f['role'] = '';
        }
        if (isset(self::CATEGORIES[$f['category']])) {
            $where[] = 'category = :cat';
            $params['cat'] = $f['category'];
        } else {
            $f['category'] = '';
        }
        if ($f['q'] !== '') {
            $where[] = '(user_name LIKE :q1 OR details LIKE :q2)';
            $params['q1'] = $params['q2'] = '%' . $f['q'] . '%';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['from'])) {
            $where[] = 'created_at >= :from';
            $params['from'] = $f['from'] . ' 00:00:00';
        } else {
            $f['from'] = '';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['to'])) {
            $where[] = 'created_at <= :to';
            $params['to'] = $f['to'] . ' 23:59:59';
        } else {
            $f['to'] = '';
        }
        $sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $missing = false;
        $logs = [];
        $total = 0;
        try {
            $stmt = db()->prepare('SELECT COUNT(*) FROM activity_logs' . $sql);
            $stmt->execute($params);
            $total = (int) $stmt->fetchColumn();
            $pages = max(1, (int) ceil($total / self::PER_PAGE));
            $f['p'] = min($f['p'], $pages);

            $stmt = db()->prepare(
                'SELECT * FROM activity_logs' . $sql . ' ORDER BY created_at DESC, id DESC
                  LIMIT ' . self::PER_PAGE . ' OFFSET ' . (($f['p'] - 1) * self::PER_PAGE)
            );
            $stmt->execute($params);
            $logs = $stmt->fetchAll();
        } catch (PDOException $e) {
            $missing = true; // activity-log.sql not imported yet
        }

        view('admin/activity', [
            'title'      => 'Activity Log',
            'logs'       => $logs,
            'filters'    => $f,
            'total'      => $total,
            'pages'      => max(1, (int) ceil($total / self::PER_PAGE)),
            'perPage'    => self::PER_PAGE,
            'categories' => self::CATEGORIES,
            'missing'    => $missing,
        ]);
    }
}
