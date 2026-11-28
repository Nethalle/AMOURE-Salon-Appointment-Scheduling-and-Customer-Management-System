<?php
/** @var array $user  @var array $counts  @var int $pendingRequests  @var array $schedule  @var int $dailyLimit
 *  @var float $revToday  @var float $revYesterday  @var float $revExpected  @var array $chart  @var array $overview */
$hour   = (int) date('G');
$greet  = $hour < 12 ? 'morning' : ($hour < 18 ? 'afternoon' : 'evening');
$booked = $counts['pending'] + $counts['confirmed'] + $counts['completed'];
$statusLabels = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'no_show' => 'No-show'];

// Revenue change vs yesterday.
if ($revYesterday > 0) {
    $change = ($revToday - $revYesterday) / $revYesterday * 100;
    $changeText = ($change >= 0 ? '+' : '') . number_format($change, 0) . '%';
} else {
    $change = 0;
    $changeText = $revToday > 0 ? 'New' : '0%';
}

/** "+12%" style change vs last month, or null when there is nothing to compare with. */
$pct = static function (float $now, float $before): ?array {
    if ($before <= 0) { return null; }
    $c = ($now - $before) / $before * 100;
    $text = abs($c) >= 1000 ? ($c > 0 ? '+999%+' : '-99%') : (($c >= 0 ? '+' : '') . number_format($c, 0) . '%');
    return [$text, $c >= 0];
};

// Bar chart geometry.
$W = 900; $H = 250; $padL = 30; $padB = 28; $padT = 16;
$max   = max(4, (int) ceil(max($chart) / 4) * 4);
$n     = count($chart);
$slot  = ($W - $padL) / $n;
$barW  = min(22, $slot * .5);
$plotH = $H - $padB - $padT;
?>
<div class="ph dash-hero">
    <div>
        <h1>Good <?= $greet ?>, <?= e(explode(' ', $user['name'])[0]) ?>!
            <svg class="sparkle" viewBox="0 0 32 32" aria-hidden="true"><path d="M14 3c1 7 3.5 9.5 10.5 10.5C17.5 14.5 15 17 14 24c-1-7-3.5-9.5-10.5-10.5C10.5 12.5 13 10 14 3Z"/><path d="M25 19c.4 2.6 1.4 3.6 4 4-2.6.4-3.6 1.4-4 4-.4-2.6-1.4-3.6-4-4 2.6-.4 3.6-1.4 4-4Z"/></svg>
        </h1>
        <p>Here's what's happening at the salon today</p>
    </div>
    <svg class="hero-art" viewBox="0 0 360 200" aria-hidden="true">
        <circle cx="210" cy="96" r="92" class="ha-glow"/>
        <path d="M166 186V82a50 50 0 0 1 100 0v104" class="ha-frame"/>
        <path d="M178 186V84a38 38 0 0 1 76 0v102Z" class="ha-glass"/>
        <path d="M196 70c10-16 26-20 40-14M190 104c14-22 36-30 52-24" class="ha-shine"/>
        <path d="M120 186h176" class="ha-line"/>
        <path d="M128 186c-4-22-2-40 8-56M136 130c-14-6-24-2-30 8 12 4 22 2 30-8Zm2 18c12-10 24-10 32-2-10 8-22 8-32 2Zm-6 18c-12-6-22-2-28 6 10 4 20 2 28-6Zm-2-52c-6-12-2-24 8-30 4 12 0 24-8 30Z" class="ha-leaf"/>
        <rect x="300" y="150" width="26" height="36" rx="5" class="ha-bottle"/><rect x="306" y="140" width="14" height="10" rx="2" class="ha-bottle"/>
    </svg>
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
        ['calendar', "Today's Bookings", $booked . ' <small>/ ' . $dailyLimit . '</small>', 'Daily limit ' . $dailyLimit, 'rose'],
        ['clock',    'Pending',          (string) $counts['pending'],   'Need confirming',     'amber'],
        ['check',    'Confirmed',        (string) $counts['confirmed'], 'Still to come today', 'green'],
        ['award',    'Completed',        (string) $counts['completed'], 'Done today',          'blue'],
    ];
    foreach ($kpis as [$ic, $label, $value, $note, $tone]): ?>
        <div class="kpi kpi-<?= $tone ?>">
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
    <section class="panel rev-card">
        <div class="panel-head"><h2>Today's Revenue</h2></div>
        <div class="rev-top">
            <p class="revenue-value"><?= e(peso($revToday)) ?></p>
            <div class="rev-chip-wrap">
                <span class="rev-chip"><?= icon($change < 0 ? 'trend-down' : 'trend') ?> <?= e($changeText) ?></span>
                <small>vs yesterday</small>
            </div>
        </div>
        <svg class="rev-wave" viewBox="0 0 400 70" preserveAspectRatio="none" aria-hidden="true">
            <path d="M0 62C60 60 90 40 150 44s100 22 150-4 70-34 100-30V70H0Z"/>
        </svg>
        <div class="revenue-split">
            <div><span>Yesterday</span><strong><?= e(peso($revYesterday)) ?></strong></div>
            <div><span>Expected (confirmed)</span><strong><?= e(peso($revExpected)) ?></strong></div>
        </div>
    </section>

    <section class="panel chart">
        <div class="panel-head">
            <h2>Bookings · Last 14 Days</h2>
            <a href="<?= e(url('staff/appointments')) ?>" class="more">View appointments <?= icon('chev-right', 'icon icon-xs') ?></a>
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

<div class="dash-bottom">
<section class="panel panel-flush">
    <div class="panel-head">
        <h2>Today's Schedule</h2>
        <a href="<?= e(url('staff/appointments', ['tab' => 'today'])) ?>" class="more">View All</a>
    </div>
    <?php if (!$schedule): ?>
        <div class="empty-art">
            <svg viewBox="0 0 220 120" aria-hidden="true">
                <path d="M38 96c-6-18-2-34 10-44M48 60c-12-8-22-6-28 2 10 6 20 6 28-2Zm-4 18c-12-4-20 0-24 8 10 4 18 2 24-8Zm8-30c-2-12 4-20 12-24 0 12-4 20-12 24Z" class="ea-leaf"/>
                <path d="M182 96c6-18 2-34-10-44M172 60c12-8 22-6 28 2-10 6-20 6-28-2Zm4 18c12-4 20 0 24 8-10 4-18 2-24-8Zm-8-30c2-12-4-20-12-24 0 12 4 20 12 24Z" class="ea-leaf"/>
                <rect x="66" y="22" width="88" height="80" rx="12" class="ea-page"/>
                <path d="M66 46V34a12 12 0 0 1 12-12h64a12 12 0 0 1 12 12v12Z" class="ea-top"/>
                <rect x="84" y="14" width="6" height="16" rx="3" class="ea-ring"/><rect x="130" y="14" width="6" height="16" rx="3" class="ea-ring"/>
                <path d="M94 74l10 10 22-22" class="ea-check"/>
            </svg>
            <strong>No appointments today.</strong>
            <p>The schedule is clear for now.</p>
            <a href="<?= e(url('staff/appointments/new')) ?>" class="btn btn-primary btn-small"><?= icon('plus') ?> Add Appointment</a>
        </div>
    <?php else: ?>
        <div class="table-wrap" style="margin-top:16px">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Booking #</th>
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
                        <td><span class="ref"><?= e(appt_ref($a['id'])) ?></span></td>
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

<section class="panel overview">
    <div class="panel-head"><h2>Quick Overview</h2><span class="ov-period"><?= e(date('F')) ?> so far</span></div>
    <?php
    $rows = [
        ['users', 'Total Appointments', (string) $overview['appointments'], $pct($overview['appointments'], $overview['appointmentsBefore']), 'rose'],
        ['user',  'New Customers',      (string) $overview['customers'],    $pct($overview['customers'], $overview['customersBefore']),       'rose'],
        ['star',  'Average Rating',     $overview['rating'] !== null ? number_format($overview['rating'], 1) . ' <small>/ 5</small>' : '—', null, 'amber'],
        ['card',  'Revenue This Month', e(peso($overview['revenue'])),      $pct($overview['revenue'], $overview['revenueBefore']),           'amber'],
    ];
    foreach ($rows as [$ic, $label, $value, $chg, $tone]): ?>
        <div class="ov-row">
            <span class="ov-icon ov-<?= $tone ?>"><?= icon($ic) ?></span>
            <span class="ov-label"><?= e($label) ?></span>
            <strong class="ov-value"><?= $value /* numbers or escaped above */ ?></strong>
            <?php if ($chg): ?>
                <span class="ov-chg <?= $chg[1] ? 'is-up' : 'is-down' ?>" title="Compared with the same days last month"><?= icon($chg[1] ? 'trend' : 'trend-down') ?><?= e($chg[0]) ?></span>
            <?php else: ?>
                <span class="ov-chg is-none"></span>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</section>
</div>
