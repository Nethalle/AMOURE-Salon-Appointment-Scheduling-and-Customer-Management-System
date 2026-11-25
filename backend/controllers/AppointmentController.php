<?php
// Appointment Scheduling module: customer side.
// Online requests are saved as "pending"; staff confirm them.

declare(strict_types=1);

require_once __DIR__ . '/../services/BookingRules.php';
require_once __DIR__ . '/../services/NotificationService.php';

class AppointmentController
{
    /** Customer: their upcoming and past appointments. */
    public function mine(): void
    {
        $user = require_role(['customer']);

        $stmt = db()->prepare(
            'SELECT a.id, a.appt_date, a.start_time, a.end_time, a.status,
                    s.name AS service, s.price, st.full_name AS stylist,
                    r.rating AS review_rating, r.status AS review_status
               FROM appointments a
               JOIN customers  c  ON c.id  = a.customer_id
               JOIN services   s  ON s.id  = a.service_id
               JOIN stylists   st ON st.id = a.stylist_id
               LEFT JOIN reviews r ON r.appointment_id = a.id
              WHERE c.user_id = :uid
              ORDER BY a.appt_date DESC, a.start_time DESC'
        );
        $stmt->execute(['uid' => $user['id']]);

        $upcoming = [];
        $past     = [];
        $now      = time();
        foreach ($stmt->fetchAll() as $a) {
            $isFuture = strtotime("{$a['appt_date']} {$a['start_time']}") > $now;
            $a['can_cancel'] = $isFuture && in_array($a['status'], ['pending', 'confirmed'], true);
            if ($isFuture && in_array($a['status'], ['pending', 'confirmed'], true)) {
                $upcoming[] = $a;
            } else {
                $past[] = $a;
            }
        }
        // Soonest first for upcoming.
        $upcoming = array_reverse($upcoming);

        view('customer/my-appointments', [
            'title'    => 'My appointments',
            'upcoming' => $upcoming,
            'past'     => $past,
        ]);
    }

    /**
     * Customer: 4-step booking.
     *   1. ?page=customer/book                          choose service
     *   2. &service=ID                                   choose stylist
     *   3. &service=ID&stylist=ID[&date=Y-m-d]           choose date and time
     *   4. &service=ID&stylist=ID&date=Y-m-d&time=H:i    confirm (POST saves)
     */
    public function book(): void
    {
        $user = require_role(['customer']);

        $serviceId = (int) ($_GET['service'] ?? 0);
        $stylistId = (int) ($_GET['stylist'] ?? 0);
        $date      = (string) ($_GET['date'] ?? '');
        $time      = (string) ($_GET['time'] ?? '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $this->saveBooking($user, [
                'service_id' => (int) ($_POST['service_id'] ?? 0),
                'stylist_id' => (int) ($_POST['stylist_id'] ?? 0),
                'date'       => (string) ($_POST['date'] ?? ''),
                'time'       => (string) ($_POST['time'] ?? ''),
            ]);
            return;
        }

        // Step 1: service
        $service = $serviceId ? BookingRules::service($serviceId) : null;
        if (!$service) {
            view('customer/book', [
                'title'    => 'Book an appointment',
                'step'     => 1,
                'services' => BookingRules::bookableServices(),
            ]);
            return;
        }

        // Step 2: stylist
        $stylist = $stylistId ? BookingRules::stylistFor($stylistId, $serviceId) : null;
        if (!$stylist) {
            view('customer/book', [
                'title'    => 'Book an appointment',
                'step'     => 2,
                'service'  => $service,
                'stylists' => BookingRules::stylistsForService($serviceId),
            ]);
            return;
        }

        // Step 3: date and time
        $days = $this->upcomingDays();
        if (!isset($days[$date])) {
            $date = array_key_first(array_filter($days, static fn ($d) => !$d['full'])) ?? '';
        }
        $grid = ($date !== '' && !$days[$date]['full'])
            ? BookingRules::slotGrid($stylistId, $date, (int) $service['duration_minutes'])
            : [];
        $slots = array_column(array_filter($grid, static fn ($s) => $s['state'] === 'open'), 'time');

        if ($time === '' || !in_array($time, $slots, true)) {
            view('customer/book', [
                'title'   => 'Book an appointment',
                'step'    => 3,
                'service' => $service,
                'stylist' => $stylist,
                'days'    => $days,
                'date'    => $date,
                'slots'   => $slots,
                'grid'    => $grid,
            ]);
            return;
        }

        // Step 4: confirm
        view('customer/book', [
            'title'   => 'Book an appointment',
            'step'    => 4,
            'service' => $service,
            'stylist' => $stylist,
            'date'    => $date,
            'time'    => $time,
            'endTime' => BookingRules::endTime($time, (int) $service['duration_minutes']),
        ]);
    }

    /** Customer: cancel one of their own upcoming appointments. */
    public function cancel(): void
    {
        $user = require_role(['customer']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('customer/my-appointments');
        }
        csrf_check();

        $id = (int) ($_POST['id'] ?? 0);
        $stmt = db()->prepare(
            'SELECT a.id, a.appt_date, a.start_time, a.status
               FROM appointments a
               JOIN customers c ON c.id = a.customer_id
              WHERE a.id = :id AND c.user_id = :uid'
        );
        $stmt->execute(['id' => $id, 'uid' => $user['id']]);
        $appt = $stmt->fetch();

        // Not found and "not yours" get the same answer, so IDs can't be probed.
        if (!$appt) {
            flash('error', 'Appointment not found.');
            redirect('customer/my-appointments');
        }
        if (!in_array($appt['status'], ['pending', 'confirmed'], true)
            || strtotime("{$appt['appt_date']} {$appt['start_time']}") <= time()) {
            flash('error', 'This appointment can no longer be cancelled.');
            redirect('customer/my-appointments');
        }

        db()->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = :id")->execute(['id' => $id]);
        NotificationService::appointmentChanged($id, 'cancellation');

        logged_flash('appointments', 'customer_cancel', 'Cancelled own appointment on ' . fmt_date($appt['appt_date']) . ' at ' . fmt_time($appt['start_time']) . ' was cancelled.');
        redirect('customer/my-appointments');
    }

    // ---------- helpers ----------

    private function saveBooking(array $user, array $b): void
    {
        $back = [
            'service' => $b['service_id'],
            'stylist' => $b['stylist_id'],
            'date'    => $b['date'],
        ];

        $customer = db()->prepare('SELECT id FROM customers WHERE user_id = :uid');
        $customer->execute(['uid' => $user['id']]);
        $customerId = (int) $customer->fetchColumn();
        if (!$customerId) {
            flash('error', 'Your customer profile is missing. Please contact the salon.');
            redirect('customer/my-appointments');
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            BookingRules::lock();
            $errors = BookingRules::check($b);

            if ($errors) {
                $pdo->rollBack();
                foreach ($errors as $error) {
                    flash('error', $error);
                }
                redirect('customer/book', $back);
            }

            $service = BookingRules::service($b['service_id']);
            $start   = $b['time'] . ':00';
            $insert  = $pdo->prepare(
                "INSERT INTO appointments
                    (customer_id, stylist_id, service_id, appt_date, start_time, end_time, status, source, created_by)
                 VALUES (:customer, :stylist, :service, :date, :start, :end, 'pending', 'online', :created_by)"
            );
            $insert->execute([
                'customer'   => $customerId,
                'stylist'    => $b['stylist_id'],
                'service'    => $b['service_id'],
                'date'       => $b['date'],
                'start'      => $start,
                'end'        => BookingRules::endTime($start, (int) $service['duration_minutes']),
                'created_by' => $user['id'],
            ]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        logged_flash('appointments', 'customer_book', 'Booking request sent for ' . fmt_date($b['date']) . ' at ' . fmt_time($b['time']) . '. The salon will confirm it soon.');
        redirect('customer/my-appointments');
    }

    /** The next N days the salon takes bookings, each marked full or not. */
    private function upcomingDays(): array
    {
        $count = max(1, min(60, (int) setting('booking_days_ahead')));
        $days  = [];
        for ($i = 0; $i < $count; $i++) {
            $d = date('Y-m-d', strtotime("+$i day"));
            $days[$d] = [
                'weekday' => date('D', strtotime($d)),
                'day'     => date('j', strtotime($d)),
                'month'   => date('M', strtotime($d)),
                'full'    => BookingRules::isDateFull($d),
            ];
        }
        return $days;
    }
}
