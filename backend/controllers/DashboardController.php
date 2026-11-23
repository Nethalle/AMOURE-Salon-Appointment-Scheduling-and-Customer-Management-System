<?php
// Dashboard: today's appointments and counts. Admin and staff see the same overview.

declare(strict_types=1);

class DashboardController
{
    public function admin(): void
    {
        $user = require_role(['admin']);
        $this->show($user, 'Admin dashboard');
    }

    public function staff(): void
    {
        $user = require_role(['admin', 'staff']);
        $this->show($user, 'Dashboard');
    }

    private function show(array $user, string $title): void
    {
        $today = date('Y-m-d');

        // Today's counts per status.
        $counts = ['pending' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0, 'no_show' => 0];
        $stmt = db()->prepare('SELECT status, COUNT(*) AS n FROM appointments WHERE appt_date = :d GROUP BY status');
        $stmt->execute(['d' => $today]);
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['status']] = (int) $row['n'];
        }

        // All pending requests (any date from today on) that still need confirming.
        $stmt = db()->prepare("SELECT COUNT(*) FROM appointments WHERE status = 'pending' AND appt_date >= :d");
        $stmt->execute(['d' => $today]);
        $pendingRequests = (int) $stmt->fetchColumn();

        // Today's schedule (not cancelled).
        $stmt = db()->prepare(
            "SELECT a.start_time, a.end_time, a.status, c.full_name AS customer, s.name AS service, st.full_name AS stylist
               FROM appointments a
               JOIN customers c  ON c.id  = a.customer_id
               JOIN services  s  ON s.id  = a.service_id
               JOIN stylists  st ON st.id = a.stylist_id
              WHERE a.appt_date = :d AND a.status <> 'cancelled'
              ORDER BY a.start_time"
        );
        $stmt->execute(['d' => $today]);

        view('staff/dashboard', [
            'title'           => $title,
            'user'            => $user,
            'counts'          => $counts,
            'pendingRequests' => $pendingRequests,
            'schedule'        => $stmt->fetchAll(),
            'dailyLimit'      => (int) setting('daily_appointment_limit'),
        ]);
    }
}
