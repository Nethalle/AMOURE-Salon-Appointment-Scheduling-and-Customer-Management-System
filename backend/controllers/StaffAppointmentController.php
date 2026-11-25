<?php
// Appointment Scheduling + Status Monitoring: staff and admin side.

declare(strict_types=1);

require_once __DIR__ . '/../services/BookingRules.php';
require_once __DIR__ . '/../services/NotificationService.php';

class StaffAppointmentController
{
    /** Allowed status changes: action => [from statuses, new status]. */
    private const ACTIONS = [
        'confirm'  => [['pending'],              'confirmed'],
        'cancel'   => [['pending', 'confirmed'], 'cancelled'],
        'complete' => [['confirmed'],            'completed'],
        'no_show'  => [['confirmed'],            'no_show'],
    ];

    /** Tabs on the appointments page: key => [label, statuses]. */
    private const TABS = [
        'all'       => ['All', []],
        'today'     => ['Today', []],
        'pending'   => ['Pending', ['pending']],
        'confirmed' => ['Confirmed', ['confirmed']],
        'completed' => ['Completed', ['completed']],
        'cancelled' => ['Cancelled', ['cancelled', 'no_show']],
    ];

    private const PER_PAGE = 10;

    /** List appointments: tabs, filters, pagination and a month calendar. */
    public function index(): void
    {
        require_role(['admin', 'staff']);

        $today = date('Y-m-d');
        $f = [
            'tab'     => (string) ($_GET['tab'] ?? 'all'),
            'date'    => (string) ($_GET['date'] ?? ''),
            'service' => (int) ($_GET['service'] ?? 0),
            'stylist' => (int) ($_GET['stylist'] ?? 0),
            'q'       => trim((string) ($_GET['q'] ?? '')),
            'p'       => max(1, (int) ($_GET['p'] ?? 1)),
        ];
        if (!isset(self::TABS[$f['tab']])) {
            $f['tab'] = 'all';
        }
        if (!BookingRules::isValidDate($f['date'])) {
            $f['date'] = '';
        }

        // Filters shared by every tab (date, service, stylist, search).
        $where  = [];
        $params = [];
        if ($f['service'] > 0) {
            $where[] = 'a.service_id = :service';
            $params['service'] = $f['service'];
        }
        if ($f['stylist'] > 0) {
            $where[] = 'a.stylist_id = :stylist';
            $params['stylist'] = $f['stylist'];
        }
        if ($f['q'] !== '') {
            $where[] = '(c.full_name LIKE :q1 OR c.phone LIKE :q2 OR c.email LIKE :q3 OR a.id = :qid)';
            $like = '%' . $f['q'] . '%';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like, 'qid' => ref_to_id($f['q'], 'APT') ?: -1];
        }
        $from = ' FROM appointments a
                  JOIN customers c  ON c.id  = a.customer_id
                  JOIN services  s  ON s.id  = a.service_id
                  JOIN stylists  st ON st.id = a.stylist_id';

        // Tab counts (with the date filter, but before the tab's own status filter).
        $countWhere  = $where;
        $countParams = $params;
        if ($f['date'] !== '') {
            $countWhere[] = 'a.appt_date = :date';
            $countParams['date'] = $f['date'];
        }
        $stmt = db()->prepare('SELECT a.status, COUNT(*) AS n' . $from
            . ($countWhere ? ' WHERE ' . implode(' AND ', $countWhere) : '') . ' GROUP BY a.status');
        $stmt->execute($countParams);
        $byStatus = array_fill_keys(['pending', 'confirmed', 'completed', 'cancelled', 'no_show'], 0);
        foreach ($stmt->fetchAll() as $row) {
            $byStatus[$row['status']] = (int) $row['n'];
        }
        $todayWhere = $where;
        $todayWhere[] = 'a.appt_date = :today';
        $stmt = db()->prepare('SELECT COUNT(*)' . $from . ' WHERE ' . implode(' AND ', $todayWhere));
        $stmt->execute($params + ['today' => $today]);

        $counts = [
            'all'       => array_sum($byStatus),
            'today'     => (int) $stmt->fetchColumn(),
            'pending'   => $byStatus['pending'],
            'confirmed' => $byStatus['confirmed'],
            'completed' => $byStatus['completed'],
            'cancelled' => $byStatus['cancelled'] + $byStatus['no_show'],
        ];

        // The list itself.
        $listWhere  = $where;
        $listParams = $params;
        $day = $f['tab'] === 'today' ? $today : $f['date'];
        if ($day !== '') {
            $listWhere[] = 'a.appt_date = :day';
            $listParams['day'] = $day;
        }
        $statuses = self::TABS[$f['tab']][1];
        if ($statuses) {
            $in = [];
            foreach ($statuses as $i => $st) {
                $in[] = ':st' . $i;
                $listParams['st' . $i] = $st;
            }
            $listWhere[] = 'a.status IN (' . implode(',', $in) . ')';
        }
        $whereSql = $listWhere ? ' WHERE ' . implode(' AND ', $listWhere) : '';

        $stmt = db()->prepare('SELECT COUNT(*)' . $from . $whereSql);
        $stmt->execute($listParams);
        $total = (int) $stmt->fetchColumn();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $f['p'] = min($f['p'], $pages);

        // Work still to do (pending/confirmed) is listed soonest first; history newest first.
        $order = in_array($f['tab'], ['pending', 'confirmed', 'today'], true) || $day !== ''
            ? 'a.appt_date ASC, a.start_time ASC'
            : 'a.appt_date DESC, a.start_time ASC';

        $stmt = db()->prepare(
            'SELECT a.id, a.appt_date, a.start_time, a.end_time, a.status, a.source,
                    c.id AS customer_id, c.full_name AS customer, c.phone, c.email,
                    s.name AS service, st.full_name AS stylist'
            . $from . $whereSql
            . ' ORDER BY ' . $order
            . ' LIMIT ' . self::PER_PAGE . ' OFFSET ' . (($f['p'] - 1) * self::PER_PAGE)
        );
        $stmt->execute($listParams);

        $now = time();
        $appointments = array_map(function (array $a) use ($now) {
            $started = strtotime("{$a['appt_date']} {$a['start_time']}") <= $now;
            $a['actions'] = $this->allowedActions($a['status'], $started);
            $a['can_reschedule'] = !$started && in_array($a['status'], ['pending', 'confirmed'], true);
            return $a;
        }, $stmt->fetchAll());

        // Month calendar: ?m=2026-11 (defaults to the selected day's month).
        $m = (string) ($_GET['m'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}$/', $m)) {
            $m = substr($day !== '' ? $day : $today, 0, 7);
        }
        $first = $m . '-01';
        $stmt = db()->prepare(
            "SELECT DISTINCT appt_date FROM appointments
              WHERE appt_date BETWEEN :a AND :b AND status IN ('pending','confirmed','completed')"
        );
        $stmt->execute(['a' => $first, 'b' => date('Y-m-t', strtotime($first))]);
        $busyDays = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));

        view('staff/appointments', [
            'title'        => 'Appointments',
            'appointments' => $appointments,
            'filters'      => $f,
            'tabs'         => array_map(fn ($t) => $t[0], self::TABS),
            'counts'       => $counts,
            'total'        => $total,
            'pages'        => $pages,
            'perPage'      => self::PER_PAGE,
            'month'        => $m,
            'busyDays'     => $busyDays,
            'services'     => db()->query('SELECT id, name, category FROM services ORDER BY name')->fetchAll(),
            'stylists'     => db()->query('SELECT id, full_name FROM stylists ORDER BY full_name')->fetchAll(),
            'back'         => http_build_query(array_filter([
                'tab' => $f['tab'], 'date' => $f['date'], 'service' => $f['service'] ?: null,
                'stylist' => $f['stylist'] ?: null, 'q' => $f['q'], 'p' => $f['p'] > 1 ? $f['p'] : null, 'm' => $_GET['m'] ?? null,
            ], fn ($v) => $v !== null && $v !== '')),
        ]);
    }

    /** Confirm / cancel / complete / no-show. */
    public function status(): void
    {
        require_role(['admin', 'staff']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('staff/appointments');
        }
        csrf_check();

        $id     = (int) ($_POST['id'] ?? 0);
        $action = (string) ($_POST['action'] ?? '');
        $appt   = $this->find($id);

        if (!$appt || !isset(self::ACTIONS[$action])) {
            flash('error', 'Appointment not found.');
            $this->back();
        }

        $started = strtotime("{$appt['appt_date']} {$appt['start_time']}") <= time();
        if (!in_array($action, $this->allowedActions($appt['status'], $started), true)) {
            if (in_array($action, ['complete', 'no_show'], true) && $appt['status'] === 'confirmed' && !$started) {
                flash('error', 'An appointment can only be marked completed or no-show once its start time has passed.');
            } else {
                flash('error', 'That change is not allowed for a ' . str_replace('_', '-', $appt['status']) . ' appointment.');
            }
            $this->back();
        }

        [, $newStatus] = self::ACTIONS[$action];
        db()->prepare('UPDATE appointments SET status = :status WHERE id = :id')
            ->execute(['status' => $newStatus, 'id' => $id]);

        if ($newStatus === 'confirmed') {
            $this->emailNote(NotificationService::appointmentChanged($id, 'confirmation'));
        } elseif ($newStatus === 'cancelled') {
            $this->emailNote(NotificationService::appointmentChanged($id, 'cancellation'));
        }

        $labels = [
            'confirmed' => 'confirmed',
            'cancelled' => 'cancelled',
            'completed' => 'marked as completed',
            'no_show'   => 'marked as no-show',
        ];
        logged_flash('appointments', $action, "{$appt['customer']}'s appointment on " . fmt_date($appt['appt_date']) . ' at ' . fmt_time($appt['start_time']) . " was {$labels[$newStatus]}.");
        $this->back();
    }

    /** Book a walk-in or phone customer. Saved as confirmed. */
    public function create(): void
    {
        $user = require_role(['admin', 'staff']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $this->saveNew($user);
            return;
        }

        $pick = $this->readPicker((int) ($_GET['service'] ?? 0), (int) ($_GET['stylist'] ?? 0), (string) ($_GET['date'] ?? date('Y-m-d')));

        view('staff/appointment-new', [
            'title'     => 'New appointment',
            'pick'      => $pick,
            'services'  => db()->query('SELECT id, name, category, duration_minutes FROM services WHERE is_active = 1 ORDER BY name')->fetchAll(),
            'stylists'  => $this->stylistsWithServices(),
            'customers' => db()->query(
                "SELECT c.id, c.full_name, c.phone FROM customers c LEFT JOIN users u ON u.id = c.user_id
                  WHERE c.user_id IS NULL OR u.role = 'customer' ORDER BY c.full_name"
            )->fetchAll(),
        ]);
    }

    /** Move an upcoming appointment to another stylist, date or time. */
    public function reschedule(): void
    {
        require_role(['admin', 'staff']);

        $id   = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        $appt = $this->find($id);
        if (!$appt) {
            flash('error', 'Appointment not found.');
            redirect('staff/appointments');
        }
        if (!in_array($appt['status'], ['pending', 'confirmed'], true)
            || strtotime("{$appt['appt_date']} {$appt['start_time']}") <= time()) {
            flash('error', 'Only upcoming pending or confirmed appointments can be rescheduled.');
            redirect('staff/appointments');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $b = [
                'service_id' => (int) $appt['service_id'],
                'stylist_id' => (int) ($_POST['stylist_id'] ?? 0),
                'date'       => (string) ($_POST['date'] ?? ''),
                'time'       => (string) ($_POST['time'] ?? ''),
            ];

            $pdo = db();
            $pdo->beginTransaction();
            try {
                BookingRules::lock();
                $errors = BookingRules::check($b, $id);
                if ($errors) {
                    $pdo->rollBack();
                    foreach ($errors as $error) {
                        flash('error', $error);
                    }
                    redirect('staff/appointments/reschedule', ['id' => $id, 'stylist' => $b['stylist_id'], 'date' => $b['date']]);
                }

                $start = $b['time'] . ':00';
                // reminder_sent_at is cleared so the customer is reminded of the new time.
                $pdo->prepare(
                    'UPDATE appointments
                        SET stylist_id = :stylist, appt_date = :date, start_time = :start, end_time = :end, reminder_sent_at = NULL
                      WHERE id = :id'
                )->execute([
                    'stylist' => $b['stylist_id'],
                    'date'    => $b['date'],
                    'start'   => $start,
                    'end'     => BookingRules::endTime($start, (int) $appt['duration_minutes']),
                    'id'      => $id,
                ]);
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }

            // Confirmed bookings get a new confirmation with the new time.
            if ($appt['status'] === 'confirmed') {
                $this->emailNote(NotificationService::appointmentChanged($id, 'confirmation', true));
            }

            logged_flash('appointments', 'reschedule', "{$appt['customer']}'s appointment moved to " . fmt_date($b['date']) . ' at ' . fmt_time($b['time']) . '.');
            redirect('staff/appointments', ['from' => $b['date']]);
        }

        $pick = $this->readPicker(
            (int) $appt['service_id'],
            (int) ($_GET['stylist'] ?? $appt['stylist_id']),
            (string) ($_GET['date'] ?? $appt['appt_date']),
            $id
        );

        view('staff/appointment-reschedule', [
            'title'    => 'Reschedule appointment',
            'appt'     => $appt,
            'pick'     => $pick,
            'stylists' => BookingRules::stylistsForService((int) $appt['service_id']),
        ]);
    }

    // ---------- helpers ----------

    /** Tell staff when the customer email could not be sent (the booking itself is still saved). */
    private function emailNote(string $result): void
    {
        if ($result === 'failed') {
            flash('error', 'The change was saved, but the email to the customer could not be sent. The admin can see the reason in Settings › Email log.');
        }
    }

    private function allowedActions(string $status, bool $started): array
    {
        $allowed = [];
        foreach (self::ACTIONS as $action => [$from]) {
            if (!in_array($status, $from, true)) {
                continue;
            }
            // Completed / no-show only once the appointment time has come.
            if (in_array($action, ['complete', 'no_show'], true) && !$started) {
                continue;
            }
            $allowed[] = $action;
        }
        return $allowed;
    }

    private function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = db()->prepare(
            'SELECT a.*, c.full_name AS customer, s.name AS service, s.duration_minutes, st.full_name AS stylist
               FROM appointments a
               JOIN customers c  ON c.id  = a.customer_id
               JOIN services  s  ON s.id  = a.service_id
               JOIN stylists  st ON st.id = a.stylist_id
              WHERE a.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Go back to the list with the same filters the staff member had. */
    private function back(): void
    {
        $query = (string) ($_POST['back'] ?? '');
        parse_str($query, $params);
        unset($params['page']);
        redirect('staff/appointments', array_intersect_key($params, array_flip(['tab', 'date', 'service', 'stylist', 'q', 'p', 'm'])));
    }

    /** Validate service / stylist / date choices and load free slots. */
    private function readPicker(int $serviceId, int $stylistId, string $date, int $exceptId = 0): array
    {
        $pick = [
            'service_id' => $serviceId,
            'stylist_id' => $stylistId,
            'date'       => BookingRules::isValidDate($date) ? $date : date('Y-m-d'),
            'service'    => null,
            'stylist'    => null,
            'slots'      => [],
            'grid'       => [],
            'full'       => false,
            'problem'    => '',
        ];

        if (!$serviceId || !$stylistId) {
            return $pick;
        }
        $pick['service'] = BookingRules::service($serviceId);
        if (!$pick['service']) {
            $pick['problem'] = 'That service is not available.';
            return $pick;
        }
        $pick['stylist'] = BookingRules::stylistFor($stylistId, $serviceId);
        if (!$pick['stylist']) {
            $pick['problem'] = 'That stylist does not perform ' . $pick['service']['name'] . '. Choose another stylist.';
            return $pick;
        }
        if ($pick['date'] < date('Y-m-d')) {
            $pick['problem'] = 'Choose today or a future date.';
            return $pick;
        }
        if (BookingRules::isDateFull($pick['date'], $exceptId)) {
            $pick['full'] = true;
            return $pick;
        }
        $pick['grid']  = BookingRules::slotGrid($stylistId, $pick['date'], (int) $pick['service']['duration_minutes'], $exceptId);
        $pick['slots'] = array_column(array_filter($pick['grid'], static fn ($s) => $s['state'] === 'open'), 'time');
        return $pick;
    }

    /** Active stylists with the ids of the services they perform (for the stylist dropdown filter). */
    private function stylistsWithServices(): array
    {
        $stylists = db()->query('SELECT id, full_name FROM stylists WHERE is_active = 1 ORDER BY full_name')->fetchAll();
        $map = [];
        foreach (db()->query('SELECT stylist_id, service_id FROM stylist_services')->fetchAll() as $row) {
            $map[(int) $row['stylist_id']][] = (int) $row['service_id'];
        }
        foreach ($stylists as &$st) {
            $st['service_ids'] = $map[(int) $st['id']] ?? [];
        }
        unset($st);
        return $stylists;
    }

    private function saveNew(array $user): void
    {
        $b = [
            'service_id' => (int) ($_POST['service_id'] ?? 0),
            'stylist_id' => (int) ($_POST['stylist_id'] ?? 0),
            'date'       => (string) ($_POST['date'] ?? ''),
            'time'       => (string) ($_POST['time'] ?? ''),
        ];
        $source     = in_array($_POST['source'] ?? '', ['walk_in', 'phone'], true) ? $_POST['source'] : 'walk_in';
        $customerId = (int) ($_POST['customer_id'] ?? 0);
        $newName    = trim((string) ($_POST['new_name'] ?? ''));
        $newPhone   = trim((string) ($_POST['new_phone'] ?? ''));
        $newEmail   = strtolower(trim((string) ($_POST['new_email'] ?? '')));

        $back = ['service' => $b['service_id'], 'stylist' => $b['stylist_id'], 'date' => $b['date']];

        // Customer: existing or new.
        $errors = [];
        if ($customerId > 0) {
            $check = db()->prepare('SELECT 1 FROM customers WHERE id = :id');
            $check->execute(['id' => $customerId]);
            if (!$check->fetch()) {
                $errors[] = 'Choose a customer from the list.';
            }
        } else {
            if ($newName === '' || mb_strlen($newName) > 100) {
                $errors[] = 'Choose an existing customer or enter the new customer\'s name.';
            }
            if ($newPhone !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $newPhone)) {
                $errors[] = 'Enter a valid phone number.';
            }
            if ($newEmail !== '' && !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Enter a valid email address, or leave it empty.';
            }
        }
        if ($errors) {
            remember_old($_POST);
            foreach ($errors as $error) {
                flash('error', $error);
            }
            redirect('staff/appointments/new', $back);
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            BookingRules::lock();
            $errors = BookingRules::check($b);
            if ($errors) {
                $pdo->rollBack();
                remember_old($_POST);
                foreach ($errors as $error) {
                    flash('error', $error);
                }
                redirect('staff/appointments/new', $back);
            }

            if ($customerId === 0) {
                $pdo->prepare('INSERT INTO customers (full_name, phone, email) VALUES (:name, :phone, :email)')
                    ->execute([
                        'name'  => $newName,
                        'phone' => $newPhone !== '' ? $newPhone : null,
                        'email' => $newEmail !== '' ? $newEmail : null,
                    ]);
                $customerId = (int) $pdo->lastInsertId();
            }

            $service = BookingRules::service($b['service_id']);
            $start   = $b['time'] . ':00';
            $pdo->prepare(
                "INSERT INTO appointments
                    (customer_id, stylist_id, service_id, appt_date, start_time, end_time, status, source, created_by)
                 VALUES (:customer, :stylist, :service, :date, :start, :end, 'confirmed', :source, :created_by)"
            )->execute([
                'customer'   => $customerId,
                'stylist'    => $b['stylist_id'],
                'service'    => $b['service_id'],
                'date'       => $b['date'],
                'start'      => $start,
                'end'        => BookingRules::endTime($start, (int) $service['duration_minutes']),
                'source'     => $source,
                'created_by' => $user['id'],
            ]);
            $appointmentId = (int) $pdo->lastInsertId();
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        $this->emailNote(NotificationService::appointmentChanged($appointmentId, 'confirmation'));

        logged_flash('appointments', 'create', 'Walk-in/phone appointment booked for ' . fmt_date($b['date']) . ' at ' . fmt_time($b['time']) . '.');
        redirect('staff/appointments', ['from' => $b['date']]);
    }
}
