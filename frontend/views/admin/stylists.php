<?php /** @var array $stylists  @var array $services  @var int[] $checked */ ?>
<div class="ph">
    <div>
        <h1>Stylists</h1>
        <p>Manage your salon stylists</p>
    </div>
    <?php if ($services): ?>
        <button type="button" class="btn btn-primary" data-dialog="add-stylist"><?= icon('plus') ?> Add Stylist</button>
    <?php else: ?>
        <a href="<?= e(url('admin/services')) ?>" class="btn btn-ghost">Add a service first</a>
    <?php endif; ?>
</div>

<?php if (!$stylists): ?>
    <section class="panel"><p class="empty">No stylists yet. Add the first one.</p></section>
<?php else: ?>
    <div class="sty-grid">
        <?php foreach ($stylists as $st): $on = (int) $st['is_active']; ?>
            <article class="sty-card <?= $on ? '' : 'is-off' ?>">
                <div class="sty-photo">
                    <?php if ($file = stylist_photo_file($st)): ?>
                        <?= photo($file, $st['full_name']) ?>
                    <?php else: ?>
                        <span><?= e(initials($st['full_name'])) ?></span>
                    <?php endif; ?>
                </div>
                <h3><?= e($st['full_name']) ?></h3>
                <p><?= e($st['specialty'] ?: 'Stylist') ?></p>
                <?php if (!$on): ?><span class="badge badge-inactive" style="margin-top:8px">Inactive</span><?php endif; ?>
                <div class="sty-tags">
                    <?php foreach (array_slice($st['services'], 0, 3) as $name): ?><span><?= e($name) ?></span><?php endforeach; ?>
                    <?php if (count($st['services']) > 3): ?><span>+<?= count($st['services']) - 3 ?></span><?php endif; ?>
                </div>
                <div class="sty-foot">
                    <a href="<?= e(url('admin/stylists/edit', ['id' => $st['id']])) ?>" class="icon-btn" title="Edit" aria-label="Edit <?= e($st['full_name']) ?>"><?= icon('edit') ?></a>
                    <form method="post" action="<?= e(url('admin/stylists/toggle')) ?>"
                          <?= $on ? 'data-confirm="Deactivate this stylist? Customers will no longer be able to book them."' : '' ?>>
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e($st['id']) ?>">
                        <button type="submit" class="icon-btn" title="<?= $on ? 'Deactivate' : 'Activate' ?>" aria-label="<?= $on ? 'Deactivate' : 'Activate' ?> <?= e($st['full_name']) ?>"><?= icon('power') ?></button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($services): ?>
<dialog class="modal" id="add-stylist" <?= has_old() ? 'data-open' : '' ?>>
    <form method="post" action="<?= e(url('admin/stylists')) ?>" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <div class="modal-head">
            <h2>Add Stylist</h2>
            <button type="button" class="modal-x" data-close aria-label="Close"><?= icon('x') ?></button>
        </div>
        <div class="modal-body">
            <div class="f-grid">
                <div class="field">
                    <label for="full_name">Full name</label>
                    <input type="text" id="full_name" name="full_name" value="<?= old('full_name') ?>" required maxlength="100">
                </div>
                <div class="field">
                    <label for="specialty">Specialty (optional)</label>
                    <input type="text" id="specialty" name="specialty" value="<?= old('specialty') ?>" maxlength="100">
                </div>
            </div>
            <div class="field photo-field">
                <label for="photo">Photo (optional)</label>
                <div class="photo-pick">
                    <span class="photo-preview" data-photo-preview><?= icon('image') ?></span>
                    <span>
                        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" data-photo-input>
                        <small class="hint">JPG, PNG or WEBP, up to 3 MB. A square photo looks best. It shows on the website and when customers pick a stylist.</small>
                    </span>
                </div>
            </div>
            <div>
                <span class="lbl" style="display:block;font-weight:600;font-size:13px;margin-bottom:8px">Services this stylist performs</span>
                <div class="svc-check-scroll"><?= service_checkboxes($services, $checked) ?></div>
            </div>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-ghost" data-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Add stylist</button>
        </div>
    </form>
</dialog>
<?php endif; ?>
