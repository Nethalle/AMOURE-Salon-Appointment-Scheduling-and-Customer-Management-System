<?php /** @var array $values  @var array $logs */
$val = static fn (string $key) => old_or($key, $values[$key]);
$checked = static fn (string $key) => (isset($_SESSION['old']) ? isset($_SESSION['old'][$key]) : $values[$key] === '1') ? 'checked' : '';
$typeLabels = ['confirmation' => 'Confirmation', 'cancellation' => 'Cancellation', 'reminder' => 'Reminder'];
?>
<div class="page-head">
    <h1 class="card-title">Settings</h1>
    <p class="muted">Booking limits, opening hours and emails.</p>
</div>

<div class="stack">
    <form method="post" action="<?= e(url('admin/settings')) ?>" class="stack" novalidate>
        <?= csrf_field() ?>

        <section class="card">
            <h2 class="section-title">Bookings</h2>
            <div class="form form-grid">
                <div class="field">
                    <label for="daily_appointment_limit">Daily appointment limit</label>
                    <input type="number" id="daily_appointment_limit" name="daily_appointment_limit" value="<?= $val('daily_appointment_limit') ?>" min="1" max="500" required>
                    <small class="hint">Bookings refused once a day reaches this number.</small>
                </div>
                <div class="field">
                    <label for="opening_time">Opening time</label>
                    <input type="time" id="opening_time" name="opening_time" value="<?= $val('opening_time') ?>" required>
                </div>
                <div class="field">
                    <label for="closing_time">Closing time</label>
                    <input type="time" id="closing_time" name="closing_time" value="<?= $val('closing_time') ?>" required>
                    <small class="hint">Appointments must end by this time.</small>
                </div>
                <div class="field">
                    <label for="slot_interval_minutes">Time slot interval</label>
                    <select id="slot_interval_minutes" name="slot_interval_minutes">
                        <?php foreach (['15', '30', '60'] as $m): ?>
                            <option value="<?= $m ?>" <?= $val('slot_interval_minutes') === $m ? 'selected' : '' ?>>Every <?= $m ?> minutes</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="booking_days_ahead">Customers can book up to</label>
                    <input type="number" id="booking_days_ahead" name="booking_days_ahead" value="<?= $val('booking_days_ahead') ?>" min="1" max="60" required>
                    <small class="hint">Days ahead.</small>
                </div>
            </div>
        </section>

        <section class="card">
            <h2 class="section-title">Emails</h2>
            <div class="form">
                <label class="check">
                    <input type="checkbox" name="booking_emails_enabled" value="1" <?= $checked('booking_emails_enabled') ?>>
                    Send confirmation and cancellation emails
                </label>
                <label class="check">
                    <input type="checkbox" name="reminders_enabled" value="1" <?= $checked('reminders_enabled') ?>>
                    Send reminder emails before appointments
                </label>
                <div class="form-grid">
                    <div class="field">
                        <label for="reminder_hours_before">Send reminders this many hours before</label>
                        <input type="number" id="reminder_hours_before" name="reminder_hours_before" value="<?= $val('reminder_hours_before') ?>" min="1" max="72" required>
                    </div>
                </div>
            </div>
        </section>

        <div>
            <button type="submit" class="btn btn-primary">Save settings</button>
        </div>
    </form>

    <section class="card">
        <div class="head-row">
            <div>
                <h2 class="section-title">Reminders</h2>
                <p class="muted" style="margin:0">Reminders are sent automatically every hour by the scheduled job. Use this button to send the ones due now.</p>
            </div>
            <form method="post" action="<?= e(url('admin/settings/send-reminders')) ?>" class="inline-form">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-gold">Send due reminders now</button>
            </form>
        </div>
    </section>

    <section class="card">
        <h2 class="section-title">Email log</h2>
        <p class="muted" style="margin:0">The last 25 emails the system tried to send.</p>
        <?php if (!$logs): ?>
            <p class="muted">No emails sent yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Sent at</th>
                            <th scope="col">Type</th>
                            <th scope="col">To</th>
                            <th scope="col">Appointment</th>
                            <th scope="col">Result</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td><?= e(date('M j, g:i A', strtotime($l['created_at']))) ?></td>
                            <td><?= e($typeLabels[$l['type']] ?? $l['type']) ?></td>
                            <td><?= e($l['recipient']) ?></td>
                            <td><?= $l['appt_date'] ? e(fmt_date($l['appt_date']) . ' ' . fmt_time($l['start_time'])) : '—' ?></td>
                            <td class="wrap">
                                <?php if ($l['status'] === 'sent'): ?>
                                    <span class="badge badge-completed">Sent</span>
                                <?php else: ?>
                                    <span class="badge badge-no_show">Failed</span>
                                    <br><small class="muted"><?= e($l['error_message']) ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
