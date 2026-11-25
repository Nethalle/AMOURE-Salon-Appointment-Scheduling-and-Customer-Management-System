<?php
// One-time email codes for "Forgot password" and 2-step login, plus "remember this device".

declare(strict_types=1);

require_once __DIR__ . '/Mailer.php';

final class AuthCodes
{
    public const MINUTES      = 15;  // a code works for 15 minutes
    public const MAX_ATTEMPTS = 5;   // wrong tries before the code stops working
    public const MAX_PER_HOUR = 5;   // codes one account can ask for in an hour
    public const RESEND_WAIT  = 60;  // seconds between "send a new code"
    private const DEVICE_DAYS = 30;
    private const COOKIE      = 'amoure_device';

    /** Make a new 6-digit code (older unused ones of the same kind stop working) and email it. Returns null or an error. */
    public static function send(array $user, string $purpose): ?string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $pdo  = db();
        $pdo->prepare('UPDATE auth_codes SET used_at = :now WHERE user_id = :u AND purpose = :p AND used_at IS NULL')
            ->execute(['now' => date('Y-m-d H:i:s'), 'u' => $user['id'], 'p' => $purpose]);
        $pdo->prepare('INSERT INTO auth_codes (user_id, purpose, code_hash, expires_at, created_at) VALUES (:u, :p, :h, :exp, :now)')
            ->execute([
                'u' => $user['id'], 'p' => $purpose, 'h' => password_hash($code, PASSWORD_DEFAULT),
                'exp' => date('Y-m-d H:i:s', time() + self::MINUTES * 60), 'now' => date('Y-m-d H:i:s'),
            ]);

        $subject = $purpose === 'reset' ? 'Your password reset code' : 'Your sign-in code';
        $html = render_email('code', ['name' => $user['name'], 'code' => $code, 'purpose' => $purpose, 'minutes' => self::MINUTES]);
        $text = "Hi {$user['name']},\n\nYour AMOURÉ SALON " . ($purpose === 'reset' ? 'password reset' : 'sign-in')
              . " code is: $code\n\nIt works once and expires in " . self::MINUTES . " minutes.\n"
              . "If you didn't ask for this, you can ignore this email" . ($purpose === 'reset' ? '' : ' and change your password') . ".\n\nAMOURÉ SALON";
        return Mailer::send($user['email'], $user['name'], 'AMOURÉ SALON · ' . $subject . ': ' . $code, $html, $text);
    }

    /** How many codes of this kind the account asked for in the last hour, and seconds since the latest. */
    public static function recent(int $userId, string $purpose): array
    {
        $stmt = db()->prepare(
            'SELECT COUNT(*) AS n, MAX(created_at) AS last FROM auth_codes
              WHERE user_id = :u AND purpose = :p AND created_at >= :since'
        );
        $stmt->execute(['u' => $userId, 'p' => $purpose, 'since' => date('Y-m-d H:i:s', time() - 3600)]);
        $row = $stmt->fetch();
        return [(int) $row['n'], $row['last'] ? time() - strtotime((string) $row['last']) : PHP_INT_MAX];
    }

    /** Check a typed code. Returns ok | wrong | expired | too_many | none. A correct code is used up. */
    public static function verify(int $userId, string $purpose, string $typed): string
    {
        $stmt = db()->prepare(
            'SELECT id, code_hash, attempts, expires_at FROM auth_codes
              WHERE user_id = :u AND purpose = :p AND used_at IS NULL
              ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['u' => $userId, 'p' => $purpose]);
        $row = $stmt->fetch();
        if (!$row) {
            return 'none';
        }
        if (strtotime((string) $row['expires_at']) < time()) {
            return 'expired';
        }
        if ((int) $row['attempts'] >= self::MAX_ATTEMPTS) {
            return 'too_many';
        }
        $typed = preg_replace('/\D/', '', $typed) ?? '';
        if (!password_verify($typed, (string) $row['code_hash'])) {
            db()->prepare('UPDATE auth_codes SET attempts = attempts + 1 WHERE id = :id')->execute(['id' => $row['id']]);
            return (int) $row['attempts'] + 1 >= self::MAX_ATTEMPTS ? 'too_many' : 'wrong';
        }
        // Use it up so it can't be used twice (only succeeds once, even with two tabs).
        $use = db()->prepare('UPDATE auth_codes SET used_at = :now WHERE id = :id AND used_at IS NULL');
        $use->execute(['now' => date('Y-m-d H:i:s'), 'id' => $row['id']]);
        return $use->rowCount() === 1 ? 'ok' : 'none';
    }

    // ---------- "Remember this device for 30 days" ----------

    public static function trustDevice(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        db()->prepare('INSERT INTO trusted_devices (user_id, token_hash, expires_at, created_at) VALUES (:u, :h, :exp, :now)')
            ->execute([
                'u' => $userId, 'h' => hash('sha256', $token),
                'exp' => date('Y-m-d H:i:s', time() + self::DEVICE_DAYS * 86400), 'now' => date('Y-m-d H:i:s'),
            ]);
        $p = session_get_cookie_params();
        setcookie(self::COOKIE, $userId . ':' . $token, [
            'expires'  => time() + self::DEVICE_DAYS * 86400,
            'path'     => $p['path'] ?: '/',
            'secure'   => (bool) $p['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function isTrustedDevice(int $userId): bool
    {
        $cookie = (string) ($_COOKIE[self::COOKIE] ?? '');
        if (!preg_match('/^(\d+):([a-f0-9]{64})$/', $cookie, $m) || (int) $m[1] !== $userId) {
            return false;
        }
        try {
            $stmt = db()->prepare('SELECT 1 FROM trusted_devices WHERE user_id = :u AND token_hash = :h AND expires_at > :now');
            $stmt->execute(['u' => $userId, 'h' => hash('sha256', $m[2]), 'now' => date('Y-m-d H:i:s')]);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    /** Sign out every remembered device (after a password reset, or from My Account). */
    public static function forgetDevices(int $userId): void
    {
        try {
            db()->prepare('DELETE FROM trusted_devices WHERE user_id = :u')->execute(['u' => $userId]);
        } catch (Throwable $e) {
            // table not created yet
        }
    }

    /** Does this account need a code at login? Its own switch, or the salon's rule for admin/staff. */
    public static function required(array $user): bool
    {
        if (!empty($user['two_factor_enabled'])) {
            return true;
        }
        return in_array($user['role'], ['admin', 'staff'], true) && setting('require_2fa_staff') === '1';
    }

    /** Hide most of an email: cyrene@gmail.com => cy****@gmail.com */
    public static function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        return mb_substr($name, 0, 2) . str_repeat('*', max(2, mb_strlen($name) - 2)) . '@' . $domain;
    }
}
