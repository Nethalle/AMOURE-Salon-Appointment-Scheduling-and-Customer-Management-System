<?php
// Login, customer registration and logout.

declare(strict_types=1);

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

            $stmt = db()->prepare(
                'SELECT id, name, password_hash, role, is_active FROM users WHERE email = :email LIMIT 1'
            );
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                remember_old(['email' => $email]);
                flash('error', 'Incorrect email or password.');
                redirect('login');
            }

            if (!(int) $user['is_active']) {
                remember_old(['email' => $email]);
                flash('error', 'This account has been deactivated. Please contact the salon.');
                redirect('login');
            }

            login_user($user);
            flash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect(home_for_role($user['role']));
        }

        view('public/login', ['title' => 'Log in']);
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
            flash('success', 'Your account is ready. Welcome to AMOURÉ SALON!');
            redirect(home_for_role('customer'));
        }

        view('public/register', ['title' => 'Create an account']);
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('home');
        }
        csrf_check();

        logout_user();
        start_session();
        flash('success', 'You have been logged out.');
        redirect('login');
    }
}
