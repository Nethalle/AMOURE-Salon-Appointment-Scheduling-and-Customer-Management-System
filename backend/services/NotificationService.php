<?php
// Email Notifications: confirmation, cancellation and reminder emails, each logged in email_logs.

declare(strict_types=1);

require_once __DIR__ . '/Mailer.php';

final class NotificationService
{
    /**
     * Email the customer about a confirmed, rescheduled or cancelled appointment.
     * Call AFTER the appointment change has been saved.
     *
     * @param string $type 'confirmation' | 'cancellation'
     * @return string 'sent' | 'failed' | 'skipped'
     */
    public static function appointmentChanged(int $appointmentId, string $type, bool $rescheduled = false): string
    {
        if (setting('booking_emails_enabled') !== '1') {
            return 'skipped';
        }
        return self::send($appointmentId, $type, ['rescheduled' => $rescheduled]);
    }

    /** Reminder for one appointment. Marks reminder_sent_at only when the email was sent. */
    public static function reminder(int $appointmentId): string
    {
        $result = self::send($appointmentId, 'reminder');
        if ($result === 'sent') {
            db()->prepare('UPDATE appointments SET reminder_sent_at = :now WHERE id = :id')
                ->execute(['now' => date('Y-m-d H:i:s'), 'id' => $appointmentId]);
        }
        return $result;
    }

    /**
     * Send every reminder that is due now.
     * Due = confirmed, not reminded yet, customer has an email, starts within the next N hours.
     * @return array{sent: int, failed: int}
     */
    public static function sendDueReminders(): array
    {
        $result = ['sent' => 0, 'failed' => 0];
        if (setting('reminders_enabled') !== '1') {
            return $result;
        }

        $hours = max(1, (int) setting('reminder_hours_before'));
        $now   = time();
        $until = $now + $hours * 3600;

        $stmt = db()->prepare(
            "SELECT a.id, a.appt_date, a.start_time
               FROM appointments a
               JOIN customers c ON c.id = a.customer_id
              WHERE a.status = 'confirmed'
                AND a.reminder_sent_at IS NULL
                AND c.email IS NOT NULL AND c.email <> ''
                AND a.appt_date BETWEEN :from AND :to"
        );
        $stmt->execute(['from' => date('Y-m-d', $now), 'to' => date('Y-m-d', $until)]);

        foreach ($stmt->fetchAll() as $a) {
            $start = strtotime("{$a['appt_date']} {$a['start_time']}");
            if ($start <= $now || $start > $until) {
                continue;
            }
            $status = self::reminder((int) $a['id']);
            if ($status === 'sent') {
                $result['sent']++;
            } elseif ($status === 'failed') {
                $result['failed']++;
            }
        }
        return $result;
    }

    // ---------- internals ----------

    private static function send(int $appointmentId, string $type, array $extra = []): string
    {
        $a = self::details($appointmentId);
        if (!$a || !filter_var($a['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            return 'skipped';
        }

        $when = fmt_date($a['appt_date']) . ' at ' . fmt_time($a['start_time']);
        $subjects = [
            'confirmation' => ($extra['rescheduled'] ?? false)
                ? "Your appointment was moved to $when"
                : "Your appointment is confirmed for $when",
            'cancellation' => "Your appointment on $when was cancelled",
            'reminder'     => "Reminder: your appointment on $when",
        ];

        $data = $extra + [
            'a'        => $a,
            'when'     => $when,
            'viewUrl'  => $a['user_id'] ? abs_url('customer/my-appointments', ['appt' => $a['id']]) : '',
            'bookUrl'  => abs_url($a['user_id'] ? 'customer/book' : 'register'),
            'siteUrl'  => abs_url('home'),
            'hasAccount' => (bool) $a['user_id'],
        ];
        $html = render_email($type, $data);
        $text = self::plainText($type, $a, $when, (bool) ($extra['rescheduled'] ?? false), $data['viewUrl']);

        $error = Mailer::send($a['email'], $a['customer'], 'AMOURÉ SALON · ' . $subjects[$type], $html, $text);
        self::log($appointmentId, $type, $a['email'], $error);

        return $error === null ? 'sent' : 'failed';
    }

    private static function details(int $appointmentId): ?array
    {
        $stmt = db()->prepare(
            'SELECT a.id, a.appt_date, a.start_time, a.end_time, a.status,
                    c.full_name AS customer, c.email, c.user_id,
                    s.name AS service, s.price, st.full_name AS stylist
               FROM appointments a
               JOIN customers c  ON c.id  = a.customer_id
               JOIN services  s  ON s.id  = a.service_id
               JOIN stylists  st ON st.id = a.stylist_id
              WHERE a.id = :id'
        );
        $stmt->execute(['id' => $appointmentId]);
        return $stmt->fetch() ?: null;
    }

    private static function log(int $appointmentId, string $type, string $recipient, ?string $error): void
    {
        if ($error !== null) {
            error_log("[AMOURE] $type email to $recipient failed: $error");
        }
        db()->prepare(
            'INSERT INTO email_logs (appointment_id, type, recipient, status, error_message, created_at)
             VALUES (:appointment, :type, :recipient, :status, :error, :created_at)'
        )->execute([
            'appointment' => $appointmentId,
            'type'        => $type,
            'recipient'   => $recipient,
            'status'      => $error === null ? 'sent' : 'failed',
            'error'       => $error !== null ? mb_substr($error, 0, 1000) : null,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    private static function plainText(string $type, array $a, string $when, bool $rescheduled, string $viewUrl = ''): string
    {
        $intro = match ($type) {
            'confirmation' => $rescheduled ? 'Your appointment has been moved. Here are the new details:' : 'Your appointment is confirmed. We look forward to seeing you!',
            'cancellation' => 'Your appointment has been cancelled. We hope to see you another time.',
            default        => "This is a reminder of your upcoming appointment.",
        };

        return "Hi {$a['customer']},\n\n$intro\n\n"
            . "Booking: " . appt_ref($a['id']) . "\n"
            . "Service: {$a['service']}\n"
            . "Stylist: {$a['stylist']}\n"
            . "When:    $when – " . fmt_time($a['end_time']) . "\n"
            . "Price:   " . peso($a['price']) . "\n\n"
            . ($viewUrl !== '' ? "View your appointment: $viewUrl\n\n" : '')
            . "AMOURÉ SALON";
    }
}
