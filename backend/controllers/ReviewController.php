<?php
// Customer Reviews: customers review completed appointments; the admin approves or removes them.
// Only approved reviews are shown publicly.

declare(strict_types=1);

class ReviewController
{
    /** Admin actions: action => [allowed from statuses, new status]. */
    private const ACTIONS = [
        'approve' => [['pending', 'removed'], 'approved'],
        'remove'  => [['pending', 'approved'], 'removed'],
    ];

    /** Customer: write a review for one of their own completed appointments. */
    public function write(): void
    {
        $user = require_role(['customer']);

        $id   = (int) ($_GET['id'] ?? $_POST['appointment_id'] ?? 0);
        $appt = $this->customerAppointment($id, (int) $user['id']);

        if (!$appt) {
            flash('error', 'Appointment not found.');
            redirect('customer/my-appointments');
        }
        if ($appt['status'] !== 'completed') {
            flash('error', 'You can review an appointment once it has been completed.');
            redirect('customer/my-appointments');
        }
        if ($appt['review_id']) {
            flash('error', 'You have already reviewed this appointment. Thank you!');
            redirect('customer/my-appointments');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();

            $rating  = (int) ($_POST['rating'] ?? 0);
            $comment = trim((string) ($_POST['comment'] ?? ''));

            $errors = [];
            if ($rating < 1 || $rating > 5) {
                $errors[] = 'Choose a rating from 1 to 5 stars.';
            }
            if (mb_strlen($comment) > 1000) {
                $errors[] = 'Your review can be up to 1,000 characters.';
            }
            if ($errors) {
                remember_old($_POST);
                foreach ($errors as $error) {
                    flash('error', $error);
                }
                redirect('customer/review', ['id' => $id]);
            }

            try {
                db()->prepare(
                    "INSERT INTO reviews (appointment_id, rating, comment, status, created_at)
                     VALUES (:appointment, :rating, :comment, 'pending', :created_at)"
                )->execute([
                    'appointment' => $id,
                    'rating'      => $rating,
                    'comment'     => $comment !== '' ? $comment : null,
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
            } catch (PDOException $e) {
                // Unique key on appointment_id: a second review from a double-click or another tab.
                flash('error', 'You have already reviewed this appointment. Thank you!');
                redirect('customer/my-appointments');
            }

            flash('success', 'Thank you for your review! It will appear on our page once approved.');
            redirect('customer/my-appointments');
        }

        view('customer/review', ['title' => 'Leave a review', 'appt' => $appt]);
    }

    /** Admin: list reviews by status. */
    public function index(): void
    {
        require_role(['admin']);

        $status = (string) ($_GET['status'] ?? 'pending');
        if (!in_array($status, ['pending', 'approved', 'removed', 'all'], true)) {
            $status = 'pending';
        }

        $sql = 'SELECT r.id, r.rating, r.comment, r.status, r.created_at,
                       a.appt_date, c.full_name AS customer, s.name AS service, st.full_name AS stylist
                  FROM reviews r
                  JOIN appointments a ON a.id = r.appointment_id
                  JOIN customers c    ON c.id = a.customer_id
                  JOIN services s     ON s.id = a.service_id
                  JOIN stylists st    ON st.id = a.stylist_id'
             . ($status !== 'all' ? ' WHERE r.status = :status' : '')
             . ' ORDER BY r.created_at DESC LIMIT 200';
        $stmt = db()->prepare($sql);
        $stmt->execute($status !== 'all' ? ['status' => $status] : []);

        $counts = ['pending' => 0, 'approved' => 0, 'removed' => 0];
        foreach (db()->query('SELECT status, COUNT(*) AS n FROM reviews GROUP BY status')->fetchAll() as $row) {
            $counts[$row['status']] = (int) $row['n'];
        }

        view('admin/reviews', [
            'title'   => 'Reviews',
            'reviews' => $stmt->fetchAll(),
            'status'  => $status,
            'counts'  => $counts,
        ]);
    }

    /** Admin: approve / remove a review. */
    public function moderate(): void
    {
        require_role(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/reviews');
        }
        csrf_check();

        $id     = (int) ($_POST['id'] ?? 0);
        $action = (string) ($_POST['action'] ?? '');
        $back   = in_array($_POST['back'] ?? '', ['pending', 'approved', 'removed', 'all'], true) ? $_POST['back'] : 'pending';

        $stmt = db()->prepare('SELECT status FROM reviews WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $current = $stmt->fetchColumn();

        if ($current === false || !isset(self::ACTIONS[$action]) || !in_array($current, self::ACTIONS[$action][0], true)) {
            flash('error', 'That review could not be updated.');
            redirect('admin/reviews', ['status' => $back]);
        }

        $new = self::ACTIONS[$action][1];
        db()->prepare('UPDATE reviews SET status = :status WHERE id = :id')->execute(['status' => $new, 'id' => $id]);

        flash('success', $new === 'approved' ? 'Review approved. It now shows on the home page.' : 'Review removed. It no longer shows on the home page.');
        redirect('admin/reviews', ['status' => $back]);
    }

    // ---------- helpers ----------

    private function customerAppointment(int $id, int $userId): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = db()->prepare(
            'SELECT a.id, a.appt_date, a.start_time, a.status, s.name AS service, st.full_name AS stylist, r.id AS review_id
               FROM appointments a
               JOIN customers c  ON c.id  = a.customer_id
               JOIN services s   ON s.id  = a.service_id
               JOIN stylists st  ON st.id = a.stylist_id
               LEFT JOIN reviews r ON r.appointment_id = a.id
              WHERE a.id = :id AND c.user_id = :uid'
        );
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        return $stmt->fetch() ?: null;
    }
}
