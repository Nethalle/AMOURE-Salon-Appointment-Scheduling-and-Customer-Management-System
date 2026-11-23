<?php /** @var array $user  @var array $counts  @var int $pendingRequests  @var array $schedule  @var int $dailyLimit */ ?>
<div class="page-head head-row">
    <div>
        <h1 class="card-title">Good <?= (int) date('G') < 12 ? 'morning' : ((int) date('G') < 18 ? 'afternoon' : 'evening') ?>, <?= e($user['name']) ?></h1>
        <p class="muted"><?= e(fmt_date(date('Y-m-d'))) ?></p>
    </div>
    <a href="<?= e(url('staff/appointments/new')) ?>" class="btn btn-primary">New appointment</a>
</div>

<?php if ($pendingRequests > 0): ?>
    <div class="notice">
        <strong><?= $pendingRequests ?></strong> online booking <?= $pendingRequests === 1 ? 'request is' : 'requests are' ?> waiting for confirmation.
        <a href="<?= e(url('staff/appointments', ['status' => 'pending'])) ?>">Review requests →</a>
    </div>
<?php endif; ?>

<div class="stat-grid">
    <?php
    $booked = $counts['pending'] + $counts['confirmed'] + $counts['completed'];
    $stats = [
        ['Booked today',  $booked . ' / ' . $dailyLimit, 'Daily limit'],
        ['Pending',       $counts['pending'],   'Need confirming'],
        ['Confirmed',     $counts['confirmed'], 'Still to come'],
        ['Completed',     $counts['completed'], 'Done today'],
        ['Cancelled',     $counts['cancelled'], 'Today'],
        ['No-show',       $counts['no_show'],   'Today'],
    ];
    foreach ($stats as [$label, $value, $note]): ?>
        <div class="stat">
            <span class="stat-label"><?= e($label) ?></span>
            <span class="stat-value"><?= e($value) ?></span>
            <span class="stat-note"><?= e($note) ?></span>
        </div>
    <?php endforeach; ?>
</div>

<section class="card">
    <div class="head-row">
        <h2 class="section-title">Today's schedule</h2>
        <a href="<?= e(url('staff/appointments', ['from' => date('Y-m-d'), 'to' => date('Y-m-d')])) ?>">Manage today's appointments →</a>
    </div>
    <?php if (!$schedule): ?>
        <p class="muted">No appointments today.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Time</th>
                        <th scope="col">Customer</th>
                        <th scope="col">Service</th>
                        <th scope="col">Stylist</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($schedule as $a): ?>
                    <tr>
                        <td><?= e(fmt_time($a['start_time'])) ?> – <?= e(fmt_time($a['end_time'])) ?></td>
                        <td><?= e($a['customer']) ?></td>
                        <td><?= e($a['service']) ?></td>
                        <td><?= e($a['stylist']) ?></td>
                        <td><span class="badge badge-<?= e($a['status']) ?>"><?= e(ucfirst(str_replace('_', '-', $a['status']))) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
