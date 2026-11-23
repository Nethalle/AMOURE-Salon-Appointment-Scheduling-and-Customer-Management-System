<?php /** @var array $appointments  @var array $filters  @var array $stylists  @var string $back */
$actionLabels = [
    'confirm'  => ['Confirm',  'btn-primary', ''],
    'complete' => ['Complete', 'btn-ghost',   ''],
    'no_show'  => ['No-show',  'btn-ghost',   'Mark this appointment as a no-show?'],
    'cancel'   => ['Cancel',   'btn-ghost',   'Cancel this appointment? The time slot will be freed.'],
];
$statusLabels = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'no_show' => 'No-show'];
$sourceLabels = ['online' => 'Online', 'walk_in' => 'Walk-in', 'phone' => 'Phone'];
?>
<div class="page-head head-row">
    <div>
        <h1 class="card-title">Appointments</h1>
        <p class="muted">Confirm requests, update statuses and reschedule bookings.</p>
    </div>
    <a href="<?= e(url('staff/appointments/new')) ?>" class="btn btn-primary">New appointment</a>
</div>

<div class="stack">
    <section class="card">
        <form method="get" action="index.php" class="form form-grid filters">
            <input type="hidden" name="page" value="staff/appointments">

            <div class="field">
                <label for="from">From</label>
                <input type="date" id="from" name="from" value="<?= e($filters['from']) ?>">
            </div>
            <div class="field">
                <label for="to">To</label>
                <input type="date" id="to" name="to" value="<?= e($filters['to']) ?>">
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Any status</option>
                    <?php foreach ($statusLabels as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="stylist">Stylist</label>
                <select id="stylist" name="stylist">
                    <option value="0">Any stylist</option>
                    <?php foreach ($stylists as $st): ?>
                        <option value="<?= e($st['id']) ?>" <?= $filters['stylist'] === (int) $st['id'] ? 'selected' : '' ?>><?= e($st['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="q">Customer</label>
                <input type="search" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Name, phone or email">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-small">Filter</button>
                <a href="<?= e(url('staff/appointments')) ?>" class="btn btn-ghost btn-small">Reset</a>
                <a href="<?= e(url('staff/appointments', ['from' => '', 'status' => 'pending'])) ?>" class="btn btn-ghost btn-small">All pending requests</a>
            </div>
        </form>
    </section>

    <section class="card">
        <h2 class="section-title"><?= count($appointments) ?> appointment<?= count($appointments) === 1 ? '' : 's' ?></h2>
        <?php if (!$appointments): ?>
            <p class="muted">No appointments match these filters.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Date &amp; time</th>
                            <th scope="col">Customer</th>
                            <th scope="col">Service</th>
                            <th scope="col">Stylist</th>
                            <th scope="col">Source</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($appointments as $a): ?>
                        <tr>
                            <td>
                                <?= e(fmt_date($a['appt_date'])) ?><br>
                                <span class="muted"><?= e(fmt_time($a['start_time'])) ?> – <?= e(fmt_time($a['end_time'])) ?></span>
                            </td>
                            <td>
                                <?= e($a['customer']) ?>
                                <?php if ($a['phone']): ?><br><span class="muted"><?= e($a['phone']) ?></span><?php endif; ?>
                            </td>
                            <td><?= e($a['service']) ?></td>
                            <td><?= e($a['stylist']) ?></td>
                            <td><?= e($sourceLabels[$a['source']] ?? $a['source']) ?></td>
                            <td><span class="badge badge-<?= e($a['status']) ?>"><?= e($statusLabels[$a['status']] ?? $a['status']) ?></span></td>
                            <td>
                                <div class="actions">
                                    <?php foreach ($a['actions'] as $action): [$label, $style, $confirm] = $actionLabels[$action]; ?>
                                        <form method="post" action="<?= e(url('staff/appointments/status')) ?>" class="inline-form"
                                              <?= $confirm ? 'data-confirm="' . e($confirm) . '"' : '' ?>>
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= e($a['id']) ?>">
                                            <input type="hidden" name="action" value="<?= e($action) ?>">
                                            <input type="hidden" name="back" value="<?= e($back) ?>">
                                            <button type="submit" class="btn btn-small <?= e($style) ?>"><?= e($label) ?></button>
                                        </form>
                                    <?php endforeach; ?>
                                    <?php if ($a['can_reschedule']): ?>
                                        <a href="<?= e(url('staff/appointments/reschedule', ['id' => $a['id']])) ?>" class="btn btn-small btn-ghost">Reschedule</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
