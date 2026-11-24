<?php
// Reports (admin only): appointments over a period, statuses, popular services, stylists, sources, ratings.
// Revenue is an estimate: completed appointments × the service's CURRENT price.

declare(strict_types=1);

require_once __DIR__ . '/../services/BookingRules.php';

class ReportController
{
    private const PRESETS = [
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'last_7'     => 'Last 7 days',
        'last_30'    => 'Last 30 days',
        'this_year'  => 'This year',
        'custom'     => 'Custom range',
    ];

    public function index(): void
    {
        require_role(['admin']);
        [$from, $to, $preset] = $this->period();

        $range = ['from' => $from, 'to' => $to];

        // Status counts.
        $counts = ['pending' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0, 'no_show' => 0];
        $stmt = db()->prepare('SELECT status, COUNT(*) AS n FROM appointments WHERE appt_date BETWEEN :from AND :to GROUP BY status');
        $stmt->execute($range);
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['status']] = (int) $row['n'];
        }
        $total = array_sum($counts);
        $finished = $counts['completed'] + $counts['no_show'];

        // Appointments per day (cancelled ones not counted), grouped by month for long ranges.
        $stmt = db()->prepare(
            "SELECT appt_date, COUNT(*) AS n FROM appointments
              WHERE appt_date BETWEEN :from AND :to AND status <> 'cancelled'
              GROUP BY appt_date"
        );
        $stmt->execute($range);
        $perDate = array_column($stmt->fetchAll(), 'n', 'appt_date');
        $series  = $this->buildSeries($from, $to, $perDate);

        // Services.
        $stmt = db()->prepare(
            "SELECT s.name, s.price,
                    COUNT(a.id) AS total,
                    SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) AS completed
               FROM appointments a
               JOIN services s ON s.id = a.service_id
              WHERE a.appt_date BETWEEN :from AND :to AND a.status <> 'cancelled'
              GROUP BY s.id, s.name, s.price
              ORDER BY total DESC, s.name"
        );
        $stmt->execute($range);
        $services = $stmt->fetchAll();
        $revenue = 0.0;
        foreach ($services as &$s) {
            $s['revenue'] = (int) $s['completed'] * (float) $s['price'];
            $revenue += $s['revenue'];
        }
        unset($s);

        // Stylists, with the average rating of their reviewed appointments in the period.
        $stmt = db()->prepare(
            "SELECT st.full_name,
                    COUNT(a.id) AS total,
                    SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN a.status = 'no_show'   THEN 1 ELSE 0 END) AS no_show,
                    AVG(CASE WHEN r.status <> 'removed' THEN r.rating END) AS avg_rating,
                    COUNT(CASE WHEN r.status <> 'removed' THEN r.id END) AS reviews
               FROM appointments a
               JOIN stylists st ON st.id = a.stylist_id
               LEFT JOIN reviews r ON r.appointment_id = a.id
              WHERE a.appt_date BETWEEN :from AND :to AND a.status <> 'cancelled'
              GROUP BY st.id, st.full_name
              ORDER BY total DESC, st.full_name"
        );
        $stmt->execute($range);
        $stylists = $stmt->fetchAll();

        // Booking sources.
        $sources = ['online' => 0, 'walk_in' => 0, 'phone' => 0];
        $stmt = db()->prepare(
            "SELECT source, COUNT(*) AS n FROM appointments
              WHERE appt_date BETWEEN :from AND :to AND status <> 'cancelled' GROUP BY source"
        );
        $stmt->execute($range);
        foreach ($stmt->fetchAll() as $row) {
            $sources[$row['source']] = (int) $row['n'];
        }

        // Ratings for appointments in the period (removed reviews excluded).
        $stmt = db()->prepare(
            "SELECT COUNT(r.id) AS n, AVG(r.rating) AS avg
               FROM reviews r JOIN appointments a ON a.id = r.appointment_id
              WHERE a.appt_date BETWEEN :from AND :to AND r.status <> 'removed'"
        );
        $stmt->execute($range);
        $rating = $stmt->fetch();

        // Revenue per day / month (completed appointments × current price).
        $stmt = db()->prepare(
            "SELECT a.appt_date, SUM(s.price) AS amount
               FROM appointments a JOIN services s ON s.id = a.service_id
              WHERE a.appt_date BETWEEN :from AND :to AND a.status = 'completed'
              GROUP BY a.appt_date"
        );
        $stmt->execute($range);
        $revenueSeries = $this->buildSeries($from, $to, array_column($stmt->fetchAll(), 'amount', 'appt_date'), true);

        // New customer profiles created in the period.
        $stmt = db()->prepare('SELECT COUNT(*) FROM customers WHERE created_at BETWEEN :from AND :to');
        $stmt->execute(['from' => $from . ' 00:00:00', 'to' => $to . ' 23:59:59']);
        $newCustomers = (int) $stmt->fetchColumn();

        view('admin/reports', [
            'revenueSeries' => $revenueSeries,
            'newCustomers'  => $newCustomers,
            'title'    => 'Reports',
            'from'     => $from,
            'to'       => $to,
            'preset'   => $preset,
            'presets'  => self::PRESETS,
            'counts'   => $counts,
            'total'    => $total,
            'completionRate' => $finished > 0 ? round($counts['completed'] / $finished * 100) : null,
            'series'   => $series,
            'services' => $services,
            'revenue'  => $revenue,
            'stylists' => $stylists,
            'sources'  => $sources,
            'rating'   => ['count' => (int) $rating['n'], 'avg' => $rating['n'] ? round((float) $rating['avg'], 1) : null],
        ]);
    }

    /** Download every appointment in the period as a CSV file (opens in Excel). */
    public function export(): void
    {
        require_role(['admin']);
        [$from, $to] = $this->period();

        $stmt = db()->prepare(
            'SELECT a.appt_date, a.start_time, a.end_time, c.full_name AS customer, c.phone, c.email,
                    s.name AS service, s.price, st.full_name AS stylist, a.status, a.source
               FROM appointments a
               JOIN customers c  ON c.id  = a.customer_id
               JOIN services  s  ON s.id  = a.service_id
               JOIN stylists  st ON st.id = a.stylist_id
              WHERE a.appt_date BETWEEN :from AND :to
              ORDER BY a.appt_date, a.start_time'
        );
        $stmt->execute(['from' => $from, 'to' => $to]);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="amoure-appointments-' . $from . '-to-' . $to . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel shows É and ₱ correctly
        fputcsv($out, ['Date', 'Start', 'End', 'Customer', 'Phone', 'Email', 'Service', 'Price', 'Stylist', 'Status', 'Source']);
        foreach ($stmt->fetchAll() as $r) {
            fputcsv($out, array_map([$this, 'csvSafe'], [
                $r['appt_date'], substr($r['start_time'], 0, 5), substr($r['end_time'], 0, 5),
                $r['customer'], $r['phone'] ?? '', $r['email'] ?? '',
                $r['service'], number_format((float) $r['price'], 2, '.', ''), $r['stylist'],
                str_replace('_', '-', $r['status']), str_replace('_', '-', $r['source']),
            ]));
        }
        fclose($out);
        exit;
    }

    // ---------- helpers ----------

    /** @return array{0: string, 1: string, 2: string} [from, to, preset] */
    private function period(): array
    {
        $preset = (string) ($_GET['preset'] ?? 'this_month');
        if (!isset(self::PRESETS[$preset])) {
            $preset = 'this_month';
        }

        $today = date('Y-m-d');
        [$from, $to] = match ($preset) {
            'last_month' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
            'last_7'     => [date('Y-m-d', strtotime('-6 days')), $today],
            'last_30'    => [date('Y-m-d', strtotime('-29 days')), $today],
            'this_year'  => [date('Y-01-01'), date('Y-12-31')],
            'custom'     => [(string) ($_GET['from'] ?? ''), (string) ($_GET['to'] ?? '')],
            default      => [date('Y-m-01'), date('Y-m-t')],
        };

        if (!BookingRules::isValidDate($from) || !BookingRules::isValidDate($to)) {
            if ($preset === 'custom') {
                flash('error', 'Choose a valid start and end date.');
            }
            return [date('Y-m-01'), date('Y-m-t'), 'this_month'];
        }
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        // Keep reports fast and readable: at most 2 years.
        if (strtotime($to) - strtotime($from) > 731 * 86400) {
            $from = date('Y-m-d', strtotime($to . ' -731 days'));
            flash('error', 'Reports cover up to 2 years. The start date was moved to ' . fmt_date($from) . '.');
        }
        return [$from, $to, $preset];
    }

    /** One bar per day, or per month when the range is longer than about 2 months. */
    private function buildSeries(string $from, string $to, array $perDate, bool $money = false): array
    {
        $byMonth = (strtotime($to) - strtotime($from)) > 62 * 86400;
        $series  = [];

        if ($byMonth) {
            $cursor = strtotime(date('Y-m-01', strtotime($from)));
            while ($cursor <= strtotime($to)) {
                $series[date('Y-m', $cursor)] = ['label' => date('M Y', $cursor), 'short' => date('M', $cursor), 'value' => 0];
                $cursor = strtotime('+1 month', $cursor);
            }
            foreach ($perDate as $date => $n) {
                $series[substr($date, 0, 7)]['value'] += $money ? (float) $n : (int) $n;
            }
        } else {
            for ($d = strtotime($from); $d <= strtotime($to); $d = strtotime('+1 day', $d)) {
                $key = date('Y-m-d', $d);
                $series[$key] = ['label' => fmt_date($key), 'short' => date('j', $d), 'value' => $money ? (float) ($perDate[$key] ?? 0) : (int) ($perDate[$key] ?? 0)];
            }
        }

        return ['byMonth' => $byMonth, 'points' => array_values($series), 'max' => max(1, ...array_column($series, 'value'))];
    }

    /** Stop spreadsheet formula injection: values starting with = + - @ are prefixed with '. */
    private function csvSafe(mixed $value): string
    {
        $value = (string) $value;
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }
}
