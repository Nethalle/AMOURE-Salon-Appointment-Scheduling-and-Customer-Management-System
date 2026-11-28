<?php /** @var array $upcoming  @var array $past */
$completed = count(array_filter($past, fn ($a) => $a['status'] === 'completed'));
$reviews   = count(array_filter($past, fn ($a) => !empty($a['review_rating'])));
$toReview  = count(array_filter($past, fn ($a) => $a['status'] === 'completed' && empty($a['review_rating'])));
$focus     = (int) ($_GET['appt'] ?? 0);
$statuses  = array_values(array_unique(array_column($past, 'status')));
?>
<div class="book-hero">
    <div class="book-hero-text">
        <p class="book-kicker">My appointments</p>
        <h1>My Appointments</h1>
        <p>View your upcoming bookings and appointment history.</p>
    </div>
    <svg class="book-hero-art" viewBox="0 0 520 200" preserveAspectRatio="xMaxYMid slice" aria-hidden="true">
        <g class="bh-petals">
            <path d="M300 96c-18-40-6-72 14-86 14 26 10 58-14 86Z"/><path d="M300 96c34-28 68-26 86-8-24 20-56 22-86 8Z"/>
            <path d="M300 96c40 6 60 32 58 56-30-4-52-24-58-56Z"/><path d="M300 96c-4 40-30 62-56 62 2-30 24-52 56-62Z"/>
            <path d="M300 96c-38-10-56-40-50-62 30 8 48 30 50 62Z"/>
        </g>
        <g class="bh-petals bh-small">
            <path d="M210 44c-8-18-2-32 6-38 6 12 4 26-6 38Z"/><path d="M210 44c16-12 30-12 38-4-10 9-24 10-38 4Z"/>
            <path d="M210 44c18 2 26 14 26 24-14-2-24-10-26-24Z"/><path d="M210 44c-2 18-14 28-24 28 0-14 10-24 24-28Z"/><path d="M210 44c-16-4-24-18-22-28 14 4 22 14 22 28Z"/>
        </g>
        <circle cx="300" cy="96" r="9" class="bh-heart"/><circle cx="210" cy="44" r="4" class="bh-heart"/>
    </svg>
    <a href="<?= e(url('customer/book')) ?>" class="btn btn-primary book-hero-btn"><?= icon('plus') ?> Book Appointment</a>
</div>

<div class="my-stats">
    <div class="my-stat">
        <span class="my-stat-icon"><?= icon('calendar') ?></span>
        <div><span class="my-stat-label">Upcoming</span><strong><?= count($upcoming) ?></strong></div>
        <?= icon('calendar', 'icon my-stat-art') ?>
    </div>
    <div class="my-stat">
        <span class="my-stat-icon"><?= icon('check') ?></span>
        <div><span class="my-stat-label">Completed</span><strong><?= $completed ?></strong></div>
        <?= icon('award', 'icon my-stat-art') ?>
    </div>
    <div class="my-stat">
        <span class="my-stat-icon"><?= icon('star') ?></span>
        <div><span class="my-stat-label">Reviews</span><strong><?= $reviews ?></strong>
            <?php if ($toReview): ?><small><?= $toReview ?> visit<?= $toReview === 1 ? '' : 's' ?> waiting for your review</small><?php endif; ?></div>
        <?= icon('message', 'icon my-stat-art') ?>
    </div>
</div>

<section class="panel my-panel">
    <div class="panel-head"><h2><?= icon('calendar') ?> Upcoming Appointments</h2></div>
    <?php if (!$upcoming): ?>
        <div class="my-empty">
            <svg viewBox="0 0 140 110" aria-hidden="true">
                <rect x="14" y="26" width="84" height="74" rx="12" class="ea-page"/>
                <path d="M14 48V38a12 12 0 0 1 12-12h60a12 12 0 0 1 12 12v10Z" class="ea-top"/>
                <rect x="32" y="18" width="6" height="16" rx="3" class="ea-ring"/><rect x="74" y="18" width="6" height="16" rx="3" class="ea-ring"/>
                <path d="M40 76l10 10 20-20" class="ea-check" style="stroke:#B4556E"/>
                <path d="M108 26l10-12M114 40h16M104 12l2-10" class="my-empty-burst"/>
            </svg>
            <div>
                <strong>No upcoming appointments</strong>
                <p>You don't have any scheduled appointments yet.</p>
                <a href="<?= e(url('customer/book')) ?>" class="btn btn-primary btn-small">Book one now <?= icon('chev-right', 'icon icon-xs') ?></a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table my-table">
                <thead>
                    <tr><th scope="col">Booking #</th><th scope="col">Date</th><th scope="col">Time</th><th scope="col">Service</th><th scope="col">Stylist</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Actions</span></th></tr>
                </thead>
                <tbody>
                <?php foreach ($upcoming as $a): ?>
                    <tr id="appt-<?= e($a['id']) ?>" class="<?= $focus === (int) $a['id'] ? 'row-focus' : '' ?>">
                        <td><span class="ref"><?= e(appt_ref($a['id'])) ?></span></td>
                        <td><span class="cell-ic"><?= icon('calendar') ?><?= e(fmt_date($a['appt_date'])) ?></span></td>
                        <td><span class="cell-ic"><?= icon('clock') ?><?= e(fmt_time($a['start_time'])) ?> – <?= e(fmt_time($a['end_time'])) ?></span></td>
                        <td><span class="cell-ic"><?= icon('scissors') ?><?= e($a['service']) ?></span></td>
                        <td><span class="cell-ic"><?= icon('user') ?><?= e($a['stylist']) ?></span></td>
                        <td><span class="badge badge-<?= e($a['status']) ?>"><?= e($a['status'] === 'pending' ? 'Waiting for confirmation' : 'Confirmed') ?></span></td>
                        <td class="t-right">
                            <?php if ($a['can_cancel']): ?>
                                <form method="post" action="<?= e(url('customer/cancel')) ?>" class="inline-form"
                                      data-confirm="Cancel your <?= e($a['service']) ?> appointment on <?= e(fmt_date($a['appt_date'])) ?>?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= e($a['id']) ?>">
                                    <button type="submit" class="btn btn-small btn-ghost">Cancel</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="panel my-panel">
    <div class="panel-head">
        <h2><?= icon('clock') ?> Appointment History</h2>
        <?php if (count($past) > 1): ?>
            <label class="sel-pill"><span class="sr-only">Filter by status</span>
                <select data-status-filter="#history-table" aria-label="Filter history by status">
                    <option value="">All Status</option>
                    <?php foreach ($statuses as $st): ?><option value="<?= e($st) ?>"><?= e(ucfirst(str_replace('_', '-', $st))) ?></option><?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
    </div>
    <?php if (!$past): ?>
        <p class="empty">No past appointments yet. Your finished visits will show here.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table my-table" id="history-table">
                <thead>
                    <tr><th scope="col">Booking #</th><th scope="col">Date</th><th scope="col">Time</th><th scope="col">Service</th><th scope="col">Stylist</th><th scope="col">Status</th><th scope="col">Review</th></tr>
                </thead>
                <tbody>
                <?php foreach ($past as $a): ?>
                    <tr id="appt-<?= e($a['id']) ?>" data-status="<?= e($a['status']) ?>" class="<?= $focus === (int) $a['id'] ? 'row-focus' : '' ?>">
                        <td><span class="ref"><?= e(appt_ref($a['id'])) ?></span></td>
                        <td><span class="cell-ic"><?= icon('calendar') ?><?= e(fmt_date($a['appt_date'])) ?></span></td>
                        <td><span class="cell-ic"><?= icon('clock') ?><?= e(fmt_time($a['start_time'])) ?></span></td>
                        <td><span class="cell-ic"><?= icon('scissors') ?><?= e($a['service']) ?></span></td>
                        <td><span class="cell-ic"><?= icon('user') ?><?= e($a['stylist']) ?></span></td>
                        <td><span class="badge badge-<?= e($a['status']) ?>"><?= e(ucfirst(str_replace('_', '-', $a['status']))) ?></span></td>
                        <td>
                            <?php if ($a['review_rating']): ?>
                                <span class="my-review">
                                    <span class="stars" aria-label="<?= e($a['review_rating']) ?> out of 5 stars"><?= e(stars((int) $a['review_rating'])) ?></span>
                                    <a href="<?= e(url('customer/review', ['id' => $a['id']])) ?>" class="review-edit"><?= icon('edit', 'icon icon-xs') ?> View / edit</a>
                                </span>
                                <?php if ($a['review_status'] === 'pending'): ?><small class="muted review-flag">Waiting for approval</small><?php elseif ($a['review_status'] === 'removed'): ?><small class="muted review-flag">Not published</small><?php endif; ?>
                            <?php elseif ($a['status'] === 'completed'): ?>
                                <a href="<?= e(url('customer/review', ['id' => $a['id']])) ?>" class="btn btn-small btn-ghost my-review-btn"><?= icon('message') ?> Leave a review</a>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="filter-none" hidden><td colspan="7" class="empty">No appointments with this status.</td></tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php if ($focus): ?>
<script>
    (function () {
        var row = document.getElementById('appt-<?= $focus ?>');
        if (row) { row.scrollIntoView({ block: 'center' }); }
    })();
</script>
<?php endif; ?>
