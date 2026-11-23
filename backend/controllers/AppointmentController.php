<?php
// Appointment Scheduling module. Booking, rescheduling and cancelling are added next.

declare(strict_types=1);

class AppointmentController
{
    /** Customer: list of their own appointments. */
    public function mine(): void
    {
        $user = require_role(['customer']);

        $stmt = db()->prepare(
            'SELECT a.appt_date, a.start_time, a.end_time, a.status,
                    s.name AS service, st.full_name AS stylist
               FROM appointments a
               JOIN customers  c  ON c.id  = a.customer_id
               JOIN services   s  ON s.id  = a.service_id
               JOIN stylists   st ON st.id = a.stylist_id
              WHERE c.user_id = :uid
              ORDER BY a.appt_date DESC, a.start_time DESC'
        );
        $stmt->execute(['uid' => $user['id']]);

        view('customer/my-appointments', [
            'title'        => 'My appointments',
            'appointments' => $stmt->fetchAll(),
        ]);
    }
}
