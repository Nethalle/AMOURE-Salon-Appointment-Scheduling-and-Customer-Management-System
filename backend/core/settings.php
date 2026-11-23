<?php
// Values from the settings table, with safe defaults if a row is missing.

declare(strict_types=1);

const SETTING_DEFAULTS = [
    'daily_appointment_limit' => '20',
    'booking_emails_enabled'  => '1',
    'reminders_enabled'       => '1',
    'reminder_hours_before'   => '24',
    'opening_time'            => '09:00',
    'closing_time'            => '19:00',
    'slot_interval_minutes'   => '30',
    'booking_days_ahead'      => '30',
    // Salon info shown on the website (empty = hidden)
    'salon_phone'             => '',
    'salon_email'             => '',
    'salon_address'           => '',
    'instagram_url'           => '',
    'facebook_url'            => '',
    'tiktok_url'              => '',
];

function setting(string $key): string
{
    static $cache = null;

    if ($cache === null) {
        $cache = [];
        try {
            foreach (db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll() as $row) {
                $cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $e) {
            // Database not reachable (e.g. while showing the error page): fall back to defaults.
        }
    }

    return $cache[$key] ?? SETTING_DEFAULTS[$key] ?? '';
}
