<?php
// User accounts (admin only) + "change my password" (everyone).
// Safety rules: an admin can't deactivate or demote themselves, and the last active admin can't be removed.

declare(strict_types=1);

class UserController
{
    private const MIN_PASSWORD = 8;

    /** List accounts + add a staff/admin account. */
    public function index(): void
    {
        require_role(['admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();

            $name     = trim((string) ($_POST['name'] ?? ''));
            $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
            $role     = (string) ($_POST['role'] ?? '');
            $password = (string) ($_POST['password'] ?? '');

            $errors = $this->validateProfile($name, $email);
            if (!in_array($role, ['staff', 'admin'], true)) {
                $errors[] = 'Choose Staff or Admin. Customers create their own accounts.';
            }
            if (strlen($password) < self::MIN_PASSWORD) {
                $errors[] = 'The starting password must be at least ' . self::MIN_PASSWORD . ' characters.';
            }
            if (!$errors && $this->emailTaken($email)) {
                $errors[] = 'An account with this email already exists.';
            }

            if ($errors) {
                remember_old(['name' => $name, 'email' => $email, 'role' => $role]);
                foreach ($errors as $error) {
                    flash('error', $error);
                }
                redirect('admin/users');
            }

            db()->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :hash, :role)')
                ->execute(['name' => $name, 'email' => $email, 'hash' => password_hash($password, PASSWORD_DEFAULT), 'role' => $role]);

            logged_flash('users', 'create', ucfirst($role) . ' account created for ' . $name . '. Share the starting password privately and ask them to change it after logging in.');
            redirect('admin/users');
        }

        $role = (string) ($_GET['role'] ?? '');
        $params = [];
        $where = '';
        if (in_array($role, ['admin', 'staff', 'customer'], true)) {
            $where = 'WHERE role = :role';
            $params['role'] = $role;
        } else {
            $role = '';
        }

        $stmt = db()->prepare(
            "SELECT id, name, email, role, is_active, created_at FROM users $where
              ORDER BY CASE role WHEN 'admin' THEN 1 WHEN 'staff' THEN 2 ELSE 3 END, name"
        );
        $stmt->execute($params);

        $roleCounts = ['admin' => 0, 'staff' => 0, 'customer' => 0];
        foreach (db()->query('SELECT role, COUNT(*) AS n FROM users GROUP BY role')->fetchAll() as $row) {
            $roleCounts[$row['role']] = (int) $row['n'];
        }

        view('admin/users', [
            'title'  => 'User accounts',
            'roleCounts' => $roleCounts,
            'users'  => $stmt->fetchAll(),
            'role'   => $role,
            'me'     => current_user(),
        ]);
    }

    /** Edit name / email / role, and reset the password. */
    public function edit(): void
    {
        $me = require_role(['admin']);

        $id   = (int) ($_GET['id'] ?? 0);
        $user = $this->find($id);
        if (!$user) {
            flash('error', 'Account not found.');
            redirect('admin/users');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();

            if (($_POST['form'] ?? '') === 'password') {
                $password = (string) ($_POST['new_password'] ?? '');
                if (strlen($password) < self::MIN_PASSWORD) {
                    flash('error', 'The new password must be at least ' . self::MIN_PASSWORD . ' characters.');
                    redirect('admin/users/edit', ['id' => $id]);
                }
                db()->prepare('UPDATE users SET password_hash = :hash WHERE id = :id')
                    ->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id]);
                logged_flash('users', 'reset_password', 'Password reset for ' . $user['name'] . '. Share it privately and ask them to change it.');
                redirect('admin/users/edit', ['id' => $id]);
            }

            $name  = trim((string) ($_POST['name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $role  = (string) ($_POST['role'] ?? $user['role']);

            $errors = $this->validateProfile($name, $email);
            if (!$errors && $this->emailTaken($email, $id)) {
                $errors[] = 'Another account already uses this email.';
            }
            if (!in_array($role, ['customer', 'staff', 'admin'], true)) {
                $errors[] = 'Choose Customer, Staff or Admin.';
            }
            if ($role !== $user['role'] && $id === (int) $me['id']) {
                $errors[] = 'You can\'t change your own role. Ask another admin to do it.';
            } elseif ($role !== $user['role'] && $user['role'] === 'admin') {
                if ($this->activeAdminCount() <= 1 && (int) $user['is_active']) {
                    $errors[] = 'This is the last active admin. Make someone else an admin first.';
                }
            }

            if ($errors) {
                remember_old(['name' => $name, 'email' => $email, 'role' => $role]);
                foreach ($errors as $error) {
                    flash('error', $error);
                }
                redirect('admin/users/edit', ['id' => $id]);
            }

            $pdo = db();
            $pdo->beginTransaction();
            try {
                $pdo->prepare('UPDATE users SET name = :name, email = :email, role = :role WHERE id = :id')
                    ->execute(['name' => $name, 'email' => $email, 'role' => $role, 'id' => $id]);
                if ($role === 'customer') {
                    $this->ensureCustomerProfile($id, $name, $email);
                }
                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }

            $roleNames = ['customer' => 'Customer', 'staff' => 'Staff', 'admin' => 'Admin'];
            logged_flash('users', 'update', 'Account for ' . $name . ' saved'
                . ($role !== $user['role'] ? ' and changed from ' . $roleNames[$user['role']] . ' to ' . $roleNames[$role] . '.' : '.'));
            redirect('admin/users');
        }

        view('admin/user-edit', ['title' => 'Edit account', 'account' => $user, 'isMe' => $id === (int) $me['id']]);
    }

    /** Activate / deactivate an account. Deactivated users can't log in and are signed out. */
    public function toggle(): void
    {
        $me = require_role(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/users');
        }
        csrf_check();

        $id   = (int) ($_POST['id'] ?? 0);
        $user = $this->find($id);
        if (!$user) {
            flash('error', 'Account not found.');
            redirect('admin/users');
        }

        $activate = !(int) $user['is_active'];
        if (!$activate) {
            if ($id === (int) $me['id']) {
                flash('error', 'You can\'t deactivate your own account.');
                redirect('admin/users');
            }
            if ($user['role'] === 'admin' && $this->activeAdminCount() <= 1) {
                flash('error', 'This is the last active admin and can\'t be deactivated.');
                redirect('admin/users');
            }
        }

        db()->prepare('UPDATE users SET is_active = :a WHERE id = :id')->execute(['a' => $activate ? 1 : 0, 'id' => $id]);
        logged_flash('users', $activate ? 'reactivate' : 'deactivate', $user['name'] . '\'s account was ' . ($activate ? 'reactivated.' : 'deactivated. They can no longer log in.'));
        redirect('admin/users');
    }

    /** Everyone: change your own password. */
    public function changePassword(): void
    {
        $me = require_login();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();

            $current = (string) ($_POST['current_password'] ?? '');
            $new     = (string) ($_POST['new_password'] ?? '');
            $confirm = (string) ($_POST['confirm_password'] ?? '');

            $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = :id');
            $stmt->execute(['id' => $me['id']]);
            $hash = (string) $stmt->fetchColumn();

            $errors = [];
            if (!password_verify($current, $hash)) {
                $errors[] = 'Your current password is incorrect.';
            }
            if (strlen($new) < self::MIN_PASSWORD) {
                $errors[] = 'Your new password must be at least ' . self::MIN_PASSWORD . ' characters.';
            }
            if ($new !== $confirm) {
                $errors[] = 'The new passwords do not match.';
            }
            if (!$errors && password_verify($new, $hash)) {
                $errors[] = 'Choose a password different from your current one.';
            }

            if ($errors) {
                foreach ($errors as $error) {
                    flash('error', $error);
                }
                redirect('account/password');
            }

            db()->prepare('UPDATE users SET password_hash = :hash WHERE id = :id')
                ->execute(['hash' => password_hash($new, PASSWORD_DEFAULT), 'id' => $me['id']]);
            session_regenerate_id(true);

            logged_flash('account', 'change_password', 'Your password was changed.');
            redirect(home_for_role($me['role']));
        }

        view('account/password', ['title' => 'Change password']);
    }

    // ---------- helpers ----------

    private function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = db()->prepare('SELECT id, name, email, role, is_active, created_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Customers need a customer profile to book. If the account has none, link a walk-in
     * record with the same email (keeping its history), or create a new profile.
     */
    private function ensureCustomerProfile(int $userId, string $name, string $email): void
    {
        $pdo = db();
        $has = $pdo->prepare('SELECT 1 FROM customers WHERE user_id = :uid');
        $has->execute(['uid' => $userId]);
        if ($has->fetchColumn()) {
            return;
        }

        $link = $pdo->prepare('SELECT id FROM customers WHERE email = :email AND user_id IS NULL ORDER BY id LIMIT 1');
        $link->execute(['email' => $email]);
        $walkInId = $link->fetchColumn();

        if ($walkInId) {
            $pdo->prepare('UPDATE customers SET user_id = :uid WHERE id = :id')->execute(['uid' => $userId, 'id' => $walkInId]);
        } else {
            $pdo->prepare('INSERT INTO customers (user_id, full_name, email) VALUES (:uid, :name, :email)')
                ->execute(['uid' => $userId, 'name' => $name, 'email' => $email]);
        }
    }

    private function emailTaken(string $email, int $exceptId = 0): bool
    {
        $stmt = db()->prepare('SELECT 1 FROM users WHERE email = :email AND id <> :id');
        $stmt->execute(['email' => $email, 'id' => $exceptId]);
        return (bool) $stmt->fetchColumn();
    }

    private function activeAdminCount(): int
    {
        return (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1")->fetchColumn();
    }

    private function validateProfile(string $name, string $email): array
    {
        $errors = [];
        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = 'Enter a name (up to 100 characters).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            $errors[] = 'Enter a valid email address.';
        }
        return $errors;
    }
}
