<?php
/**
 * @var string $from  @var string $to  @var string $preset  @var array $presets
 * @var array $counts  @var int $total  @var ?int $completionRate  @var array $series
 * @var array $services  @var float $revenue  @var array $stylists  @var array $sources  @var array $rating
 */
$query = ['preset' => $preset, 'from' => $from, 'to' => $to];
$sourceLabels = ['online' => 'Online', 'walk_in' => 'Walk-in', 'phone' => 'Phone'];
$sourceTotal  = max(1, array_sum($sources));
$serviceMax   = max(1, ...array_map(static fn ($s) => (int) $s['total'], $services ?: [['total' => 1]]));
$points       = $series['points'];
// Show every label on short ranges, every 5th day on a full month.
$labelEvery   = $series['byMonth'] || count($points) <= 14 ? 1 : 5;
?>
<div class="page-head head-row">
    <div>
        <h1 class="card-title">Reports</h1>
        <p class="muted"><?= e(fmt_date($from)) ?> to <?= e(fmt_date($to)) ?></p>
    </div>
    <div class="actions no-print">
        <a href="<?= e(url('admin/reports/export', $query)) ?>" class="btn btn-ghost btn-small">Download CSV</a>
        <button type="button" class="btn btn-ghost btn-small" onclick="window.print()">Print</button>
    </div>
</div>

<section class="card no-print">
    <form method="get" action="index.php" class="form form-grid filters" id="report-filter">
        <input type="hidden" name="page" value="admin/reports">
        <div class="field">
            <label for="preset">Period</label>
            <select id="preset" name="preset">
                <?php foreach ($presets as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $preset === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field custom-range">
            <label for="from">From</label>
            <input type="date" id="from" name="from" value="<?= e($from) ?>">
        </div>
        <div class="field custom-range">
            <label for="to">To</label>
            <input type="date" id="to" name="to" value="<?= e($to) ?>">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-small">Show report</button>
        </div>
    </form>
</section>

<div class="stat-grid report-stats">
    <div class="stat"><span class="stat-label">Appointments</span><span class="stat-value"><?= e($total) ?></span><span class="stat-note">All statuses</span></div>
    <div class="stat"><span class="stat-label">Completed</span><span class="stat-value"><?= e($counts['completed']) ?></span><span class="stat-note"><?= $completionRate !== null ? e($completionRate) . '% of finished visits' : 'No finished visits yet' ?></span></div>
    <div class="stat"><span class="stat-label">Cancelled</span><span class="stat-value"><?= e($counts['cancelled']) ?></span><span class="stat-note"><?= $total ? e(round($counts['cancelled'] / $total * 100)) . '% of bookings' : '—' ?></span></div>
    <div class="stat"><span class="stat-label">No-shows</span><span class="stat-value"><?= e($counts['no_show']) ?></span><span class="stat-note">Customer didn't arrive</span></div>
    <div class="stat"><span class="stat-label">Average rating</span><span class="stat-value"><?= $rating['avg'] !== null ? e(number_format($rating['avg'], 1)) : '—' ?></span><span class="stat-note"><?= e($rating['count']) ?> review<?= $rating['count'] === 1 ? '' : 's' ?></span></div>
    <div class="stat"><span class="stat-label">Est. revenue</span><span class="stat-value stat-money"><?= e(peso($revenue)) ?></span><span class="stat-note">Completed × current prices</span></div>
</div>

<div class="stack">
    <section class="card">
        <h2 class="section-title">Appointments per <?= $series['byMonth'] ? 'month' : 'day' ?></h2>
        <p class="muted chart-sub">Cancelled bookings are not counted. Busiest: <?= e(max(array_column($points, 'value'))) ?> in one <?= $series['byMonth'] ? 'month' : 'day' ?>.</p>

        <?php if (array_sum(array_column($points, 'value')) === 0): ?>
            <p class="muted">No appointments in this period.</p>
        <?php else: ?>
            <div class="bar-chart" role="img" aria-label="Bar chart of appointments per <?= $series['byMonth'] ? 'month' : 'day' ?>. Full numbers in the table below.">
                <div class="bar-chart-plot" style="--cols: <?= count($points) ?>">
                    <span class="gridline" style="bottom: 100%" aria-hidden="true"><span><?= e($series['max']) ?></span></span>
                    <span class="gridline" style="bottom: 50%" aria-hidden="true"><span><?= e(round($series['max'] / 2, 1)) ?></span></span>
                    <?php foreach ($points as $i => $p): $h = $p['value'] / $series['max'] * 100; ?>
                        <div class="bar-col" tabindex="0" data-tip="<?= e($p['label']) ?>: <?= e($p['value']) ?>">
                            <div class="bar" style="height: <?= e(round($h, 2)) ?>%"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="bar-chart-axis" style="--cols: <?= count($points) ?>" aria-hidden="true">
                    <?php foreach ($points as $i => $p): ?>
                        <span><?= $i % $labelEvery === 0 ? e($p['short']) : '' ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <details class="chart-table">
                <summary>Show as table</summary>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th scope="col"><?= $series['byMonth'] ? 'Month' : 'Date' ?></th><th scope="col">Appointments</th></tr></thead>
                        <tbody>
                        <?php foreach ($points as $p): ?>
                            <tr><td><?= e($p['label']) ?></td><td><?= e($p['value']) ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </details>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2 class="section-title">Popular services</h2>
        <?php if (!$services): ?>
            <p class="muted">No appointments in this period.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Service</th>
                            <th scope="col" class="col-bar">Bookings</th>
                            <th scope="col">Completed</th>
                            <th scope="col">Est. revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($services as $s): ?>
                        <tr>
                            <td><?= e($s['name']) ?></td>
                            <td class="col-bar">
                                <div class="inline-bar">
                                    <span class="inline-bar-fill" style="width: <?= e(round($s['total'] / $serviceMax * 100, 2)) ?>%"></span>
                                    <span class="inline-bar-value"><?= e($s['total']) ?></span>
                                </div>
                            </td>
                            <td><?= e($s['completed']) ?></td>
                            <td><?= e(peso($s['revenue'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <div class="report-pair">
        <section class="card">
            <h2 class="section-title">Stylists</h2>
            <?php if (!$stylists): ?>
                <p class="muted">No appointments in this period.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Stylist</th>
                                <th scope="col">Bookings</th>
                                <th scope="col">Completed</th>
                                <th scope="col">No-shows</th>
                                <th scope="col">Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($stylists as $st): ?>
                            <tr>
                                <td><?= e($st['full_name']) ?></td>
                                <td><?= e($st['total']) ?></td>
                                <td><?= e($st['completed']) ?></td>
                                <td><?= e($st['no_show']) ?></td>
                                <td><?= $st['reviews'] ? e(number_format((float) $st['avg_rating'], 1)) . ' <span class="muted">(' . e($st['reviews']) . ')</span>' : '<span class="muted">—</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2 class="section-title">How customers booked</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th scope="col">Source</th><th scope="col" class="col-bar">Bookings</th><th scope="col">Share</th></tr></thead>
                    <tbody>
                    <?php foreach ($sources as $key => $n): ?>
                        <tr>
                            <td><?= e($sourceLabels[$key]) ?></td>
                            <td class="col-bar">
                                <div class="inline-bar">
                                    <span class="inline-bar-fill" style="width: <?= e(round($n / $sourceTotal * 100, 2)) ?>%"></span>
                                    <span class="inline-bar-value"><?= e($n) ?></span>
                                </div>
                            </td>
                            <td><?= e(round($n / $sourceTotal * 100)) ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
