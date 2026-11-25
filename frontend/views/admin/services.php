<?php /** @var array $services  @var string $category  @var array $stylistsPerCat */
$groups     = group_services($services);
$activeRows = array_filter($services, fn ($s) => (int) $s['is_active']);
$activeGrp  = group_services($activeRows);
$mainCats   = array_diff_key($activeGrp, array_flip(SEPARATE_CATEGORIES), ['Other' => 1]);
$shown      = $category !== '' ? [$category => $groups[$category] ?? []] : [];

// Search: ?q=gel (inside a category it only searches that category).
$q = trim((string) ($_GET['q'] ?? ''));
if ($q !== '') {
    $needle  = mb_strtolower($q);
    $matches = array_filter($services, static fn ($s) =>
        ($category === '' || ($s['category'] ?? '') === $category)
        && (str_contains(mb_strtolower($s['name']), $needle) || str_contains(mb_strtolower((string) ($s['category'] ?? '')), $needle)));
    $shown = group_services($matches);
}
$searchBox = static function (string $category, string $q): string {
    ob_start(); ?>
    <form method="get" action="index.php" class="toolbar svc-admin-search" role="search">
        <input type="hidden" name="page" value="admin/services">
        <?php if ($category !== ''): ?><input type="hidden" name="category" value="<?= e($category) ?>"><?php endif; ?>
        <div class="search-box grow">
            <?= icon('search') ?>
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= $category !== '' ? 'Search in ' . e($category) . '…' : 'Search all services, e.g. rebond, gel, wax…' ?>" aria-label="Search services">
        </div>
        <button type="submit" class="btn btn-ghost">Search</button>
        <?php if ($q !== ''): ?><a href="<?= e(url('admin/services', $category !== '' ? ['category' => $category] : [])) ?>" class="btn btn-ghost">Clear</a><?php endif; ?>
    </form>
    <?php return (string) ob_get_clean();
};
?>
<?php if ($category === '' && $q !== ''): ?>
<div class="ph ph-line">
    <div>
        <p><a href="<?= e(url('admin/services')) ?>" class="back-link"><?= icon('chev-left', 'icon icon-sm') ?> All categories</a></p>
        <h1>Search results</h1>
        <p><?= count($matches) ?> service<?= count($matches) === 1 ? '' : 's' ?> found for "<?= e($q) ?>"</p>
    </div>
    <button type="button" class="btn btn-primary" data-dialog="add-service"><?= icon('plus') ?> Add Service</button>
</div>
<?= $searchBox('', $q) ?>
<?php if (!$matches): ?>
    <section class="panel"><p class="empty">No service matches "<?= e($q) ?>". Try another word.</p></section>
<?php endif; ?>

<?php elseif ($category === ''): ?>
<div class="ph ph-line">
    <div>
        <h1>Services Management</h1>
        <p>Manage your salon services and pricing.</p>
    </div>
    <button type="button" class="btn btn-primary" data-dialog="add-service"><?= icon('plus') ?> Add Service</button>
</div>

<?= $searchBox('', '') ?>

<div class="sec-head">
    <h2>Salon Services</h2>
    <span class="pill-rose"><?= count($mainCats) ?> categories</span>
</div>
<div class="cat-grid">
    <?php foreach ($mainCats as $cat => $rows): $img = category_photo($cat); ?>
        <a class="cat-card" href="<?= e(url('admin/services', ['category' => $cat])) ?>">
            <span class="cat-photo">
                <?php if (photo_exists($img)): ?>
                    <?= photo($img, $cat) ?>
                <?php else: ?>
                    <?= icon(category_icon($cat)) ?>
                <?php endif; ?>
            </span>
            <span class="cat-info">
                <?= icon(category_icon($cat), 'icon cat-line-icon') ?>
                <strong><?= e($cat) ?></strong>
                <span><?= count($rows) ?> <?= $cat === 'Perming & Rebonding' ? 'option' : 'service' ?><?= count($rows) === 1 ? '' : 's' ?></span>
            </span>
            <?= icon('chev-right', 'icon cat-go') ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="sec-head sec-line">
    <h2>Separate Management</h2>
</div>
<?php foreach (SEPARATE_CATEGORIES as $cat): $n = $stylistsPerCat[$cat] ?? 0; ?>
    <a class="sep-row" href="<?= e(url('admin/services', ['category' => $cat])) ?>">
        <span class="sep-icon"><?= icon('users') ?></span>
        <span class="sep-text">
            <strong>Senior Hairstylists</strong>
            <span>Manage hairstylists and their service rates.</span>
        </span>
        <span class="pill-soft"><?= $n ?> stylist<?= $n === 1 ? '' : 's' ?></span>
        <?= icon('chev-right', 'icon sep-go') ?>
    </a>
<?php endforeach; ?>
<?php if (isset($groups['Other'])): ?>
    <p class="hint" style="margin-top:14px"><a href="<?= e(url('admin/services', ['category' => 'Other'])) ?>"><?= count($groups['Other']) ?> old / uncategorized services</a> (not shown to customers)</p>
<?php endif; ?>

<?php else: ?>
<div class="ph">
    <div>
        <p><a href="<?= e(url('admin/services')) ?>" class="back-link"><?= icon('chev-left', 'icon icon-sm') ?> All categories</a></p>
        <h1><span class="cat-icon cat-icon-lg"><?= icon(category_icon($category)) ?></span> <?= $category === 'Haircut' ? 'Haircut · Senior Hairstylists' : e($category) ?></h1>
        <p><?= count($shown[$category]) ?> service<?= count($shown[$category]) === 1 ? '' : 's' ?><?php if ($category === 'Haircut'): ?> · <a href="<?= e(url('admin/stylists')) ?>">Manage stylists →</a><?php endif; ?></p>
    </div>
    <button type="button" class="btn btn-primary" data-dialog="add-service"><?= icon('plus') ?> Add Service</button>
</div>

<?= $searchBox($category, $q) ?>
<?php if ($q !== '' && !$shown): ?>
    <section class="panel" style="margin-bottom:20px"><p class="empty">No service in <?= e($category) ?> matches "<?= e($q) ?>".</p></section>
<?php endif; ?>

<nav class="cat-chips" aria-label="Categories">
    <?php foreach ($groups as $cat => $rows): ?>
        <a href="<?= e(url('admin/services', ['category' => $cat])) ?>" <?= $category === $cat ? 'aria-current="page"' : '' ?>>
            <?= icon(category_icon($cat), 'icon icon-sm') ?> <?= e($cat) ?> <span><?= count($rows) ?></span>
        </a>
    <?php endforeach; ?>
</nav>
<?php endif; ?>

<?php foreach ($shown as $cat => $rows): ?>
    <?php if (!$rows): ?><section class="panel"><p class="empty">No services in this category yet.</p></section><?php continue; endif; ?>
    <section class="cat-section">
        <?php if ($category === '' && $q !== ''): ?>
            <h2 class="cat-title"><span class="kpi-icon"><?= icon(category_icon($cat)) ?></span> <?= e($cat) ?> <small><?= count($rows) ?></small></h2>
        <?php endif; ?>
        <div class="svc-grid">
        <?php foreach ($rows as $s): $on = (int) $s['is_active']; ?>
            <article class="svc-card svc-compact <?= $on ? '' : 'is-off' ?>">
                <div class="svc-body">
                    <div>
                        <h3><?= e($s['name']) ?> <?php if (!$on): ?><span class="badge badge-inactive">Inactive</span><?php endif; ?></h3>
                        <div class="svc-meta">
                            <span><?= icon('clock', 'icon icon-xs') ?> <?= e(fmt_duration((int) $s['duration_minutes'])) ?></span> ·
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
    </section>
<?php endforeach; ?>

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
            <div class="field">
                <label for="category">Category</label>
                <select id="category" name="category" required><?= category_options($_SESSION['old']['category'] ?? $category) ?></select>
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
