<?php /** @var array $stylists  @var array $services  @var int[] $checked */ ?>
<div class="page-head">
    <h1 class="card-title">Stylists</h1>
    <p class="muted">Stylists customers can book, and the services each one performs.</p>
</div>

<div class="stack">
    <section class="card">
        <h2 class="section-title">Add a stylist</h2>
        <?php if (!$services): ?>
            <p class="muted">Add at least one active <a href="<?= e(url('admin/services')) ?>">service</a> first.</p>
        <?php else: ?>
        <form method="post" action="<?= e(url('admin/stylists')) ?>" class="form form-grid" novalidate>
            <?= csrf_field() ?>

            <div class="field">
                <label for="full_name">Full name</label>
                <input type="text" id="full_name" name="full_name" value="<?= old('full_name') ?>" required maxlength="100">
            </div>

            <div class="field">
                <label for="specialty">Specialty (optional)</label>
                <input type="text" id="specialty" name="specialty" value="<?= old('specialty') ?>" maxlength="100">
            </div>

            <fieldset class="checks">
                <legend>Services this stylist performs</legend>
                <div class="check-list">
                <?php foreach ($services as $s): ?>
                    <label class="check">
                        <input type="checkbox" name="services[]" value="<?= e($s['id']) ?>" <?= in_array((int) $s['id'], $checked, true) ? 'checked' : '' ?>>
                        <?= e($s['name']) ?>
                    </label>
                <?php endforeach; ?>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add stylist</button>
            </div>
        </form>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2 class="section-title">All stylists</h2>
        <?php if (!$stylists): ?>
            <p class="muted">No stylists yet. Add the first one.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Stylist</th>
                            <th scope="col">Specialty</th>
                            <th scope="col">Services</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($stylists as $st): ?>
                        <tr class="<?= (int) $st['is_active'] ? '' : 'row-inactive' ?>">
                            <td><?= e($st['full_name']) ?></td>
                            <td><?= e($st['specialty'] ?? '—') ?></td>
                            <td class="wrap"><?= $st['services'] ? e(implode(', ', $st['services'])) : '<span class="muted">None</span>' ?></td>
                            <td>
                                <span class="badge <?= (int) $st['is_active'] ? 'badge-completed' : 'badge-cancelled' ?>">
                                    <?= (int) $st['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="<?= e(url('admin/stylists/edit', ['id' => $st['id']])) ?>" class="btn btn-small btn-ghost">Edit</a>
                                    <form method="post" action="<?= e(url('admin/stylists/toggle')) ?>" class="inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= e($st['id']) ?>">
                                        <button type="submit" class="btn btn-small btn-ghost">
                                            <?= (int) $st['is_active'] ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
