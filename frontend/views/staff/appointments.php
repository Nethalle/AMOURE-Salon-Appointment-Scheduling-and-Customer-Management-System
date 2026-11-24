<?php
/** @var array $appointments  @var array $filters  @var array $tabs  @var array $counts  @var int $total  @var int $pages  @var int $perPage
 *  @var string $month  @var array $busyDays  @var array $services  @var array $stylists  @var string $back */
$actionLabels = [
    'confirm'  => ['Confirm',          'check', ''],
    'complete' => ['Mark completed',   'check', ''],
    'no_show'  => ['Mark no-show',     'x',     'Mark this appointment as a no-show?'],
    'cancel'   => ['Cancel booking',   'x',     'Cancel this appointment? The time slot will be freed.'],
];
$statusLabels = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'no_show' => 'No-show'];
$sourceLabels = ['online' => 'Online', 'walk_in' => 'Walk-in', 'phone' => 'Phone'];

// Query kept when switching tabs / pages / months.
$keep = array_filter([
    'date' => $filters['date'], 'service' => $filters['service'] ?: '', 'stylist' => $filters['stylist'] ?: '', 'q' => $filters['q'],
], fn ($v) => $v !== '');
$tabQuery = fn (string $tab) => ($tab === 'all' ? [] : ['tab' => $tab]) + ($tab === 'today' ? array_diff_key($keep, ['date' => 1]) : $keep);

// Calendar grid (weeks start on Sunday).
$firstTs  = strtotime($month . '-01');
$startTs  = strtotime('-' . date('w', $firstTs) . ' days', $firstTs);
$lastTs   = strtotime(date('Y-m-t', $firstTs));
$endTs    = strtotime('+' . (6 - (int) date('w', $lastTs)) . ' days', $lastTs);
$prevM    = date('Y-m', strtotime('-1 month', $firstTs));
$nextM    = date('Y-m', strtotime('+1 month', $firstTs));
$selected = $filters['tab'] === 'today' ? date('Y-m-d') : $filters['date'];
$calQuery = array_diff_key($keep, ['date' => 1]) + ($filters['tab'] !== 'all' && $filters['tab'] !== 'today' ? ['tab' => $filters['tab']] : []);
?>
<div class="ph">
    <div>
        <h1>Appointments</h1>
        <p>Manage and track all appointments</p>
    </div>
    <a href="<?= e(url('staff/appointments/new')) ?>" class="btn btn-primary"><?= icon('plus') ?> New Appointment</a>
</div>

<div class="appt-grid">
    <section class="panel">
        <nav class="tabbar" aria-label="Status">
            <?php foreach ($tabs as $key => $label): ?>
                <a href="<?= e(url('staff/appointments', $tabQuery($key))) ?>" <?= $filters['tab'] === $key ? 'aria-current="page"' : '' ?>>
                    <?= e($label) ?> <span class="count"><?= (int) $counts[$key] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <form method="get" class="toolbar" data-autosubmit>
            <input type="hidden" name="page" value="staff/appointments">
            <?php if ($filters['tab'] !== 'all'): ?><input type="hidden" name="tab" value="<?= e($filters['tab']) ?>"><?php endif; ?>
            <?php if ($filters['tab'] !== 'today'): ?>
                <input type="date" name="date" value="<?= e($filters['date']) ?>" aria-label="Date">
            <?php endif; ?>
            <select name="service" aria-label="Service">
                <option value="">All Services</option>
                <?php foreach ($services as $s): ?>
                    <option value="<?= e($s['id']) ?>" <?= $filters['service'] === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="stylist" aria-label="Stylist">
                <option value="">All Stylists</option>
                <?php foreach ($stylists as $st): ?>
                    <option value="<?= e($st['id']) ?>" <?= $filters['stylist'] === (int) $st['id'] ? 'selected' : '' ?>><?= e($st['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="search-box grow">
                <?= icon('search') ?>
                <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search customer…" aria-label="Search">
            </div>
            <?php if ($keep): ?>
                <a href="<?= e(url('staff/appointments', $filters['tab'] === 'all' ? [] : ['tab' => $filters['tab']])) ?>" class="btn btn-ghost btn-small">Clear</a>
            <?php endif; ?>
        </form>

        <?php if (!$appointments): ?>
            <p class="empty">No appointments found.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Date &amp; Time</th>
                            <th scope="col">Customer</th>
                            <th scope="col">Service</th>
                            <th scope="col">Stylist</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="t-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($appointments as $a): ?>
                        <tr>
                            <td>
                                <span class="t-time"><?= e(fmt_time($a['start_time'])) ?></span>
                                <span class="cell-sub"><?= e(date('D, M j', strtotime($a['appt_date']))) ?></span>
                            </td>
                            <td>
                                <div class="cell-person">
                                    <span class="avatar-i"><?= e(initials($a['customer'])) ?></span>
                                    <span>
                                        <strong><?= e($a['customer']) ?></strong>
                                        <small><?= e($a['phone'] ?: ($sourceLabels[$a['source']] ?? '')) ?></small>
                                    </span>
                                </div>
                            </td>
                            <td><?= e($a['service']) ?></td>
                            <td><?= e($a['stylist']) ?></td>
                            <td><span class="badge badge-<?= e($a['status']) ?>"><?= e($statusLabels[$a['status']] ?? $a['status']) ?></span></td>
                            <td class="t-right">
                                <details class="dots">
                                    <summary aria-label="Actions for <?= e($a['customer']) ?>"><?= icon('more') ?></summary>
                                    <div class="menu menu-right">
                                        <?php foreach ($a['actions'] as $action): [$label, $ic, $confirm] = $actionLabels[$action]; ?>
                                            <form method="post" action="<?= e(url('staff/appointments/status')) ?>"
                                                  <?= $confirm ? 'data-confirm="' . e($confirm) . '"' : '' ?>>
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= e($a['id']) ?>">
                                                <input type="hidden" name="action" value="<?= e($action) ?>">
                                                <input type="hidden" name="back" value="<?= e($back) ?>">
                                                <button type="submit" class="<?= in_array($action, ['cancel', 'no_show'], true) ? 'danger' : '' ?>"><?= icon($ic) ?> <?= e($label) ?></button>
                                            </form>
                                        <?php endforeach; ?>
                                        <?php if ($a['can_reschedule']): ?>
                                            <a href="<?= e(url('staff/appointments/reschedule', ['id' => $a['id']])) ?>"><?= icon('calendar') ?> Reschedule</a>
                                        <?php endif; ?>
                                        <?php if ($a['actions'] || $a['can_reschedule']): ?><hr><?php endif; ?>
                                        <a href="<?= e(url('staff/customers/view', ['id' => $a['customer_id']])) ?>"><?= icon('eye') ?> View customer</a>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php
        $pgRoute = 'staff/appointments';
        $pgQuery = $tabQuery($filters['tab']);
        $pgPage = $filters['p']; $pgPages = $pages; $pgTotal = $total; $pgPer = $perPage; $pgNoun = 'appointments';
        require __DIR__ . '/../layouts/_pager.php';
        ?>
    </section>

    <aside class="panel mini-cal">
        <div class="mini-cal-head">
            <a href="<?= e(url('staff/appointments', $calQuery + ['m' => $prevM] + ($filters['date'] ? ['date' => $filters['date']] : []))) ?>" aria-label="Previous month"><?= icon('chev-left') ?></a>
            <strong><?= e(date('F Y', $firstTs)) ?></strong>
            <a href="<?= e(url('staff/appointments', $calQuery + ['m' => $nextM] + ($filters['date'] ? ['date' => $filters['date']] : []))) ?>" aria-label="Next month"><?= icon('chev-right') ?></a>
        </div>
        <table>
            <thead><tr><?php foreach (['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'] as $d): ?><th scope="col"><?= $d ?></th><?php endforeach; ?></tr></thead>
            <tbody>
            <?php for ($ts = $startTs; $ts <= $endTs; $ts = strtotime('+1 day', $ts)):
                $d = date('Y-m-d', $ts);
                $cls = [];
                if (date('Y-m', $ts) !== $month) { $cls[] = 'other'; }
                if ($d === date('Y-m-d')) { $cls[] = 'today'; }
                if ($d === $selected) { $cls[] = 'sel'; }
                if (isset($busyDays[$d])) { $cls[] = 'has'; }
                if (date('w', $ts) === '0'): ?><tr><?php endif; ?>
                <td class="<?= e(implode(' ', $cls)) ?>">
                    <a href="<?= e(url('staff/appointments', ['date' => $d] + $calQuery)) ?>" aria-label="<?= e(fmt_date($d)) ?>"><?= date('j', $ts) ?></a>
                </td>
                <?php if (date('w', $ts) === '6'): ?></tr><?php endif; ?>
            <?php endfor; ?>
            </tbody>
        </table>
        <div class="legend-row">
            <span><i style="background:var(--app-rose)"></i> Has bookings</span>
            <span><i style="box-shadow:inset 0 0 0 1.5px var(--app-rose)"></i> Today</span>
        </div>
        <div class="mini-cal-foot">
            <span><?= $selected ? e(fmt_date($selected)) : 'All dates' ?></span>
            <?php if ($selected): ?>
                <a href="<?= e(url('staff/appointments', $calQuery)) ?>">Show all</a>
            <?php else: ?>
                <a href="<?= e(url('staff/appointments', ['tab' => 'today'])) ?>">Today</a>
            <?php endif; ?>
        </div>
    </aside>
</div>
