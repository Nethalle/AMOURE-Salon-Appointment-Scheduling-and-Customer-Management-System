<?php
// Login, customer registration and logout.

declare(strict_types=1);

require_once __DIR__ . '/../services/AuthCodes.php';

class AuthController
{
    public function login(): void
    {
        if ($user = current_user()) {
            redirect(home_for_role($user['role']));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();

            $email    = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            if ($wait = $this->lockedMinutes($email)) {
                remember_old(['email' => $email]);
                flash('error', "Too many wrong passwords. Please wait $wait minute" . ($wait === 1 ? '' : 's') . ' and try again.');
                redirect('login');
            }

            $stmt = db()->prepare(
                'SELECT * FROM users WHERE email = :email LIMIT 1'
            );
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                log_activity('account', 'login_failed', 'Failed login attempt for ' . $email . '.',
                    $user ? ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']] : ['id' => null, 'name' => 'Unknown', 'role' => 'guest']);
                remember_old(['email' => $email]);
                flash('error', 'Incorrect email or password.');
                redirect('login');
            }

            if (!(int) $user['is_active']) {
                remember_old(['email' => $email]);
                flash('error', 'This account has been deactivated. Please contact the salon.');
                redirect('login');
            }

            // 2-step verification: email a code first (unless this browser is remembered).
            if (AuthCodes::required($user) && !AuthCodes::isTrustedDevice((int) $user['id'])) {
                $this->startTwoStep($user);
            }

            $this->finishLogin($user, 'Logged in.');
        }

        view('public/login', ['title' => 'Log in', 'layout' => 'auth']);
    }

    /** Log the person in and send them where they were going. */
    private function finishLogin(array $user, string $logText): void
    {
        login_user($user);
        log_activity('account', 'login', $logText, ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']]);
        flash('success', 'Welcome back, ' . $user['name'] . '!');
        $next = $_SESSION['after_login'] ?? null;
        unset($_SESSION['after_login'], $_SESSION['pending_2fa']);
        if (is_array($next) && is_string($next['page'] ?? null) && preg_match('#^[a-z/-]+$#', $next['page'])) {
            $page = $next['page'];
            unset($next['page']);
            redirect($page, array_filter($next, 'is_scalar'));
        }
        redirect(home_for_role($user['role']));
    }

    /** Password was right: email a sign-in code and go to the code page. */
    private function startTwoStep(array $user): void
    {
        try {
            $error = AuthCodes::send($user, 'login');
        } catch (Throwable $e) {
            error_log('[AMOURE] 2-step setup error: ' . $e->getMessage());
            $error = 'setup';
        }
        if ($error !== null) {
            log_activity('account', '2fa_send_failed', 'Could not email a sign-in code.', ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']]);
            flash('error', $error === 'setup'
                ? '2-step verification is not set up yet. Ask the admin to import database/auth-codes.sql.'
                : 'We could not email your sign-in code right now. Please try again in a minute, or contact the salon.');
            redirect('login');
        }
        session_regenerate_id(true);
        $_SESSION['pending_2fa'] = [
            'id' => (int) $user['id'], 'name' => $user['name'], 'role' => $user['role'],
            'email' => $user['email'], 'at' => time(),
        ];
        log_activity('account', '2fa_code_sent', 'Password correct. Sign-in code emailed.', ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']]);
        redirect('verify-login');
    }

    /** Step 2 of logging in: type the emailed code. */
    public function verifyLogin(): void
    {
        $p = $_SESSION['pending_2fa'] ?? null;
        if (!is_array($p) || time() - (int) $p['at'] > AuthCodes::MINUTES * 60) {
            unset($_SESSION['pending_2fa']);
            flash('error', 'Your sign-in expired. Please enter your password again.');
            redirect('login');
        }
        $actor = ['id' => $p['id'], 'name' => $p['name'], 'role' => $p['role']];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();

            if (($_POST['action'] ?? '') === 'resend') {
                [$count, $ago] = AuthCodes::recent((int) $p['id'], 'login');
                if ($ago < AuthCodes::RESEND_WAIT) {
                    flash('error', 'Please wait ' . (AuthCodes::RESEND_WAIT - $ago) . ' seconds before asking for a new code.');
                } elseif ($count >= AuthCodes::MAX_PER_HOUR) {
                    flash('error', 'Too many codes asked for. Please try again later.');
                } else {
                    $error = AuthCodes::send(['id' => $p['id'], 'name' => $p['name'], 'email' => $p['email']], 'login');
                    flash($error ? 'error' : 'success', $error ? 'We could not send a new code. Please try again.' : 'A new code was sent. Older codes no longer work.');
                    $_SESSION['pending_2fa']['at'] = time();
                }
                redirect('verify-login');
            }

            $result = AuthCodes::verify((int) $p['id'], 'login', (string) ($_POST['code'] ?? ''));
            if ($result === 'ok') {
                $stmt = db()->prepare('SELECT * FROM users WHERE id = :id AND is_active = 1');
                $stmt->execute(['id' => $p['id']]);
                $user = $stmt->fetch();
                if (!$user) {
                    unset($_SESSION['pending_2fa']);
                    flash('error', 'This account has been deactivated. Please contact the salon.');
                    redirect('login');
                }
                if (!empty($_POST['remember'])) {
                    AuthCodes::trustDevice((int) $user['id']);
                }
                $this->finishLogin($user, 'Logged in with 2-step verification' . (!empty($_POST['remember']) ? ' (device remembered for 30 days).' : '.'));
            }

            log_activity('account', '2fa_failed', 'Wrong or expired sign-in code (' . $result . ').', $actor);
            flash('error', match ($result) {
                'wrong'    => 'That code is not right. Check the latest email and try again.',
                'too_many' => 'Too many wrong codes. Click "Send a new code".',
                default    => 'That code has expired. Click "Send a new code".',
            });
            redirect('verify-login');
        }

        view('public/verify-login', [
            'title'  => 'Enter your code',
            'layout' => 'auth',
            'masked' => AuthCodes::maskEmail((string) $p['email']),
        ]);
    }

    /** Forgot password, step 1: ask for a reset code. */
    public function forgotPassword(): void
    {
        if ($user = current_user()) {
            redirect(home_for_role($user['role']));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                remember_old(['email' => $email]);
                flash('error', 'Enter the email address you log in with.');
                redirect('forgot-password');
            }

            try {
                $stmt = db()->prepare('SELECT id, name, email, role, is_active FROM users WHERE email = :email LIMIT 1');
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch();
                if ($user && (int) $user['is_active']) {
                    [$count, $ago] = AuthCodes::recent((int) $user['id'], 'reset');
                    $actor = ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']];
                    if ($count < AuthCodes::MAX_PER_HOUR && $ago >= AuthCodes::RESEND_WAIT) {
                        $error = AuthCodes::send($user, 'reset');
                        log_activity('account', 'reset_requested', $error ? 'Asked for a password reset code (email failed).' : 'Asked for a password reset code.', $actor);
                        if ($error) {
                            flash('error', 'We could not send the email right now. Please try again in a minute.');
                            redirect('forgot-password');
                        }
                    }
                } else {
                    log_activity('account', 'reset_unknown', 'Password reset asked for an email with no active account: ' . $email . '.', ['id' => null, 'name' => 'Unknown', 'role' => 'guest']);
                }
            } catch (PDOException $e) {
                error_log('[AMOURE] Reset setup error: ' . $e->getMessage());
                flash('error', 'Password reset is not set up yet. Ask the salon admin to import database/auth-codes.sql.');
                redirect('forgot-password');
            }

            // Same answer whether or not the email has an account, so nobody can test which emails are registered.
            $_SESSION['reset_email'] = $email;
            flash('success', 'If ' . $email . ' has an account, we sent it a 6-digit code. Check your inbox and Spam folder.');
            redirect('reset-password');
        }

        view('public/forgot-password', ['title' => 'Forgot password', 'layout' => 'auth']);
    }

    /** Forgot password, step 2: type the code and a new password. */
    public function resetPassword(): void
    {
        if ($user = current_user()) {
            redirect(home_for_role($user['role']));
        }
        $email = (string) ($_SESSION['reset_email'] ?? '');
        if ($email === '') {
            redirect('forgot-password');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $code     = (string) ($_POST['code'] ?? '');
            $password = (string) ($_POST['password'] ?? '');
            $confirm  = (string) ($_POST['password_confirm'] ?? '');

            if (strlen($password) < 8) {
                flash('error', 'Your new password must be at least 8 characters.');
                redirect('reset-password');
            }
            if ($password !== $confirm) {
                flash('error', 'The two passwords do not match.');
                redirect('reset-password');
            }

            $stmt = db()->prepare('SELECT id, name, role FROM users WHERE email = :email AND is_active = 1 LIMIT 1');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();
            $result = $user ? AuthCodes::verify((int) $user['id'], 'reset', $code) : 'wrong';

            if ($result !== 'ok') {
                if ($user) {
                    log_activity('account', 'reset_failed', 'Wrong or expired reset code (' . $result . ').', ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']]);
                }
                flash('error', $result === 'too_many' || $result === 'expired' || $result === 'none'
                    ? 'That code no longer works. Ask for a new one below.'
                    : 'That code is not right. Check the latest email and try again.');
                redirect('reset-password');
            }

            db()->prepare('UPDATE users SET password_hash = :hash WHERE id = :id')
                ->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $user['id']]);
            AuthCodes::forgetDevices((int) $user['id']); // remembered browsers must sign in again
            unset($_SESSION['reset_email']);
            log_activity('account', 'password_reset', 'Password reset with an emailed code.', ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']]);
            remember_old(['email' => $email]);
            flash('success', 'Your password was changed. You can log in now.');
            redirect('login');
        }

        view('public/reset-password', [
            'title'  => 'Reset password',
            'layout' => 'auth',
            'email'  => $email,
        ]);
    }

    private const MAX_FAILED_PER_EMAIL = 5;   // wrong passwords for one account
    private const MAX_FAILED_PER_IP    = 20;  // wrong passwords from one computer/network (any accounts)
    private const LOCK_MINUTES         = 15;

    /**
     * Brute-force protection: after 5 wrong passwords for the same email (or 20 from the same
     * IP address) within 15 minutes, logging in is paused. Uses the activity log's
     * "login_failed" entries, so it needs no extra table. Returns minutes left (0 = allowed).
     */
    private function lockedMinutes(string $email): int
    {
        try {
            $since = date('Y-m-d H:i:s', time() - self::LOCK_MINUTES * 60);
            $wait  = 0;
            foreach ([
                ['details = :v', 'Failed login attempt for ' . $email . '.', self::MAX_FAILED_PER_EMAIL],
                ['ip_address = :v', substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45), self::MAX_FAILED_PER_IP],
            ] as [$where, $value, $max]) {
                $stmt = db()->prepare(
                    "SELECT created_at FROM activity_logs
                      WHERE action = 'login_failed' AND created_at >= :since AND $where
                      ORDER BY created_at DESC LIMIT $max"
                );
                $stmt->execute(['since' => $since, 'v' => $value]);
                $times = $stmt->fetchAll(PDO::FETCH_COLUMN);
                if (count($times) >= $max) {
                    // Unlocks 15 minutes after the oldest of the last $max failures.
                    $unlock = strtotime((string) end($times)) + self::LOCK_MINUTES * 60;
                    $wait = max($wait, (int) ceil(($unlock - time()) / 60));
                }
            }
            return max(0, $wait);
        } catch (Throwable $e) {
            return 0; // activity log not set up yet: don't block anyone
        }
    }

    public function register(): void
    {
        if ($user = current_user()) {
            redirect(home_for_role($user['role']));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();

            $name     = trim((string) ($_POST['name'] ?? ''));
            $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
            $phone    = trim((string) ($_POST['phone'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $confirm  = (string) ($_POST['password_confirm'] ?? '');

            $errors = [];
            if ($name === '' || mb_strlen($name) > 100) {
                $errors[] = 'Enter your full name (up to 100 characters).';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
                $errors[] = 'Enter a valid email address.';
            }
            if ($phone !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
                $errors[] = 'Enter a valid phone number (digits, spaces, + or -).';
            }
            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters.';
            }
            if ($password !== $confirm) {
                $errors[] = 'Passwords do not match.';
            }

            if (!$errors) {
                $check = db()->prepare('SELECT 1 FROM users WHERE email = :email');
                $check->execute(['email' => $email]);
                if ($check->fetch()) {
                    $errors[] = 'An account with this email already exists. Try logging in.';
                }
            }

            if ($errors) {
                remember_old(['name' => $name, 'email' => $email, 'phone' => $phone]);
                foreach ($errors as $error) {
                    flash('error', $error);
                }
                redirect('register');
            }

            $pdo = db();
            $pdo->beginTransaction();
            try {
                $insertUser = $pdo->prepare(
                    "INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :hash, 'customer')"
                );
                $insertUser->execute([
                    'name'  => $name,
                    'email' => $email,
                    'hash'  => password_hash($password, PASSWORD_DEFAULT),
                ]);
                $userId = (int) $pdo->lastInsertId();

                // If staff already recorded this person as a walk-in with the same email,
                // link that profile (and its history) to the new account instead of making a duplicate.
                $link = $pdo->prepare(
                    'UPDATE customers SET user_id = :uid, full_name = :name, phone = COALESCE(NULLIF(:phone, \'\'), phone)
                     WHERE email = :email AND user_id IS NULL LIMIT 1'
                );
                $link->execute(['uid' => $userId, 'name' => $name, 'phone' => $phone, 'email' => $email]);

                if ($link->rowCount() === 0) {
                    $insertCustomer = $pdo->prepare(
                        'INSERT INTO customers (user_id, full_name, phone, email) VALUES (:uid, :name, :phone, :email)'
                    );
                    $insertCustomer->execute([
                        'uid'   => $userId,
                        'name'  => $name,
                        'phone' => $phone !== '' ? $phone : null,
                        'email' => $email,
                    ]);
                }

                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }

            login_user(['id' => $userId, 'name' => $name, 'role' => 'customer']);
            log_activity('account', 'register', 'Created a customer account.');
            flash('success', 'Your account is ready. Welcome to AMOURÉ SALON!');
            redirect(home_for_role('customer'));
        }

        view('public/register', ['title' => 'Create an account', 'layout' => 'auth']);
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('home');
        }
        csrf_check();

        log_activity('account', 'logout', 'Logged out.');
        logout_user();
        start_session();
        flash('success', 'You have been logged out.');
        redirect('login');
    }
}
