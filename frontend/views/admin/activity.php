<?php /** @var array $logs  @var array $filters  @var int $total  @var int $pages  @var int $perPage  @var array $categories  @var bool $missing */
$roleBadge = ['admin' => 'badge-role-admin', 'staff' => 'badge-role-staff', 'customer' => 'badge-role-customer', 'guest' => 'badge-inactive'];
$catIcon = ['appointments' => 'calendar', 'customers' => 'users', 'services' => 'scissors', 'stylists' => 'user', 'reviews' => 'star',
            'users' => 'shield', 'settings' => 'settings', 'reports' => 'bar', 'account' => 'key'];
$keep = array_filter(['role' => $filters['role'], 'category' => $filters['category'], 'q' => $filters['q'], 'from' => $filters['from'], 'to' => $filters['to']], fn ($v) => $v !== '');
?>
<div class="ph">
    <div>
        <h1>Activity Log</h1>
        <p>Every action in the system, newest first. Entries are permanent and can't be edited or deleted.</p>
    </div>
    <span class="pill-soft"><?= icon('shield', 'icon icon-sm') ?> Read-only</span>
</div>

<?php if ($missing): ?>
    <div class="notice">The activity log table isn't set up yet. Import <strong>database/activity-log.sql</strong> in phpMyAdmin, then reload this page.</div>
<?php endif; ?>

<section class="panel panel-flush">
    <form method="get" action="index.php" class="toolbar" data-autosubmit style="padding:20px 24px 0">
        <input type="hidden" name="page" value="admin/activity">
        <select name="role" aria-label="Role">
            <option value="">All roles</option>
            <?php foreach (['admin' => 'Admin', 'staff' => 'Staff', 'customer' => 'Customer', 'guest' => 'Guest (not logged in)'] as $k => $label): ?>
                <option value="<?= $k ?>" <?= $filters['role'] === $k ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <select name="category" aria-label="Area">
            <option value="">All areas</option>
            <?php foreach ($categories as $k => $label): ?>
                <option value="<?= e($k) ?>" <?= $filters['category'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="from" value="<?= e($filters['from']) ?>" aria-label="From date">
        <input type="date" name="to" value="<?= e($filters['to']) ?>" aria-label="To date">
        <div class="search-box grow">
            <?= icon('search') ?>
            <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search name or action…" aria-label="Search">
        </div>
        <?php if ($keep): ?><a href="<?= e(url('admin/activity')) ?>" class="btn btn-ghost btn-small">Clear</a><?php endif; ?>
    </form>

    <?php if (!$logs): ?>
        <p class="empty"><?= $missing ? 'No log yet.' : 'No activity found.' ?></p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Date &amp; Time</th>
                        <th scope="col">Who</th>
                        <th scope="col">Area</th>
                        <th scope="col">What happened</th>
                        <th scope="col">IP address</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $l): ?>
                    <tr>
                        <td>
                            <span class="t-time"><?= e(date('g:i A', strtotime($l['created_at']))) ?></span>
                            <span class="cell-sub"><?= e(date('M j, Y', strtotime($l['created_at']))) ?></span>
                        </td>
                        <td>
                            <div class="cell-person">
                                <span class="avatar-i"><?= e(initials($l['user_name']) ?: '?') ?></span>
                                <span>
                                    <strong><?= e($l['user_name']) ?></strong>
                                    <span class="badge <?= e($roleBadge[$l['user_role']] ?? 'badge-inactive') ?>" style="margin-top:3px"><?= e(ucfirst($l['user_role'])) ?></span>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="log-area"><?= icon($catIcon[$l['category']] ?? 'sparkle', 'icon icon-sm') ?> <?= e($categories[$l['category']] ?? ucfirst($l['category'])) ?></span>
                            <span class="cell-sub"><?= e(str_replace('_', ' ', $l['action'])) ?></span>
                        </td>
                        <td class="log-details"><?= e($l['details']) ?></td>
                        <td><span class="cell-sub"><?= e($l['ip_address'] ?? '') ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php
    $pgRoute = 'admin/activity'; $pgQuery = $keep;
    $pgPage = $filters['p']; $pgPages = $pages; $pgTotal = $total; $pgPer = $perPage; $pgNoun = 'entries';
    require __DIR__ . '/../layouts/_pager.php';
    ?>
</section>
