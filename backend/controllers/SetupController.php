<?php
// First-time setup: create the very first admin account.
// Only works while the system has NO admin yet. Once one exists, this page is closed for good,
// so it is safe to leave in place after deployment.

declare(strict_types=1);

class SetupController
{
    public function index(): void
    {
        if (has_admin()) {
            flash('error', 'The system is already set up. Please log in.');
            redirect('login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();

            $name     = trim((string) ($_POST['name'] ?? ''));
            $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            $confirm  = (string) ($_POST['password_confirm'] ?? '');

            $errors = [];
            if ($name === '' || mb_strlen($name) > 100) {
                $errors[] = 'Enter your name (up to 100 characters).';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
                $errors[] = 'Enter a valid email address.';
            }
            if (strlen($password) < 10 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
                $errors[] = 'Use a strong password: at least 10 characters, with letters and numbers.';
            }
            if ($password !== $confirm) {
                $errors[] = 'The two passwords do not match.';
            }

            if ($errors) {
                remember_old(['name' => $name, 'email' => $email]);
                foreach ($errors as $error) {
                    flash('error', $error);
                }
                redirect('setup');
            }

            $pdo = db();
            $pdo->beginTransaction();
            try {
                // Check again inside the transaction, so two people can't both become the first admin.
                if ((int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' FOR UPDATE")->fetchColumn() > 0) {
                    $pdo->rollBack();
                    flash('error', 'The system is already set up. Please log in.');
                    redirect('login');
                }
                $existing = $pdo->prepare('SELECT id FROM users WHERE email = :email');
                $existing->execute(['email' => $email]);
                if ($id = $existing->fetchColumn()) {
                    // The email already has a (customer) account: make it the admin.
                    $pdo->prepare("UPDATE users SET name = :name, password_hash = :hash, role = 'admin', is_active = 1 WHERE id = :id")
                        ->execute(['name' => $name, 'hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id]);
                } else {
                    $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :hash, 'admin')")
                        ->execute(['name' => $name, 'email' => $email, 'hash' => password_hash($password, PASSWORD_DEFAULT)]);
                    $id = (int) $pdo->lastInsertId();
                }
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }

            session_regenerate_id(true);
            login_user(['id' => (int) $id, 'name' => $name, 'role' => 'admin']);
            log_activity('account', 'setup_admin', 'Created the first admin account (' . $email . ').');
            flash('success', 'Welcome, ' . $name . '! Your admin account is ready. Next: Settings > Salon Profile.');
            redirect('admin/dashboard');
        }

        view('public/setup', ['title' => 'Set up AMOURÉ SALON', 'layout' => 'auth']);
    }
}
