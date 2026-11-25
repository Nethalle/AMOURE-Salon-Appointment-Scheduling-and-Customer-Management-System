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
            [$upload, $photoError] = $this->checkPhoto();
            if ($photoError) {
                $errors[] = $photoError;
            }

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
                $newId = (int) $pdo->lastInsertId();
                $this->saveServices($newId, $serviceIds);
                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }

            if ($upload) {
                $this->storePhoto($newId, $upload);
            }
            logged_flash('stylists', 'create', 'Stylist "' . $data['full_name'] . '" added' . ($upload ? ' with a photo.' : '.') . ' They now show on the website.');
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
            [$upload, $photoError] = $this->checkPhoto();
            if ($photoError) {
                $errors[] = $photoError;
            }

            if ($errors) {
                remember_old($_POST);
                foreach ($errors as $error) {
                    flash('error', $error);
                }
                redirect('admin/stylists/edit', ['id' => $id]);
            }

            // A photo named after the old name would be lost on a rename: give it the stylist's id instead.
            $oldFile = stylist_photo_file($stylist);
            if ($oldFile && !str_starts_with(basename($oldFile), 'stylist-') && $data['full_name'] !== $stylist['full_name']) {
                @rename($this->imgDir() . basename($oldFile), $this->imgDir() . "stylist-$id.jpg");
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

            $photoNote = '';
            if ($upload) {
                $this->storePhoto($id, $upload);
                $photoNote = ' New photo uploaded.';
            } elseif (!empty($_POST['remove_photo'])) {
                $this->deletePhotos($id, $stylist['full_name']);
                $photoNote = ' Photo removed.';
            }
            logged_flash('stylists', 'update', 'Stylist "' . $data['full_name'] . '" updated.' . $photoNote);
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
    /** Folder where stylist photos are saved. */
    private function imgDir(): string
    {
        $dir = __DIR__ . '/../../frontend/assets/img/stylists/';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir;
    }

    /**
     * Check the optional "photo" upload. Returns [upload info or null, error message or null].
     * Only real JPG, PNG or WEBP pictures up to 3 MB are accepted.
     */
    private function checkPhoto(): array
    {
        $f = $_FILES['photo'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [null, null];
        }
        if ($f['error'] !== UPLOAD_ERR_OK) {
            return [null, $f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE
                ? 'The photo is too big. Use a picture under 3 MB.'
                : 'The photo could not be uploaded. Please try again.'];
        }
        if ($f['size'] > 3 * 1024 * 1024) {
            return [null, 'The photo is too big. Use a picture under 3 MB.'];
        }
        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime  = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) ?: '';
        if (!isset($types[$mime]) || @getimagesize($f['tmp_name']) === false) {
            return [null, 'The photo must be a JPG, PNG or WEBP picture.'];
        }
        return [['tmp' => $f['tmp_name'], 'ext' => $types[$mime]], null];
    }

    /** Save an uploaded photo as stylists/stylist-<id>.<ext>, replacing any older photo. */
    private function storePhoto(int $id, array $upload): void
    {
        $dir = $this->imgDir();
        foreach (['jpg', 'png', 'webp'] as $ext) {
            if (is_file($dir . "stylist-$id.$ext")) {
                @unlink($dir . "stylist-$id.$ext");
            }
        }
        if (!move_uploaded_file($upload['tmp'], $dir . "stylist-$id.{$upload['ext']}")) {
            flash('error', 'The details were saved, but the photo could not be saved. Check that the folder frontend/assets/img/stylists can be written to.');
        }
    }

    /** Remove the stylist's uploaded photo (and an older photo named after them). */
    private function deletePhotos(int $id, string $name): void
    {
        $dir = $this->imgDir();
        foreach (['jpg', 'png', 'webp'] as $ext) {
            @unlink($dir . "stylist-$id.$ext");
        }
        @unlink($dir . slugify($name) . '.jpg');
    }

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
