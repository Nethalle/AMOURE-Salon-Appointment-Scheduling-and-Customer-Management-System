<?php
// Settings (admin only): daily limit, opening hours, email switches, reminders and the email log.

declare(strict_types=1);

require_once __DIR__ . '/../services/NotificationService.php';

class SettingsController
{
    public function index(): void
    {
        require_role(['admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $this->save($_POST);
            return;
        }

        $logs = db()->query(
            'SELECT l.created_at, l.type, l.recipient, l.status, l.error_message, a.appt_date, a.start_time
               FROM email_logs l
               LEFT JOIN appointments a ON a.id = l.appointment_id
              ORDER BY l.id DESC
              LIMIT 25'
        )->fetchAll();

        $values = [];
        foreach (array_keys(SETTING_DEFAULTS) as $key) {
            $values[$key] = setting($key);
        }

        view('admin/settings', ['title' => 'Settings', 'values' => $values, 'logs' => $logs]);
    }

    /** "Send due reminders now" button: same logic as the scheduled job. */
    public function sendReminders(): void
    {
        require_role(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/settings');
        }
        csrf_check();

        if (setting('reminders_enabled') !== '1') {
            flash('error', 'Reminders are turned off. Turn them on first.');
            redirect('admin/settings');
        }

        $result = NotificationService::sendDueReminders();
        if ($result['sent'] === 0 && $result['failed'] === 0) {
            flash('success', 'No reminders are due right now.');
        } else {
            flash($result['failed'] ? 'error' : 'success',
                "Reminders sent: {$result['sent']}. Failed: {$result['failed']}." . ($result['failed'] ? ' See the email log below.' : ''));
        }
        redirect('admin/settings');
    }

    private function save(array $in): void
    {
        $v = [
            'daily_appointment_limit' => trim((string) ($in['daily_appointment_limit'] ?? '')),
            'opening_time'            => trim((string) ($in['opening_time'] ?? '')),
            'closing_time'            => trim((string) ($in['closing_time'] ?? '')),
            'slot_interval_minutes'   => trim((string) ($in['slot_interval_minutes'] ?? '')),
            'booking_days_ahead'      => trim((string) ($in['booking_days_ahead'] ?? '')),
            'booking_emails_enabled'  => isset($in['booking_emails_enabled']) ? '1' : '0',
            'reminders_enabled'       => isset($in['reminders_enabled']) ? '1' : '0',
            'reminder_hours_before'   => trim((string) ($in['reminder_hours_before'] ?? '')),
        ];

        $errors = [];
        if (!ctype_digit($v['daily_appointment_limit']) || (int) $v['daily_appointment_limit'] < 1 || (int) $v['daily_appointment_limit'] > 500) {
            $errors[] = 'Daily appointment limit must be between 1 and 500.';
        }
        $timeOk = static fn (string $t) => (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t);
        if (!$timeOk($v['opening_time']) || !$timeOk($v['closing_time'])) {
            $errors[] = 'Enter opening and closing times like 09:00.';
        } elseif ($v['closing_time'] <= $v['opening_time']) {
            $errors[] = 'Closing time must be later than opening time.';
        }
        if (!in_array($v['slot_interval_minutes'], ['15', '30', '60'], true)) {
            $errors[] = 'Time slot interval must be 15, 30 or 60 minutes.';
        }
        if (!ctype_digit($v['booking_days_ahead']) || (int) $v['booking_days_ahead'] < 1 || (int) $v['booking_days_ahead'] > 60) {
            $errors[] = 'Customers can book between 1 and 60 days ahead.';
        }
        if (!ctype_digit($v['reminder_hours_before']) || (int) $v['reminder_hours_before'] < 1 || (int) $v['reminder_hours_before'] > 72) {
            $errors[] = 'Send reminders between 1 and 72 hours before.';
        }

        if ($errors) {
            remember_old($in);
            foreach ($errors as $error) {
                flash('error', $error);
            }
            redirect('admin/settings');
        }

        $pdo    = db();
        $exists = $pdo->prepare('SELECT 1 FROM settings WHERE setting_key = :k');
        $update = $pdo->prepare('UPDATE settings SET setting_value = :v WHERE setting_key = :k');
        $insert = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)');

        $pdo->beginTransaction();
        foreach ($v as $key => $value) {
            $exists->execute(['k' => $key]);
            ($exists->fetchColumn() ? $update : $insert)->execute(['k' => $key, 'v' => $value]);
        }
        $pdo->commit();

        flash('success', 'Settings saved.');
        redirect('admin/settings');
    }
}
