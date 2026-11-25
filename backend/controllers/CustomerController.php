<?php
// Customer Management (staff and admin): search, add, edit, and view appointment history.
// Customers are never deleted, so appointment history and reports stay complete.

declare(strict_types=1);

class CustomerController
{
    /** List + search + add a customer. */
    public function index(): void
    {
        require_role(['admin', 'staff']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            [$data, $errors] = $this->validate($_POST);

            if ($errors) {
                remember_old($_POST);
                foreach ($errors as $error) {
                    flash('error', $error);
                }
                redirect('staff/customers');
            }

            $pdo = db();
            $pdo->prepare('INSERT INTO customers (full_name, phone, email, notes) VALUES (:full_name, :phone, :email, :notes)')
                ->execute($data);
            $id = (int) $pdo->lastInsertId();

            logged_flash('customers', 'create', 'Customer "' . $data['full_name'] . '" added.');
            redirect('staff/customers/view', ['id' => $id]);
        }

        $q    = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['p'] ?? 1));
        $per  = 10;

        $params = [];
        // Only real customers: walk-ins (no login) and customer accounts. Admin/staff logins are hidden.
        $where  = "WHERE (c.user_id IS NULL OR u.role = 'customer')";
        if ($q !== '') {
            $where .= ' AND (c.full_name LIKE :q1 OR c.phone LIKE :q2 OR c.email LIKE :q3)';
            $like  = '%' . $q . '%';
            $params = ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }

        $stmt = db()->prepare("SELECT COUNT(*) FROM customers c LEFT JOIN users u ON u.id = c.user_id $where");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();
        $pages = max(1, (int) ceil($total / $per));
        $page  = min($page, $pages);

        $stmt = db()->prepare(
            "SELECT c.id, c.full_name, c.phone, c.email, c.user_id,
                    COUNT(a.id) AS total,
                    SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) AS completed,
                    MAX(CASE WHEN a.status IN ('pending','confirmed','completed') THEN a.appt_date END) AS last_appt
               FROM customers c
               LEFT JOIN users u ON u.id = c.user_id
               LEFT JOIN appointments a ON a.customer_id = c.id
               $where
              GROUP BY c.id, c.full_name, c.phone, c.email, c.user_id
              ORDER BY c.full_name
              LIMIT $per OFFSET " . (($page - 1) * $per)
        );
        $stmt->execute($params);

        view('staff/customers', [
            'title'     => 'Customers',
            'customers' => $stmt->fetchAll(),
            'q'         => $q,
            'page'      => $page,
            'pages'     => $pages,
            'total'     => $total,
            'per'       => $per,
        ]);
    }

    /** One customer's profile (editable) and appointment history. */
    public function show(): void
    {
        require_role(['admin', 'staff']);

        $id = (int) ($_GET['id'] ?? 0);
        $customer = $this->find($id);
        if (!$customer) {
            flash('error', 'Customer not found.');
            redirect('staff/customers');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            [$data, $errors] = $this->validate($_POST);

            if ($errors) {
                remember_old($_POST);
                foreach ($errors as $error) {
                    flash('error', $error);
                }
                redirect('staff/customers/view', ['id' => $id]);
            }

            db()->prepare(
                'UPDATE customers SET full_name = :full_name, phone = :phone, email = :email, notes = :notes WHERE id = :id'
            )->execute($data + ['id' => $id]);

            logged_flash('customers', 'update', 'Customer details saved for "' . $data['full_name'] . '".');
            redirect('staff/customers/view', ['id' => $id]);
        }

        $stmt = db()->prepare(
            'SELECT a.id, a.appt_date, a.start_time, a.end_time, a.status, a.source,
                    s.name AS service, s.price, st.full_name AS stylist
               FROM appointments a
               JOIN services s  ON s.id  = a.service_id
               JOIN stylists st ON st.id = a.stylist_id
              WHERE a.customer_id = :id
              ORDER BY a.appt_date DESC, a.start_time DESC'
        );
        $stmt->execute(['id' => $id]);
        $history = $stmt->fetchAll();

        $summary = ['total' => count($history), 'completed' => 0, 'cancelled' => 0, 'no_show' => 0, 'spent' => 0.0];
        foreach ($history as $a) {
            if (isset($summary[$a['status']])) {
                $summary[$a['status']]++;
            }
            if ($a['status'] === 'completed') {
                $summary['spent'] += (float) $a['price'];
            }
        }

        view('staff/customer-view', [
            'title'    => $customer['full_name'],
            'customer' => $customer,
            'history'  => $history,
            'summary'  => $summary,
        ]);
    }

    // ---------- helpers ----------

    private function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = db()->prepare(
            'SELECT c.*, u.email AS account_email
               FROM customers c
               LEFT JOIN users u ON u.id = c.user_id
              WHERE c.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** @return array{0: array, 1: string[]} */
    private function validate(array $in): array
    {
        $name  = trim((string) ($in['full_name'] ?? ''));
        $phone = trim((string) ($in['phone'] ?? ''));
        $email = strtolower(trim((string) ($in['email'] ?? '')));
        $notes = trim((string) ($in['notes'] ?? ''));

        $errors = [];
        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = 'Enter the customer\'s full name (up to 100 characters).';
        }
        if ($phone !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
            $errors[] = 'Enter a valid phone number (digits, spaces, + or -).';
        }
        if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150)) {
            $errors[] = 'Enter a valid email address, or leave it empty.';
        }
        if (mb_strlen($notes) > 1000) {
            $errors[] = 'Notes can be up to 1,000 characters.';
        }

        return [[
            'full_name' => $name,
            'phone'     => $phone !== '' ? $phone : null,
            'email'     => $email !== '' ? $email : null,
            'notes'     => $notes !== '' ? $notes : null,
        ], $errors];
    }
}
