<?php /** @var array $services */ ?>
<div class="page-head">
    <h1 class="card-title">Services</h1>
    <p class="muted">Services customers can book, with their price and how long they take.</p>
</div>

<div class="stack">
    <section class="card">
        <h2 class="section-title">Add a service</h2>
        <form method="post" action="<?= e(url('admin/services')) ?>" class="form form-grid" novalidate>
            <?= csrf_field() ?>

            <div class="field">
                <label for="name">Service name</label>
                <input type="text" id="name" name="name" value="<?= old('name') ?>" required maxlength="100">
            </div>

            <div class="field">
                <label for="price">Price (₱)</label>
                <input type="number" id="price" name="price" value="<?= old('price') ?>" required min="0" max="999999" step="0.01">
            </div>

            <div class="field">
                <label for="duration_minutes">Duration (minutes)</label>
                <input type="number" id="duration_minutes" name="duration_minutes" value="<?= old('duration_minutes') ?>" required min="5" max="600" step="5">
                <small class="hint">Blocks the stylist's time when someone books.</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add service</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2 class="section-title">All services</h2>
        <?php if (!$services): ?>
            <p class="muted">No services yet. Add the first one.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Service</th>
                            <th scope="col">Price</th>
                            <th scope="col">Duration</th>
                            <th scope="col">Stylists</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($services as $s): ?>
                        <tr class="<?= (int) $s['is_active'] ? '' : 'row-inactive' ?>">
                            <td><?= e($s['name']) ?></td>
                            <td><?= e(peso($s['price'])) ?></td>
                            <td><?= e($s['duration_minutes']) ?> min</td>
                            <td><?= e($s['stylist_count']) ?></td>
                            <td>
                                <span class="badge <?= (int) $s['is_active'] ? 'badge-completed' : 'badge-cancelled' ?>">
                                    <?= (int) $s['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="<?= e(url('admin/services/edit', ['id' => $s['id']])) ?>" class="btn btn-small btn-ghost">Edit</a>
                                    <form method="post" action="<?= e(url('admin/services/toggle')) ?>" class="inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= e($s['id']) ?>">
                                        <button type="submit" class="btn btn-small btn-ghost">
                                            <?= (int) $s['is_active'] ? 'Deactivate' : 'Activate' ?>
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
