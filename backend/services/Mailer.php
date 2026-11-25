<?php
// Sends one email through SMTP with PHPMailer (installed by Composer).

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

final class Mailer
{
    /** Is mail set up at all? Returns null when ready, or what's missing. */
    public static function setupProblem(): ?string
    {
        if (!is_file(__DIR__ . '/../../vendor/autoload.php')) {
            return 'PHPMailer is not installed. Run "composer install" in the project folder.';
        }
        $configFile = __DIR__ . '/../config/mail.php';
        if (!is_file($configFile)) {
            return 'Missing backend/config/mail.php. Copy mail.example.php to mail.php and fill it in.';
        }
        $config = require $configFile;
        if (str_contains((string) ($config['password'] ?? ''), 'app-password') || str_contains((string) ($config['username'] ?? ''), 'your-salon-email')) {
            return 'backend/config/mail.php still has the example values. Enter your Gmail or Brevo SMTP details.';
        }
        if (!extension_loaded('openssl')) {
            return 'PHP\'s openssl extension is off. In C:\\xampp\\php\\php.ini remove the ; before extension=openssl, then restart Apache.';
        }
        return null;
    }

    /** The sender address from mail.php (shown in Settings), or ''. */
    public static function sender(): string
    {
        $configFile = __DIR__ . '/../config/mail.php';
        if (!is_file($configFile)) {
            return '';
        }
        $config = require $configFile;
        return (string) ($config['from_email'] ?? '');
    }

    /** Turn an SMTP error into a short "what to do" tip. */
    public static function hint(string $error): string
    {
        $e = strtolower($error);
        return match (true) {
            str_contains($e, 'authenticate') || str_contains($e, 'username and password') || str_contains($e, '535')
                => 'The mail server refused the login. Gmail: use a 16-letter App Password, no spaces. Brevo: use your SMTP login and SMTP key from Brevo > SMTP & API. Check backend/config/mail.php.',
            str_contains($e, 'connect') || str_contains($e, 'timed out') || str_contains($e, 'network')
                => 'Could not reach the mail server. Check your internet, and the host in mail.php (smtp.gmail.com or smtp-relay.brevo.com), port 587, encryption tls.',
            str_contains($e, 'certificate') || str_contains($e, 'ssl') || str_contains($e, 'crypto')
                => 'A security certificate problem on this computer. Check that extension=openssl is on in php.ini and restart Apache.',
            str_contains($e, 'sender') || str_contains($e, 'not authorized') || str_contains($e, 'not verified')
                => 'The From address is not allowed. In Brevo, add and verify it under Senders, and use the same address as from_email in mail.php.',
            str_contains($e, 'invalid address')
                => 'The email address is not valid.',
            default => '',
        };
    }

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
            return 'backend/config/mail.php still has the example values. Enter your Gmail or Brevo SMTP details.';
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
            // Attach the pictures the email uses (logo, icons): <img src="cid:name">.
            $imgDir = __DIR__ . '/../../frontend/assets/img/email/';
            if (preg_match_all('/src="cid:([a-z0-9-]+)"/', $html, $m)) {
                foreach (array_unique($m[1]) as $cid) {
                    if (is_file($imgDir . $cid . '.png')) {
                        $mail->addEmbeddedImage($imgDir . $cid . '.png', $cid, $cid . '.png', PHPMailer::ENCODING_BASE64, 'image/png');
                    }
                }
            }
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
