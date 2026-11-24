<?php /** @var array $reviews  @var string $status  @var array $counts  @var array $stats */
$total    = (int) ($stats['total'] ?? 0);
$avg      = $total ? (float) $stats['avg_rating'] : 0;
$positive = $total ? round((int) $stats['positive'] / $total * 100) : 0;
$tabs = [
    'all'      => ['All', array_sum($counts)],
    'pending'  => ['Pending', $counts['pending']],
    'approved' => ['Approved', $counts['approved']],
    'removed'  => ['Removed', $counts['removed']],
];
$statusBadge = ['pending' => 'badge-pending', 'approved' => 'badge-approved', 'removed' => 'badge-removed'];
$starRow = static function (int $n): string {
    $out = '<span class="rev-stars" aria-label="' . $n . ' out of 5 stars">';
    for ($i = 1; $i <= 5; $i++) {
        $out .= icon('star', 'icon' . ($i > $n ? ' off' : ''));
    }
    return $out . '</span>';
};
?>
<div class="ph">
    <div>
        <h1>Reviews</h1>
        <p>Customer feedback and ratings</p>
    </div>
</div>

<div class="kpis kpis-3">
    <div class="kpi">
        <span class="kpi-icon"><?= icon('star') ?></span>
        <div>
            <span class="kpi-label">Average Rating</span>
            <span class="kpi-value"><?= $total ? number_format($avg, 1) : '—' ?> <small>/ 5</small></span>
            <?= $starRow((int) round($avg)) ?>
        </div>
    </div>
    <div class="kpi">
        <span class="kpi-icon"><?= icon('message') ?></span>
        <div>
            <span class="kpi-label">Total Reviews</span>
            <span class="kpi-value"><?= $total ?></span>
            <span class="kpi-note"><?= $counts['pending'] ?> waiting for approval</span>
        </div>
    </div>
    <div class="kpi">
        <span class="kpi-icon"><?= icon('thumbs') ?></span>
        <div>
            <span class="kpi-label">Positive Reviews</span>
            <span class="kpi-value"><?= $positive ?>%</span>
            <span class="kpi-note">Rated 4 stars or higher</span>
        </div>
    </div>
</div>

<section class="panel panel-flush">
    <nav class="tabbar" aria-label="Review status" style="padding:0 16px;margin:0">
        <?php foreach ($tabs as $key => [$label, $n]): ?>
            <a href="<?= e(url('admin/reviews', $key === 'all' ? [] : ['status' => $key])) ?>" <?= $status === $key ? 'aria-current="page"' : '' ?>>
                <?= e($label) ?> <span class="count"><?= (int) $n ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if (!$reviews): ?>
        <p class="empty">No reviews here.</p>
    <?php else: ?>
        <div class="rev-list">
            <?php foreach ($reviews as $r): ?>
                <article class="rev-item">
                    <span class="avatar-i"><?= e(initials($r['customer'])) ?></span>
                    <div class="rev-body">
                        <div class="rev-top">
                            <strong><?= e($r['customer']) ?></strong>
                            <small><?= e(fmt_date(substr($r['created_at'], 0, 10))) ?></small>
                        </div>
                        <?= $starRow((int) $r['rating']) ?>
                        <p><?= $r['comment'] !== null && $r['comment'] !== '' ? nl2br(e($r['comment'])) : '<span class="muted">No comment.</span>' ?></p>
                        <p class="rev-svc"><?= e($r['service']) ?> with <?= e($r['stylist']) ?> · <?= e(fmt_date($r['appt_date'])) ?></p>
                    </div>
                    <div class="rev-side">
                        <span class="badge <?= e($statusBadge[$r['status']] ?? '') ?>"><?= e(ucfirst($r['status'])) ?></span>
                        <details class="dots">
                            <summary aria-label="Actions"><?= icon('more') ?></summary>
                            <div class="menu menu-right">
                                <?php foreach (['approve' => ['Approve', 'check', ['pending', 'removed']], 'remove' => ['Remove', 'x', ['pending', 'approved']]] as $action => [$label, $ic, $from]):
                                    if (!in_array($r['status'], $from, true)) { continue; } ?>
                                    <form method="post" action="<?= e(url('admin/reviews/moderate')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= e($r['id']) ?>">
                                        <input type="hidden" name="action" value="<?= e($action) ?>">
                                        <input type="hidden" name="back" value="<?= e($status) ?>">
                                        <button type="submit" class="<?= $action === 'remove' ? 'danger' : '' ?>"><?= icon($ic) ?> <?= e($label) ?></button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
