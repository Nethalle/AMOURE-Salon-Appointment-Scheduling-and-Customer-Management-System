<?php
/**
 * @var string $from  @var string $to  @var string $preset  @var array $presets
 * @var array $counts  @var int $total  @var ?int $completionRate  @var array $series  @var array $revenueSeries
 * @var array $services  @var float $revenue  @var array $stylists  @var array $sources  @var array $rating  @var int $newCustomers
 */
$query = ['preset' => $preset, 'from' => $from, 'to' => $to];
$sourceLabels = ['online' => 'Online', 'walk_in' => 'Walk-in', 'phone' => 'Phone'];
$sourceTotal  = max(1, array_sum($sources));
$serviceMax   = max(1, ...array_map(static fn ($s) => (int) $s['total'], $services ?: [['total' => 1]]));

// ---- Revenue line chart ----
$pts   = $revenueSeries['points'];
$n     = count($pts);
$W = 680; $H = 300; $padL = 58; $padR = 12; $padT = 16; $padB = 28;
$plotW = $W - $padL - $padR;
$plotH = $H - $padT - $padB;
$rawMax = max(array_column($pts, 'value') ?: [0]);
$step  = 500;
foreach ([500, 1000, 2000, 2500, 5000, 10000, 20000, 25000, 50000, 100000] as $s) {
    if ($rawMax / 4 <= $s) { $step = $s; break; }
}
$yMax  = $step * 4;
$xAt   = fn (int $i) => $padL + ($n > 1 ? $plotW * $i / ($n - 1) : $plotW / 2);
$yAt   = fn (float $v) => $padT + $plotH - $plotH * $v / $yMax;
$line  = '';
foreach ($pts as $i => $p) {
    $line .= ($i ? ' L' : 'M') . round($xAt($i), 1) . ',' . round($yAt($p['value']), 1);
}
$area  = $n ? $line . ' L' . round($xAt($n - 1), 1) . ',' . ($padT + $plotH) . ' L' . round($xAt(0), 1) . ',' . ($padT + $plotH) . ' Z' : '';
$labelEvery = $revenueSeries['byMonth'] || $n <= 10 ? 1 : (int) ceil($n / 8);
$hasRevenue = $rawMax > 0;

// ---- Donut: top 4 services + Others ----
$palette = ['#B4505A', '#0088A8', '#A87410', '#7B4FB0']; // validated categorical order
$slices  = [];
foreach (array_slice($services, 0, 4) as $i => $s) {
    $slices[] = ['name' => $s['name'], 'n' => (int) $s['total'], 'color' => $palette[$i]];
}
$rest = array_sum(array_map(static fn ($s) => (int) $s['total'], array_slice($services, 4)));
if ($rest > 0) {
    $slices[] = ['name' => 'Others', 'n' => $rest, 'color' => '#BDB0AD'];
}
$sliceTotal = array_sum(array_column($slices, 'n'));
$r = 70; $circ = 2 * M_PI * $r; $gap = $sliceTotal && count($slices) > 1 ? 2 : 0;
?>
<div class="ph">
    <div>
        <h1>Reports</h1>
        <p>Salon performance · <?= e(fmt_date($from)) ?> to <?= e(fmt_date($to)) ?></p>
    </div>
    <form method="get" action="index.php" class="ph-actions no-print" id="report-filter">
        <input type="hidden" name="page" value="admin/reports">
        <select id="preset" name="preset" aria-label="Period" onchange="if (this.value !== 'custom') this.form.submit()">
            <?php foreach ($presets as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $preset === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="from" value="<?= e($from) ?>" class="custom-range" aria-label="From">
        <input type="date" name="to" value="<?= e($to) ?>" class="custom-range" aria-label="To">
        <button type="submit" class="btn btn-ghost custom-range">Apply</button>
        <a href="<?= e(url('admin/reports/export', $query)) ?>" class="icon-btn" title="Download CSV" aria-label="Download CSV"><?= icon('download') ?></a>
        <button type="button" class="icon-btn" title="Print" aria-label="Print" onclick="window.print()"><?= icon('printer') ?></button>
    </form>
</div>

<div class="kpis">
    <div class="kpi">
        <span class="kpi-icon"><?= icon('card') ?></span>
        <div><span class="kpi-label">Total Revenue</span><span class="kpi-value"><?= e(peso($revenue)) ?></span><span class="kpi-note">From completed visits</span></div>
    </div>
    <div class="kpi">
        <span class="kpi-icon"><?= icon('calendar') ?></span>
        <div><span class="kpi-label">Total Appointments</span><span class="kpi-value"><?= $total ?></span>
            <span class="kpi-note"><?= $counts['completed'] ?> completed<?= $completionRate !== null ? ' · ' . $completionRate . '% show rate' : '' ?></span></div>
    </div>
    <div class="kpi">
        <span class="kpi-icon"><?= icon('users') ?></span>
        <div><span class="kpi-label">New Customers</span><span class="kpi-value"><?= $newCustomers ?></span><span class="kpi-note">Profiles added</span></div>
    </div>
    <div class="kpi">
        <span class="kpi-icon"><?= icon('star') ?></span>
        <div><span class="kpi-label">Average Rating</span><span class="kpi-value"><?= $rating['avg'] !== null ? e($rating['avg']) : '—' ?> <small>/ 5</small></span>
            <span class="kpi-note"><?= $rating['count'] ?> review<?= $rating['count'] === 1 ? '' : 's' ?></span></div>
    </div>
</div>

<div class="dash-grid" style="grid-template-columns:minmax(0,1.7fr) minmax(300px,1fr)">
    <section class="panel chart">
        <div class="panel-head"><h2>Revenue Overview</h2><span class="hint">per <?= $revenueSeries['byMonth'] ? 'month' : 'day' ?></span></div>
        <?php if (!$hasRevenue): ?>
            <p class="empty">No completed appointments in this period.</p>
        <?php else: ?>
            <svg viewBox="0 0 <?= $W ?> <?= $H ?>" role="img" aria-label="Line chart of revenue per <?= $revenueSeries['byMonth'] ? 'month' : 'day' ?>">
                <defs>
                    <linearGradient id="revFill" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="0" stop-color="#A85C5E" stop-opacity=".22"/>
                        <stop offset="1" stop-color="#A85C5E" stop-opacity="0"/>
                    </linearGradient>
                </defs>
                <?php for ($g = 0; $g <= 4; $g++): $y = $yAt($step * $g); ?>
                    <line class="grid" x1="<?= $padL ?>" x2="<?= $W - $padR ?>" y1="<?= $y ?>" y2="<?= $y ?>"/>
                    <text class="axis" x="<?= $padL - 10 ?>" y="<?= $y + 4 ?>" text-anchor="end">₱<?= $step * $g >= 1000 ? rtrim(rtrim(number_format($step * $g / 1000, 1), '0'), '.') . 'k' : $step * $g ?></text>
                <?php endfor; ?>
                <path class="area" d="<?= $area ?>"/>
                <path class="line" d="<?= $line ?>"/>
                <?php foreach ($pts as $i => $p): $x = $xAt($i); $y = $yAt($p['value']); ?>
                    <g class="pt">
                        <title><?= e($p['label']) ?>: <?= e(peso($p['value'])) ?></title>
                        <rect class="hit" x="<?= $x - max(6, $plotW / max(1, $n) / 2) ?>" y="<?= $padT ?>" width="<?= max(12, $plotW / max(1, $n)) ?>" height="<?= $plotH ?>" fill="transparent"/>
                        <?php if ($n <= 31): ?><circle class="dot" cx="<?= $x ?>" cy="<?= $y ?>" r="<?= $n <= 14 ? 4 : 3 ?>"/><?php endif; ?>
                        <text class="val" x="<?= $x ?>" y="<?= $y - 10 ?>" text-anchor="middle"><?= e(peso($p['value'])) ?></text>
                        <?php if ($i % $labelEvery === 0): ?>
                            <text class="axis" x="<?= $x ?>" y="<?= $H - 8 ?>" text-anchor="middle"><?= e($revenueSeries['byMonth'] ? $p['short'] : date('M j', strtotime($from . " +{$i} days"))) ?></text>
                        <?php endif; ?>
                    </g>
                <?php endforeach; ?>
            </svg>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-head"><h2>Appointments by Service</h2></div>
        <?php if (!$sliceTotal): ?>
            <p class="empty">No appointments in this period.</p>
        <?php else: ?>
            <div class="donut-wrap">
                <svg class="donut" viewBox="0 0 190 190" role="img" aria-label="Donut chart of appointments by service">
                    <g transform="rotate(-90 95 95)">
                        <?php $offset = 0; foreach ($slices as $sl):
                            $len = $circ * $sl['n'] / $sliceTotal; ?>
                            <circle class="seg" cx="95" cy="95" r="<?= $r ?>" stroke="<?= e($sl['color']) ?>"
                                    stroke-dasharray="<?= round(max(0.1, $len - $gap), 2) ?> <?= round($circ, 2) ?>" stroke-dashoffset="<?= round(-$offset, 2) ?>">
                                <title><?= e($sl['name']) ?>: <?= $sl['n'] ?></title>
                            </circle>
                        <?php $offset += $len; endforeach; ?>
                    </g>
                    <text class="total" x="95" y="96" text-anchor="middle"><?= $sliceTotal ?></text>
                    <text class="total-sub" x="95" y="114" text-anchor="middle">bookings</text>
                </svg>
                <ul class="legend">
                    <?php foreach ($slices as $sl): ?>
                        <li><i style="background:<?= e($sl['color']) ?>"></i><?= e($sl['name']) ?><b><?= $sl['n'] ?></b><em><?= round($sl['n'] / $sliceTotal * 100) ?>%</em></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </section>
</div>

<div class="dash-grid" style="grid-template-columns:minmax(0,1.7fr) minmax(300px,1fr)">
    <section class="panel panel-flush">
        <div class="panel-head"><h2>Stylist Performance</h2></div>
        <?php if (!$stylists): ?>
            <p class="empty">No appointments in this period.</p>
        <?php else: ?>
            <div class="table-wrap" style="margin-top:16px">
                <table class="table">
                    <thead><tr><th scope="col">Stylist</th><th scope="col">Bookings</th><th scope="col">Completed</th><th scope="col">No-shows</th><th scope="col">Rating</th></tr></thead>
                    <tbody>
                    <?php foreach ($stylists as $st): ?>
                        <tr>
                            <td><div class="cell-person"><span class="avatar-i"><?= e(initials($st['full_name'])) ?></span><strong><?= e($st['full_name']) ?></strong></div></td>
                            <td><?= (int) $st['total'] ?></td>
                            <td><?= (int) $st['completed'] ?></td>
                            <td><?= (int) $st['no_show'] ?></td>
                            <td><?= $st['reviews'] ? '★ ' . e(number_format((float) $st['avg_rating'], 1)) . ' <span class="muted">(' . (int) $st['reviews'] . ')</span>' : '<span class="muted">—</span>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-head"><h2>How Customers Booked</h2></div>
        <ul class="legend">
            <?php foreach ($sources as $key => $cnt): $pct = round($cnt / $sourceTotal * 100); ?>
                <li style="flex-wrap:wrap">
                    <?= e($sourceLabels[$key]) ?><b><?= $cnt ?></b><em><?= $pct ?>%</em>
                    <span style="flex-basis:100%;height:6px;border-radius:3px;background:#F4ECEA;overflow:hidden">
                        <span style="display:block;height:100%;width:<?= $pct ?>%;background:var(--app-rose);border-radius:3px"></span>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="revenue-split">
            <div><span>Cancelled</span><strong><?= $counts['cancelled'] ?></strong></div>
            <div><span>No-show</span><strong><?= $counts['no_show'] ?></strong></div>
        </div>
    </section>
</div>

<section class="panel panel-flush">
    <div class="panel-head"><h2>Popular Services</h2></div>
    <?php if (!$services): ?>
        <p class="empty">No appointments in this period.</p>
    <?php else: ?>
        <div class="table-wrap" style="margin-top:16px">
            <table class="table">
                <thead><tr><th scope="col">Service</th><th scope="col">Bookings</th><th scope="col">Completed</th><th scope="col" class="t-right">Est. revenue</th></tr></thead>
                <tbody>
                <?php foreach ($services as $s): ?>
                    <tr>
                        <td><strong><?= e($s['name']) ?></strong></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <span style="flex:0 0 120px;height:6px;border-radius:3px;background:#F4ECEA;overflow:hidden"><span style="display:block;height:100%;width:<?= round($s['total'] / $serviceMax * 100) ?>%;background:var(--app-rose)"></span></span>
                                <?= (int) $s['total'] ?>
                            </div>
                        </td>
                        <td><?= (int) $s['completed'] ?></td>
                        <td class="t-right"><?= e(peso($s['revenue'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
