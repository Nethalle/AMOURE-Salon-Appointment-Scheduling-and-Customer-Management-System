<?php
// Sends reminder emails that are due. Run every hour by Windows Task Scheduler or cron:
//   C:\xampp\php\php.exe C:\xampp\htdocs\<project>\backend\jobs\send_reminders.php

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

date_default_timezone_set('Asia/Manila');

require __DIR__ . '/../core/helpers.php';
require __DIR__ . '/../core/db.php';
require __DIR__ . '/../core/settings.php';
require __DIR__ . '/../services/NotificationService.php';

try {
    $result = NotificationService::sendDueReminders();
    echo date('Y-m-d H:i:s') . " Reminders sent: {$result['sent']}, failed: {$result['failed']}" . PHP_EOL;
    exit($result['failed'] > 0 ? 1 : 0);
} catch (Throwable $e) {
    fwrite(STDERR, date('Y-m-d H:i:s') . ' Reminder job error: ' . $e->getMessage() . PHP_EOL);
    exit(2);
}
