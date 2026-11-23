<?php
// Services and Stylists module: services (admin only).
// Services are deactivated, never deleted, so past appointments keep their service.

declare(strict_types=1);

class ServiceController
{
    /** List all services + add a new one. */
    public function index(): void
    {
        require_role(['admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            [$data, $errors] = $this->validate($_POST);

            if (!$errors && $this->nameTaken($data['name'])) {
                $errors[] = 'A service with this name already exists.';
            }

            if ($errors) {
                remember_old($_POST);
                foreach ($errors as $error) {
                    flash('error', $error);
                }
                redirect('admin/services');
            }

            $stmt = db()->prepare(
                'INSERT INTO services (name, price, duration_minutes) VALUES (:name, :price, :duration)'
            );
            $stmt->execute($data);

            flash('success', 'Service "' . $data['name'] . '" added.');
            redirect('admin/services');
        }

        $services = db()->query(
            'SELECT s.id, s.name, s.price, s.duration_minutes, s.is_active,
                    (SELECT COUNT(*) FROM stylist_services ss WHERE ss.service_id = s.id) AS stylist_count
               FROM services s
              ORDER BY s.is_active DESC, s.name'
        )->fetchAll();

        view('admin/services', ['title' => 'Services', 'services' => $services]);
    }

    /** Edit one service. */
    public function edit(): void
    {
        require_role(['admin']);

        $id = (int) ($_GET['id'] ?? 0);
        $service = $this->find($id);
        if (!$service) {
            flash('error', 'Service not found.');
            redirect('admin/services');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            [$data, $errors] = $this->validate($_POST);

            if (!$errors && $this->nameTaken($data['name'], $id)) {
                $errors[] = 'Another service already has this name.';
            }

            if ($errors) {
                remember_old($_POST);
                foreach ($errors as $error) {
                    flash('error', $error);
                }
                redirect('admin/services/edit', ['id' => $id]);
            }

            $stmt = db()->prepare(
                'UPDATE services SET name = :name, price = :price, duration_minutes = :duration WHERE id = :id'
            );
            $stmt->execute($data + ['id' => $id]);

            flash('success', 'Service "' . $data['name'] . '" updated.');
            redirect('admin/services');
        }

        view('admin/service-edit', ['title' => 'Edit service', 'service' => $service]);
    }

    /** Activate / deactivate a service. */
    public function toggle(): void
    {
        require_role(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/services');
        }
        csrf_check();

        $id = (int) ($_POST['id'] ?? 0);
        $service = $this->find($id);
        if (!$service) {
            flash('error', 'Service not found.');
            redirect('admin/services');
        }

        $newState = (int) $service['is_active'] ? 0 : 1;
        $stmt = db()->prepare('UPDATE services SET is_active = :active WHERE id = :id');
        $stmt->execute(['active' => $newState, 'id' => $id]);

        flash('success', 'Service "' . $service['name'] . '" ' . ($newState ? 'activated.' : 'deactivated. It can no longer be booked.'));
        redirect('admin/services');
    }

    // ---------- helpers ----------

    private function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = db()->prepare('SELECT * FROM services WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    private function nameTaken(string $name, int $exceptId = 0): bool
    {
        $stmt = db()->prepare('SELECT 1 FROM services WHERE name = :name AND id <> :id');
        $stmt->execute(['name' => $name, 'id' => $exceptId]);
        return (bool) $stmt->fetch();
    }

    /** @return array{0: array, 1: string[]} [clean data, errors] */
    private function validate(array $input): array
    {
        $name     = trim((string) ($input['name'] ?? ''));
        $price    = trim((string) ($input['price'] ?? ''));
        $duration = trim((string) ($input['duration_minutes'] ?? ''));

        $errors = [];
        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = 'Enter a service name (up to 100 characters).';
        }
        if (!is_numeric($price) || (float) $price < 0 || (float) $price > 999999) {
            $errors[] = 'Enter a price between 0 and 999,999.';
        }
        if (!ctype_digit($duration) || (int) $duration < 5 || (int) $duration > 600) {
            $errors[] = 'Enter a duration between 5 and 600 minutes.';
        }

        return [
            ['name' => $name, 'price' => round((float) $price, 2), 'duration' => (int) $duration],
            $errors,
        ];
    }
}
