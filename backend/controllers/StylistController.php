<?php
// Services and Stylists module: stylists and the services each one performs (admin only).
// Stylists are deactivated, never deleted, so past appointments keep their stylist.

declare(strict_types=1);

class StylistController
{
    /** List all stylists + add a new one. */
    public function index(): void
    {
        require_role(['admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            [$data, $serviceIds, $errors] = $this->validate($_POST);

            if ($errors) {
                remember_old($_POST);
                foreach ($errors as $error) {
                    flash('error', $error);
                }
                redirect('admin/stylists');
            }

            $pdo = db();
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('INSERT INTO stylists (full_name, specialty) VALUES (:full_name, :specialty)');
                $stmt->execute($data);
                $this->saveServices((int) $pdo->lastInsertId(), $serviceIds);
                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }

            logged_flash('stylists', 'create', 'Stylist "' . $data['full_name'] . '" added.');
            redirect('admin/stylists');
        }

        $stylists = db()->query(
            'SELECT id, full_name, specialty, is_active FROM stylists ORDER BY is_active DESC, full_name'
        )->fetchAll();

        // Attach each stylist's service names.
        $names = [];
        $rows = db()->query(
            'SELECT ss.stylist_id, s.name
               FROM stylist_services ss
               JOIN services s ON s.id = ss.service_id
              ORDER BY s.name'
        )->fetchAll();
        foreach ($rows as $row) {
            $names[(int) $row['stylist_id']][] = $row['name'];
        }
        foreach ($stylists as &$stylist) {
            $stylist['services'] = $names[(int) $stylist['id']] ?? [];
        }
        unset($stylist);

        view('admin/stylists', [
            'title'    => 'Stylists',
            'stylists' => $stylists,
            'services' => $this->activeServices(),
            'checked'  => array_map('intval', (array) ($_SESSION['old']['services'] ?? [])),
        ]);
    }

    /** Edit one stylist and the services they perform. */
    public function edit(): void
    {
        require_role(['admin']);

        $id = (int) ($_GET['id'] ?? 0);
        $stylist = $this->find($id);
        if (!$stylist) {
            flash('error', 'Stylist not found.');
            redirect('admin/stylists');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            [$data, $serviceIds, $errors] = $this->validate($_POST);

            if ($errors) {
                remember_old($_POST);
                foreach ($errors as $error) {
                    flash('error', $error);
                }
                redirect('admin/stylists/edit', ['id' => $id]);
            }

            $pdo = db();
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('UPDATE stylists SET full_name = :full_name, specialty = :specialty WHERE id = :id');
                $stmt->execute($data + ['id' => $id]);
                $this->saveServices($id, $serviceIds);
                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }

            logged_flash('stylists', 'update', 'Stylist "' . $data['full_name'] . '" updated.');
            redirect('admin/stylists');
        }

        $stmt = db()->prepare('SELECT service_id FROM stylist_services WHERE stylist_id = :id');
        $stmt->execute(['id' => $id]);
        $current = array_map('intval', array_column($stmt->fetchAll(), 'service_id'));

        view('admin/stylist-edit', [
            'title'    => 'Edit stylist',
            'stylist'  => $stylist,
            'services' => $this->activeServices(),
            'checked'  => isset($_SESSION['old']) ? array_map('intval', (array) ($_SESSION['old']['services'] ?? [])) : $current,
        ]);
    }

    /** Activate / deactivate a stylist. */
    public function toggle(): void
    {
        require_role(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/stylists');
        }
        csrf_check();

        $id = (int) ($_POST['id'] ?? 0);
        $stylist = $this->find($id);
        if (!$stylist) {
            flash('error', 'Stylist not found.');
            redirect('admin/stylists');
        }

        $newState = (int) $stylist['is_active'] ? 0 : 1;
        $stmt = db()->prepare('UPDATE stylists SET is_active = :active WHERE id = :id');
        $stmt->execute(['active' => $newState, 'id' => $id]);

        logged_flash('stylists', $newState ? 'activate' : 'deactivate', 'Stylist "' . $stylist['full_name'] . '" ' . ($newState ? 'activated.' : 'deactivated. They can no longer be booked.'));
        redirect('admin/stylists');
    }

    // ---------- helpers ----------

    private function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = db()->prepare('SELECT * FROM stylists WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    private function activeServices(): array
    {
        return db()->query('SELECT id, name, category FROM services WHERE is_active = 1 ORDER BY name')->fetchAll();
    }

    /** Replace the stylist's service list. */
    private function saveServices(int $stylistId, array $serviceIds): void
    {
        $pdo = db();
        $pdo->prepare('DELETE FROM stylist_services WHERE stylist_id = :id')->execute(['id' => $stylistId]);

        $insert = $pdo->prepare('INSERT INTO stylist_services (stylist_id, service_id) VALUES (:stylist, :service)');
        foreach ($serviceIds as $serviceId) {
            $insert->execute(['stylist' => $stylistId, 'service' => $serviceId]);
        }
    }

    /** @return array{0: array, 1: int[], 2: string[]} [clean data, service ids, errors] */
    private function validate(array $input): array
    {
        $name      = trim((string) ($input['full_name'] ?? ''));
        $specialty = trim((string) ($input['specialty'] ?? ''));

        $errors = [];
        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = 'Enter the stylist\'s name (up to 100 characters).';
        }
        if (mb_strlen($specialty) > 100) {
            $errors[] = 'Specialty can be up to 100 characters.';
        }

        // Keep only ids of services that really exist and are active.
        $requested = array_unique(array_filter(array_map('intval', (array) ($input['services'] ?? []))));
        $validIds  = array_map('intval', array_column($this->activeServices(), 'id'));
        $serviceIds = array_values(array_intersect($requested, $validIds));

        if (!$serviceIds) {
            $errors[] = 'Choose at least one service this stylist performs.';
        }

        return [
            ['full_name' => $name, 'specialty' => $specialty !== '' ? $specialty : null],
            $serviceIds,
            $errors,
        ];
    }
}
