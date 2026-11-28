<?php
// Dashboard: today's appointments and counts. Admin and staff see the same overview.

declare(strict_types=1);

class DashboardController
{
    public function admin(): void
    {
        $user = require_role(['admin']);
        $this->show($user, 'Dashboard');
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
            "SELECT a.id, a.start_time, a.end_time, a.status, c.full_name AS customer, s.name AS service, st.full_name AS stylist
               FROM appointments a
               JOIN customers c  ON c.id  = a.customer_id
               JOIN services  s  ON s.id  = a.service_id
               JOIN stylists  st ON st.id = a.stylist_id
              WHERE a.appt_date = :d AND a.status <> 'cancelled'
              ORDER BY a.start_time"
        );
        $stmt->execute(['d' => $today]);
        $schedule = $stmt->fetchAll();

        // Revenue from completed appointments: today vs yesterday.
        $revenue = db()->prepare(
            "SELECT COALESCE(SUM(s.price), 0) FROM appointments a JOIN services s ON s.id = a.service_id
              WHERE a.appt_date = :d AND a.status = 'completed'"
        );
        $revenue->execute(['d' => $today]);
        $revToday = (float) $revenue->fetchColumn();
        $revenue->execute(['d' => date('Y-m-d', strtotime('-1 day'))]);
        $revYesterday = (float) $revenue->fetchColumn();

        // Expected revenue still to come today (confirmed bookings).
        $stmt = db()->prepare(
            "SELECT COALESCE(SUM(s.price), 0) FROM appointments a JOIN services s ON s.id = a.service_id
              WHERE a.appt_date = :d AND a.status = 'confirmed'"
        );
        $stmt->execute(['d' => $today]);
        $revExpected = (float) $stmt->fetchColumn();

        // Bookings per day for the last 14 days (cancelled / no-show not counted).
        $start = date('Y-m-d', strtotime('-13 days'));
        $stmt = db()->prepare(
            "SELECT appt_date, COUNT(*) AS n FROM appointments
              WHERE appt_date BETWEEN :a AND :b AND status IN ('pending','confirmed','completed')
              GROUP BY appt_date"
        );
        $stmt->execute(['a' => $start, 'b' => $today]);
        $perDay = [];
        foreach ($stmt->fetchAll() as $row) {
            $perDay[$row['appt_date']] = (int) $row['n'];
        }
        $chart = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $chart[$d] = $perDay[$d] ?? 0;
        }

        // Quick overview: this month so far vs the same days last month.
        $monthStart = date('Y-m-01');
        $lastStart  = date('Y-m-01', strtotime('first day of last month'));
        $lastEnd    = date('Y-m-d', min(strtotime(date('Y-m-t', strtotime($lastStart))), strtotime('-1 month')));
        $overview   = [
            'appointments' => $this->countAppointments($monthStart, $today),
            'appointmentsBefore' => $this->countAppointments($lastStart, $lastEnd),
            'customers'    => $this->countNewCustomers($monthStart, $today),
            'customersBefore' => $this->countNewCustomers($lastStart, $lastEnd),
            'revenue'      => $this->sumRevenue($monthStart, $today),
            'revenueBefore' => $this->sumRevenue($lastStart, $lastEnd),
        ];
        $stmt = db()->query("SELECT COUNT(*) AS n, AVG(rating) AS avg FROM reviews WHERE status = 'approved'");
        $rating = $stmt->fetch() ?: ['n' => 0, 'avg' => null];
        $overview['ratingCount'] = (int) $rating['n'];
        $overview['rating']      = $rating['avg'] !== null ? (float) $rating['avg'] : null;

        view('staff/dashboard', [
            'overview'        => $overview,
            'title'           => $title,
            'user'            => $user,
            'counts'          => $counts,
            'pendingRequests' => $pendingRequests,
            'schedule'        => $schedule,
            'dailyLimit'      => (int) setting('daily_appointment_limit'),
            'revToday'        => $revToday,
            'revYesterday'    => $revYesterday,
            'revExpected'     => $revExpected,
            'chart'           => $chart,
        ]);
    }

    /** Bookings in a date range (cancelled ones not counted). */
    private function countAppointments(string $from, string $to): int
    {
        $stmt = db()->prepare("SELECT COUNT(*) FROM appointments WHERE appt_date BETWEEN :a AND :b AND status <> 'cancelled'");
        $stmt->execute(['a' => $from, 'b' => $to]);
        return (int) $stmt->fetchColumn();
    }

    /** New customer profiles (not admin/staff accounts) created in a date range. */
    private function countNewCustomers(string $from, string $to): int
    {
        $stmt = db()->prepare("SELECT COUNT(*) FROM customers c LEFT JOIN users u ON u.id = c.user_id
              WHERE (c.user_id IS NULL OR u.role = 'customer') AND c.created_at BETWEEN :a AND :b");
        $stmt->execute(['a' => $from . ' 00:00:00', 'b' => $to . ' 23:59:59']);
        return (int) $stmt->fetchColumn();
    }

    /** Revenue from completed appointments in a date range. */
    private function sumRevenue(string $from, string $to): float
    {
        $stmt = db()->prepare("SELECT COALESCE(SUM(s.price), 0) FROM appointments a JOIN services s ON s.id = a.service_id
              WHERE a.appt_date BETWEEN :a AND :b AND a.status = 'completed'");
        $stmt->execute(['a' => $from, 'b' => $to]);
        return (float) $stmt->fetchColumn();
    }
}
