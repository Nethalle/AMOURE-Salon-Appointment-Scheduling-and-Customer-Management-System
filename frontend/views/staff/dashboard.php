<?php
/** @var array $user  @var array $counts  @var int $pendingRequests  @var array $schedule  @var int $dailyLimit
 *  @var float $revToday  @var float $revYesterday  @var float $revExpected  @var array $chart */
$hour   = (int) date('G');
$greet  = $hour < 12 ? 'morning' : ($hour < 18 ? 'afternoon' : 'evening');
$booked = $counts['pending'] + $counts['confirmed'] + $counts['completed'];
$statusLabels = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'no_show' => 'No-show'];

// Revenue change vs yesterday.
if ($revYesterday > 0) {
    $change = ($revToday - $revYesterday) / $revYesterday * 100;
    $changeText = ($change >= 0 ? '+' : '') . number_format($change, 0) . '% vs yesterday';
} else {
    $change = $revToday > 0 ? 1 : 0;
    $changeText = $revToday > 0 ? 'No sales yesterday' : 'No sales yet today';
}

// Bar chart geometry.
$W = 640; $H = 230; $padL = 30; $padB = 28; $padT = 16;
$max   = max(4, (int) ceil(max($chart) / 4) * 4);
$n     = count($chart);
$slot  = ($W - $padL) / $n;
$barW  = min(22, $slot * .5);
$plotH = $H - $padB - $padT;
?>
<div class="ph">
    <div>
        <h1>Good <?= $greet ?>, <?= e(explode(' ', $user['name'])[0]) ?>!</h1>
        <p>Here's what's happening at the salon today · <?= e(fmt_date(date('Y-m-d'))) ?></p>
    </div>
    <a href="<?= e(url('staff/appointments/new')) ?>" class="btn btn-primary"><?= icon('plus') ?> New Appointment</a>
</div>

<?php if ($pendingRequests > 0): ?>
    <div class="notice">
        <strong><?= $pendingRequests ?></strong> online booking <?= $pendingRequests === 1 ? 'request is' : 'requests are' ?> waiting for confirmation.
        <a href="<?= e(url('staff/appointments', ['tab' => 'pending'])) ?>">Review requests →</a>
    </div>
<?php endif; ?>

<div class="kpis">
    <?php
    $kpis = [
        ['calendar', "Today's Bookings", $booked . ' <small>/ ' . $dailyLimit . '</small>', 'Daily limit ' . $dailyLimit],
        ['clock',    'Pending',          (string) $counts['pending'],   'Need confirming'],
        ['check',    'Confirmed',        (string) $counts['confirmed'], 'Still to come today'],
        ['award',    'Completed',        (string) $counts['completed'], 'Done today'],
    ];
    foreach ($kpis as [$ic, $label, $value, $note]): ?>
        <div class="kpi">
            <span class="kpi-icon"><?= icon($ic) ?></span>
            <div>
                <span class="kpi-label"><?= e($label) ?></span>
                <span class="kpi-value"><?= $value /* already escaped: numbers only */ ?></span>
                <span class="kpi-note"><?= e($note) ?></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="dash-grid">
    <section class="panel">
        <div class="panel-head"><h2>Today's Revenue</h2><span class="kpi-icon" style="width:40px;height:40px"><?= icon('card') ?></span></div>
        <p class="revenue-value"><?= e(peso($revToday)) ?></p>
        <p class="revenue-cmp <?= $change > 0 ? 'up' : ($change < 0 ? 'down' : '') ?>">
            <?php if ($change != 0): ?><?= icon($change > 0 ? 'trend' : 'trend-down') ?><?php endif; ?>
            <?= e($changeText) ?>
        </p>
        <div class="revenue-split">
            <div><span>Yesterday</span><strong><?= e(peso($revYesterday)) ?></strong></div>
            <div><span>Expected (confirmed)</span><strong><?= e(peso($revExpected)) ?></strong></div>
        </div>
    </section>

    <section class="panel chart">
        <div class="panel-head">
            <h2>Bookings · last 14 days</h2>
            <a href="<?= e(url('staff/appointments')) ?>" class="more">View appointments</a>
        </div>
        <svg viewBox="0 0 <?= $W ?> <?= $H ?>" role="img" aria-label="Bookings per day for the last 14 days">
            <?php for ($g = 0; $g <= 4; $g++):
                $v = $max / 4 * $g; $y = $padT + $plotH - $plotH * $g / 4; ?>
                <line class="grid" x1="<?= $padL ?>" x2="<?= $W ?>" y1="<?= $y ?>" y2="<?= $y ?>"/>
                <text class="axis" x="<?= $padL - 8 ?>" y="<?= $y + 4 ?>" text-anchor="end"><?= (int) $v ?></text>
            <?php endfor; ?>
            <?php $i = 0; foreach ($chart as $day => $count):
                $cx = $padL + $slot * $i + $slot / 2;
                $h  = $count ? max(4, $plotH * $count / $max) : 0;
                $y  = $padT + $plotH - $h;
                $isToday = $day === date('Y-m-d'); ?>
                <g class="col">
                    <title><?= e(date('D, M j', strtotime($day))) ?>: <?= $count ?> booking<?= $count === 1 ? '' : 's' ?></title>
                    <rect class="hit" x="<?= $cx - $slot / 2 ?>" y="<?= $padT ?>" width="<?= $slot ?>" height="<?= $plotH ?>"/>
                    <?php if ($h > 0): ?>
                        <path class="bar <?= $isToday ? 'is-today' : '' ?>" d="M<?= $cx - $barW / 2 ?>,<?= $padT + $plotH ?> V<?= $y + 4 ?> Q<?= $cx - $barW / 2 ?>,<?= $y ?> <?= $cx - $barW / 2 + 4 ?>,<?= $y ?> H<?= $cx + $barW / 2 - 4 ?> Q<?= $cx + $barW / 2 ?>,<?= $y ?> <?= $cx + $barW / 2 ?>,<?= $y + 4 ?> V<?= $padT + $plotH ?> Z"/>
                    <?php endif; ?>
                    <text class="val" x="<?= $cx ?>" y="<?= $y - 6 ?>" text-anchor="middle"><?= $count ?></text>
                    <?php if ($i % 2 === 1 || $isToday): ?>
                        <text class="axis" x="<?= $cx ?>" y="<?= $H - 8 ?>" text-anchor="middle"><?= $isToday ? 'Today' : e(date('M j', strtotime($day))) ?></text>
                    <?php endif; ?>
                </g>
            <?php $i++; endforeach; ?>
        </svg>
    </section>
</div>

<section class="panel panel-flush">
    <div class="panel-head">
        <h2>Today's Schedule</h2>
        <a href="<?= e(url('staff/appointments', ['tab' => 'today'])) ?>" class="more">View All</a>
    </div>
    <?php if (!$schedule): ?>
        <p class="empty">No appointments today.</p>
    <?php else: ?>
        <div class="table-wrap" style="margin-top:16px">
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
                        <td><span class="t-time"><?= e(fmt_time($a['start_time'])) ?></span><span class="cell-sub">to <?= e(fmt_time($a['end_time'])) ?></span></td>
                        <td><div class="cell-person"><span class="avatar-i"><?= e(initials($a['customer'])) ?></span><strong><?= e($a['customer']) ?></strong></div></td>
                        <td><?= e($a['service']) ?></td>
                        <td><?= e($a['stylist']) ?></td>
                        <td><span class="badge badge-<?= e($a['status']) ?>"><?= e($statusLabels[$a['status']] ?? $a['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
