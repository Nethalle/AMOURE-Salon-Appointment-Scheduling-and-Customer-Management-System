<?php /** @var array $reviews  @var string $status  @var array $counts */
$tabs = [
    'pending'  => 'Waiting (' . $counts['pending'] . ')',
    'approved' => 'Approved (' . $counts['approved'] . ')',
    'removed'  => 'Removed (' . $counts['removed'] . ')',
    'all'      => 'All',
];
$badge = ['pending' => 'badge-pending', 'approved' => 'badge-completed', 'removed' => 'badge-cancelled'];
$label = ['pending' => 'Waiting', 'approved' => 'Approved', 'removed' => 'Removed'];
?>
<div class="page-head">
    <h1 class="card-title">Reviews</h1>
    <p class="muted">Approved reviews appear on the home page as customer vouches.</p>
</div>

<nav class="tabs" aria-label="Review status">
    <?php foreach ($tabs as $key => $text): ?>
        <a href="<?= e(url('admin/reviews', ['status' => $key])) ?>" class="tab" <?= $status === $key ? 'aria-current="page"' : '' ?>><?= e($text) ?></a>
    <?php endforeach; ?>
</nav>

<?php if (!$reviews): ?>
    <section class="card"><p class="muted">No reviews here.</p></section>
<?php else: ?>
    <div class="review-list">
        <?php foreach ($reviews as $r): ?>
            <article class="card review-card">
                <div class="head-row">
                    <div>
                        <span class="stars" aria-label="<?= e($r['rating']) ?> out of 5 stars"><?= e(stars((int) $r['rating'])) ?></span>
                        <span class="badge <?= e($badge[$r['status']]) ?> badge-inline"><?= e($label[$r['status']]) ?></span>
                    </div>
                    <small class="muted"><?= e(date('M j, Y', strtotime($r['created_at']))) ?></small>
                </div>
                <p class="review-text"><?= $r['comment'] !== null ? '“' . e($r['comment']) . '”' : '<span class="muted">No written review.</span>' ?></p>
                <p class="muted review-meta">
                    <?= e($r['customer']) ?> · <?= e($r['service']) ?> with <?= e($r['stylist']) ?> · <?= e(fmt_date($r['appt_date'])) ?>
                </p>
                <div class="actions">
                    <?php if ($r['status'] !== 'approved'): ?>
                        <form method="post" action="<?= e(url('admin/reviews/moderate')) ?>" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($r['id']) ?>">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="back" value="<?= e($status) ?>">
                            <button type="submit" class="btn btn-small btn-primary">Approve</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($r['status'] !== 'removed'): ?>
                        <form method="post" action="<?= e(url('admin/reviews/moderate')) ?>" class="inline-form" data-confirm="Remove this review? It won't show on the home page.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($r['id']) ?>">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="back" value="<?= e($status) ?>">
                            <button type="submit" class="btn btn-small btn-ghost">Remove</button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
