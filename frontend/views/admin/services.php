<?php /** @var array $services  @var string $category  @var array $stylistsPerCat */
// Filters (all optional): ?q=gel  ?status=active|inactive  ?sort=newest|price_asc|price_desc|name  ?sub=Root Color
$q      = trim((string) ($_GET['q'] ?? ''));
$status = in_array($_GET['status'] ?? '', ['active', 'inactive'], true) ? $_GET['status'] : '';
$sort   = in_array($_GET['sort'] ?? '', ['newest', 'price_asc', 'price_desc', 'name'], true) ? $_GET['sort'] : 'name';
$sub    = trim((string) ($_GET['sub'] ?? ''));

$groups     = group_services($services);
$activeRows = array_filter($services, fn ($s) => (int) $s['is_active']);
$activeGrp  = group_services($activeRows);
$mainCats   = array_diff_key($activeGrp, array_flip(SEPARATE_CATEGORIES), ['Other' => 1]);

/** "Root Color - Deluxe" => "Root Color" (used for the small type chips). */
$baseName = static fn (string $name): string => trim(preg_split('/\s+[-–(]\s*/u', $name)[0]);

$keep = static function (array $s) use ($status): bool {
    return $status === '' || ($status === 'active') === (bool) (int) $s['is_active'];
};
$sortRows = static function (array $rows) use ($sort): array {
    usort($rows, match ($sort) {
        'newest'     => fn ($a, $b) => (int) $b['id'] <=> (int) $a['id'],
        'price_asc'  => fn ($a, $b) => (float) $a['price'] <=> (float) $b['price'],
        'price_desc' => fn ($a, $b) => (float) $b['price'] <=> (float) $a['price'],
        default      => fn ($a, $b) => [(int) $b['is_active'], $a['name']] <=> [(int) $a['is_active'], $b['name']],
    });
    return $rows;
};

$matches = [];
$shown   = [];
$subs    = [];
if ($category !== '') {
    $catRows = array_values(array_filter($groups[$category] ?? [], $keep));
    foreach ($catRows as $s) { $subs[$baseName($s['name'])] = ($subs[$baseName($s['name'])] ?? 0) + 1; }
    $rows = $catRows;
    if ($sub !== '') { $rows = array_filter($rows, fn ($s) => $baseName($s['name']) === $sub); }
    if ($q !== '') { $rows = array_filter($rows, fn ($s) => str_contains(mb_strtolower($s['name']), mb_strtolower($q))); }
    $shown = [$category => $sortRows(array_values($rows))];
} elseif ($q !== '' || $status !== '') {
    $needle  = mb_strtolower($q);
    $matches = array_filter($services, static fn ($s) => $keep($s) && ($needle === ''
        || str_contains(mb_strtolower($s['name']), $needle) || str_contains(mb_strtolower((string) ($s['category'] ?? '')), $needle)));
    $shown = array_map($sortRows, group_services($matches));
}

$toolbar = static function () use ($category, $q, $status, $sort, $sub): string {
    ob_start(); ?>
    <form method="get" action="index.php" class="svc-toolbar" role="search" data-autosubmit>
        <input type="hidden" name="page" value="admin/services">
        <?php if ($category !== ''): ?><input type="hidden" name="category" value="<?= e($category) ?>"><?php endif; ?>
        <?php if ($sub !== ''): ?><input type="hidden" name="sub" value="<?= e($sub) ?>"><?php endif; ?>
        <div class="search-box grow">
            <?= icon('search') ?>
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= $category !== '' ? 'Search in ' . e($category) . '…' : 'Search all services, e.g. rebond, gel, wax…' ?>" aria-label="Search services">
        </div>
        <?php if ($category !== ''): ?>
            <label class="sel-pill"><span class="sr-only">Sort</span>
                <select name="sort" aria-label="Sort services">
                    <?php foreach (['name' => 'Sort: Name', 'newest' => 'Sort: Newest', 'price_asc' => 'Price: Low to high', 'price_desc' => 'Price: High to low'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= $sort === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <label class="sel-pill"><span class="sr-only">Status</span>
            <select name="status" aria-label="Filter by status">
                <option value="">All Status</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </label>
        <?php if ($q !== '' || $status !== ''): ?><a href="<?= e(url('admin/services', $category !== '' ? ['category' => $category] : [])) ?>" class="btn btn-ghost btn-small">Clear</a><?php endif; ?>
    </form>
    <?php return (string) ob_get_clean();
};
?>
<?php if ($category === '' && ($q !== '' || $status !== '')): ?>
<div class="ph">
    <div>
        <p><a href="<?= e(url('admin/services')) ?>" class="back-link"><?= icon('chev-left', 'icon icon-sm') ?> All categories</a></p>
        <h1>Search results</h1>
        <p><?= count($matches) ?> service<?= count($matches) === 1 ? '' : 's' ?> found<?= $q !== '' ? ' for "' . e($q) . '"' : '' ?></p>
    </div>
    <button type="button" class="btn btn-primary" data-dialog="add-service"><?= icon('plus') ?> Add Service</button>
</div>
<?= $toolbar() ?>
<?php if (!$matches): ?>
    <section class="panel"><p class="empty">No service matches your search. Try another word.</p></section>
<?php endif; ?>

<?php elseif ($category === ''): ?>
<div class="ph">
    <div>
        <h1>Services Management</h1>
        <p>Manage your salon services and pricing.</p>
    </div>
    <button type="button" class="btn btn-primary" data-dialog="add-service"><?= icon('plus') ?> Add Service</button>
</div>

<?= $toolbar() ?>

<div class="sec-head"><h2>Salon Services</h2></div>
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

<div class="sec-head sec-line"><h2>Separate Management</h2></div>
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

<?php else: $total = array_sum($subs); ?>
<div class="ph">
    <div>
        <p><a href="<?= e(url('admin/services')) ?>" class="back-link"><?= icon('chev-left', 'icon icon-sm') ?> All categories</a></p>
        <h1 class="cat-head"><span class="cat-icon"><?= icon(category_icon($category)) ?></span> <?= $category === 'Haircut' ? 'Haircut · Senior Hairstylists' : e($category) ?></h1>
        <p><?= $total ?> service<?= $total === 1 ? '' : 's' ?><?php if ($category === 'Haircut'): ?> · <a href="<?= e(url('admin/stylists')) ?>">Manage stylists</a><?php endif; ?></p>
    </div>
    <button type="button" class="btn btn-primary" data-dialog="add-service"><?= icon('plus') ?> Add Service</button>
</div>

<?= $toolbar() ?>

<?php if (count($subs) > 1): $keepParams = array_filter(['category' => $category, 'q' => $q, 'status' => $status, 'sort' => $sort !== 'name' ? $sort : '']); ?>
<nav class="sub-chips" aria-label="Service types">
    <a href="<?= e(url('admin/services', $keepParams)) ?>" <?= $sub === '' ? 'aria-current="page"' : '' ?>>All <span><?= $total ?></span></a>
    <?php foreach ($subs as $name => $n): ?>
        <a href="<?= e(url('admin/services', $keepParams + ['sub' => $name])) ?>" <?= $sub === $name ? 'aria-current="page"' : '' ?>><?= e($name) ?> <span><?= $n ?></span></a>
    <?php endforeach; ?>
</nav>
<?php endif; ?>
<?php endif; ?>

<?php foreach ($shown as $cat => $rows): ?>
    <?php if (!$rows): ?><section class="panel"><p class="empty"><?= $q !== '' || $status !== '' || $sub !== '' ? 'No service here matches your filters.' : 'No services in this category yet.' ?></p></section><?php continue; endif; ?>
    <section class="cat-section">
        <?php if ($category === ''): ?>
            <h2 class="cat-title"><span class="cat-icon"><?= icon(category_icon($cat)) ?></span> <?= e($cat) ?> <small><?= count($rows) ?></small></h2>
        <?php endif; ?>
        <div class="price-grid">
        <?php foreach ($rows as $s): $on = (int) $s['is_active']; $n = (int) $s['stylist_count']; ?>
            <article class="price-card <?= $on ? '' : 'is-off' ?>">
                <div class="price-top">
                    <h3><?= e($s['name']) ?></h3>
                    <span class="badge <?= $on ? 'badge-active' : 'badge-inactive' ?>"><?= $on ? 'Active' : 'Inactive' ?></span>
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
                <p class="price-amount"><?= e(str_replace(".00", "", peso($s["price"]))) ?></p>
                <div class="price-meta">
                    <span><?= icon('clock') ?> <?= e(fmt_duration((int) $s['duration_minutes'])) ?></span>
                    <span><?= icon('user') ?> <?= $n ?> stylist<?= $n === 1 ? '' : 's' ?></span>
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
