<?php /** @var array $services */ ?>
<div class="ph">
    <div>
        <h1>Services</h1>
        <p>Manage your salon services</p>
    </div>
    <button type="button" class="btn btn-primary" data-dialog="add-service"><?= icon('plus') ?> Add Service</button>
</div>

<?php if (!$services): ?>
    <section class="panel"><p class="empty">No services yet. Add the first one.</p></section>
<?php else: ?>
    <div class="svc-grid">
        <?php foreach ($services as $s): $on = (int) $s['is_active']; $img = 'services/' . slugify($s['name']) . '.jpg'; ?>
            <article class="svc-card <?= $on ? '' : 'is-off' ?>">
                <div class="svc-media">
                    <?php if (photo_exists($img)): ?>
                        <?= photo($img, $s['name']) ?>
                    <?php else: ?>
                        <?= icon(service_icon($s['name'])) ?>
                    <?php endif; ?>
                </div>
                <?php if (!$on): ?><span class="badge badge-inactive svc-flag">Inactive</span><?php endif; ?>
                <div class="svc-body">
                    <div>
                        <h3><?= e($s['name']) ?></h3>
                        <div class="svc-meta">
                            <span><?= e(fmt_duration((int) $s['duration_minutes'])) ?></span> ·
                            <strong><?= e(peso($s['price'])) ?></strong>
                        </div>
                        <span class="cell-sub" style="margin-top:4px"><?= (int) $s['stylist_count'] ?> stylist<?= (int) $s['stylist_count'] === 1 ? '' : 's' ?></span>
                    </div>
                    <details class="dots">
                        <summary aria-label="Actions for <?= e($s['name']) ?>"><?= icon('more') ?></summary>
                        <div class="menu menu-right">
                            <a href="<?= e(url('admin/services/edit', ['id' => $s['id']])) ?>"><?= icon('edit') ?> Edit</a>
                            <form method="post" action="<?= e(url('admin/services/toggle')) ?>"
                                  <?= $on ? 'data-confirm="Deactivate this service? Customers will no longer be able to book it."' : '' ?>>
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= e($s['id']) ?>">
                                <button type="submit" class="<?= $on ? 'danger' : '' ?>"><?= icon('power') ?> <?= $on ? 'Deactivate' : 'Activate' ?></button>
                            </form>
                        </div>
                    </details>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <p class="hint" style="margin-top:16px">Tip: add a photo named <code>frontend/assets/img/services/&lt;service-name&gt;.jpg</code> (e.g. <code>hair-color.jpg</code>) and it shows here and on the website.</p>
<?php endif; ?>

<dialog class="modal" id="add-service" <?= has_old() ? 'data-open' : '' ?>>
    <form method="post" action="<?= e(url('admin/services')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="modal-head">
            <h2>Add Service</h2>
            <button type="button" class="modal-x" data-close aria-label="Close"><?= icon('x') ?></button>
        </div>
        <div class="modal-body">
            <div class="field">
                <label for="name">Service name</label>
                <input type="text" id="name" name="name" value="<?= old('name') ?>" required maxlength="100">
            </div>
            <div class="f-grid">
                <div class="field">
                    <label for="price">Price (₱)</label>
                    <input type="number" id="price" name="price" value="<?= old('price') ?>" required min="0" max="999999" step="0.01">
                </div>
                <div class="field">
                    <label for="duration_minutes">Duration (minutes)</label>
                    <input type="number" id="duration_minutes" name="duration_minutes" value="<?= old('duration_minutes') ?>" required min="5" max="600" step="5">
                </div>
            </div>
            <small class="hint">The duration blocks the stylist's time when someone books.</small>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-ghost" data-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Add service</button>
        </div>
    </form>
</dialog>
