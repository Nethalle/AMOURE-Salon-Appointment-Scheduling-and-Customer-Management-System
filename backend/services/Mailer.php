<?php
// Sends one email through SMTP with PHPMailer (installed by Composer).

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

final class Mailer
{
    /**
     * Send an email. Returns null when sent, or an error message when it failed.
     * Never throws, so a mail problem can't break a booking.
     */
    public static function send(string $to, string $toName, string $subject, string $html, string $text): ?string
    {
        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (!is_file($autoload)) {
            return 'PHPMailer is not installed. Run "composer install" in the project folder.';
        }
        require_once $autoload;

        $configFile = __DIR__ . '/../config/mail.php';
        if (!is_file($configFile)) {
            return 'Missing backend/config/mail.php. Copy mail.example.php to mail.php and fill it in.';
        }
        $config = require $configFile;

        if (str_contains((string) $config['password'], 'app-password') || str_contains((string) $config['username'], 'your-salon-email')) {
            return 'backend/config/mail.php still has the example values. Enter your Gmail address and App Password.';
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $config['host'];
            $mail->Port       = (int) $config['port'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $config['username'];
            $mail->Password   = $config['password'];
            $mail->SMTPSecure = ($config['encryption'] ?? 'tls') === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Timeout    = 15;
            $mail->CharSet    = PHPMailer::CHARSET_UTF8;

            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($to, $toName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = $text;

            $mail->send();
            return null;
        } catch (MailException $e) {
            return $mail->ErrorInfo ?: $e->getMessage();
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }
}
