<?php /** @var array $stylists  @var array $services  @var int[] $checked */
// Filters: ?q=bea  ?status=active|inactive
$q      = trim((string) ($_GET['q'] ?? ''));
$status = in_array($_GET['status'] ?? '', ['active', 'inactive'], true) ? $_GET['status'] : '';
$total  = count($stylists);
$list   = array_filter($stylists, static function ($st) use ($q, $status) {
    if ($status !== '' && ($status === 'active') !== (bool) (int) $st['is_active']) { return false; }
    if ($q === '') { return true; }
    $hay = mb_strtolower($st['full_name'] . ' ' . $st['specialty'] . ' ' . implode(' ', $st['services']));
    return str_contains($hay, mb_strtolower($q));
});
?>
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

<form method="get" action="index.php" class="svc-toolbar" role="search" data-autosubmit>
    <input type="hidden" name="page" value="admin/stylists">
    <div class="search-box grow">
        <?= icon('search') ?>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search stylists by name, specialty or service…" aria-label="Search stylists">
    </div>
    <label class="sel-pill"><span class="sr-only">Status</span>
        <select name="status" aria-label="Filter by status">
            <option value="">All Status</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
    </label>
    <?php if ($q !== '' || $status !== ''): ?><a href="<?= e(url('admin/stylists')) ?>" class="btn btn-ghost btn-small">Clear</a><?php endif; ?>
</form>

<?php if (!$stylists): ?>
    <section class="panel"><p class="empty">No stylists yet. Add the first one.</p></section>
<?php elseif (!$list): ?>
    <section class="panel"><p class="empty">No stylist matches your search.</p></section>
<?php else: ?>
    <div class="sty-grid">
        <?php foreach ($list as $st): $on = (int) $st['is_active']; ?>
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
