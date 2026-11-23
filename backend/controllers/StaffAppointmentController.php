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

    /** List appointments with filters. */
    public function index(): void
    {
        require_role(['admin', 'staff']);

        $f = [
            'from'    => (string) ($_GET['from'] ?? date('Y-m-d')),
            'to'      => (string) ($_GET['to'] ?? ''),
            'status'  => (string) ($_GET['status'] ?? ''),
            'stylist' => (int) ($_GET['stylist'] ?? 0),
            'q'       => trim((string) ($_GET['q'] ?? '')),
        ];

        $where  = [];
        $params = [];
        if (BookingRules::isValidDate($f['from'])) {
            $where[] = 'a.appt_date >= :from';
            $params['from'] = $f['from'];
        } else {
            $f['from'] = '';
        }
        if (BookingRules::isValidDate($f['to'])) {
            $where[] = 'a.appt_date <= :to';
            $params['to'] = $f['to'];
        } else {
            $f['to'] = '';
        }
        if (in_array($f['status'], ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'], true)) {
            $where[] = 'a.status = :status';
            $params['status'] = $f['status'];
        } else {
            $f['status'] = '';
        }
        if ($f['stylist'] > 0) {
            $where[] = 'a.stylist_id = :stylist';
            $params['stylist'] = $f['stylist'];
        }
        if ($f['q'] !== '') {
            $where[] = '(c.full_name LIKE :q1 OR c.phone LIKE :q2 OR c.email LIKE :q3)';
            $like = '%' . $f['q'] . '%';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }

        $sql = 'SELECT a.id, a.appt_date, a.start_time, a.end_time, a.status, a.source,
                       c.full_name AS customer, c.phone, c.email,
                       s.name AS service, st.full_name AS stylist
                  FROM appointments a
                  JOIN customers c  ON c.id  = a.customer_id
                  JOIN services  s  ON s.id  = a.service_id
                  JOIN stylists  st ON st.id = a.stylist_id'
             . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
             . ' ORDER BY a.appt_date, a.start_time
                 LIMIT 300';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        $now = time();
        $appointments = array_map(function (array $a) use ($now) {
            $started = strtotime("{$a['appt_date']} {$a['start_time']}") <= $now;
            $a['actions'] = $this->allowedActions($a['status'], $started);
            $a['can_reschedule'] = !$started && in_array($a['status'], ['pending', 'confirmed'], true);
            return $a;
        }, $stmt->fetchAll());

        view('staff/appointments', [
            'title'        => 'Appointments',
            'appointments' => $appointments,
            'filters'      => $f,
            'stylists'     => db()->query('SELECT id, full_name FROM stylists ORDER BY full_name')->fetchAll(),
            'back'         => $_SERVER['QUERY_STRING'] ?? '',
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
        flash('success', "{$appt['customer']}'s appointment on " . fmt_date($appt['appt_date']) . ' at ' . fmt_time($appt['start_time']) . " was {$labels[$newStatus]}.");
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
            'services'  => db()->query('SELECT id, name, duration_minutes FROM services WHERE is_active = 1 ORDER BY name')->fetchAll(),
            'stylists'  => $this->stylistsWithServices(),
            'customers' => db()->query('SELECT id, full_name, phone FROM customers ORDER BY full_name')->fetchAll(),
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

            flash('success', "{$appt['customer']}'s appointment moved to " . fmt_date($b['date']) . ' at ' . fmt_time($b['time']) . '.');
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
        redirect('staff/appointments', array_intersect_key($params, array_flip(['from', 'to', 'status', 'stylist', 'q'])));
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
        $pick['slots'] = BookingRules::availableSlots($stylistId, $pick['date'], (int) $pick['service']['duration_minutes'], $exceptId);
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

        flash('success', 'Appointment booked for ' . fmt_date($b['date']) . ' at ' . fmt_time($b['time']) . '.');
        redirect('staff/appointments', ['from' => $b['date']]);
    }
}
