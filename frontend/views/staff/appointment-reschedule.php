<?php /** @var array $appt  @var array $pick  @var array $stylists */
$ready = $pick['service'] && $pick['stylist'] && !$pick['problem'] && !$pick['full'] && $pick['slots'];
?>
<div class="page-head">
    <p><a href="<?= e(url('staff/appointments')) ?>">← Back to appointments</a></p>
    <h1 class="card-title">Reschedule appointment <span class="ref ref-lg"><?= e(appt_ref($appt['id'])) ?></span></h1>
    <p class="muted">
        <?= e($appt['customer']) ?> · <?= e($appt['service']) ?> with <?= e($appt['stylist']) ?> ·
        currently <?= e(fmt_date($appt['appt_date'])) ?> at <?= e(fmt_time($appt['start_time'])) ?>
    </p>
</div>

<div class="stack">
    <section class="card">
        <h2 class="section-title">1. New stylist and date</h2>
        <form method="get" action="index.php" class="form form-grid">
            <input type="hidden" name="page" value="staff/appointments/reschedule">
            <input type="hidden" name="id" value="<?= e($appt['id']) ?>">

            <div class="field">
                <label for="stylist">Stylist</label>
                <select id="stylist" name="stylist" required>
                    <?php foreach ($stylists as $st): ?>
                        <option value="<?= e($st['id']) ?>" <?= $pick['stylist_id'] === (int) $st['id'] ? 'selected' : '' ?>><?= e($st['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="date">Date</label>
                <input type="date" id="date" name="date" value="<?= e($pick['date']) ?>" min="<?= e(date('Y-m-d')) ?>" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-ghost btn-small">Show available times</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2 class="section-title">2. New time</h2>
        <form method="post" action="<?= e(url('staff/appointments/reschedule', ['id' => $appt['id']])) ?>" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e($appt['id']) ?>">
            <input type="hidden" name="stylist_id" value="<?= e($pick['stylist_id']) ?>">
            <input type="hidden" name="date" value="<?= e($pick['date']) ?>">

            <?php $selectedTime = ''; require __DIR__ . '/_slot-picker.php'; ?>

            <?php if ($ready): ?>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Move appointment</button>
                </div>
            <?php endif; ?>
        </form>
    </section>
</div>
