<?php /** @var array $values  @var array $logs  @var string $tab  @var ?string $mailProblem  @var string $mailFrom */
$val = static fn (string $key) => old_or($key, $values[$key]);
$checked = static fn (string $key) => (isset($_SESSION['old']) ? isset($_SESSION['old'][$key]) : $values[$key] === '1') ? 'checked' : '';
$typeLabels = ['confirmation' => 'Confirmation', 'cancellation' => 'Cancellation', 'reminder' => 'Reminder'];
$tabs = [
    'bookings'      => ['Bookings & Hours', 'clock'],
    'salon'         => ['Salon Profile', 'pin'],
    'notifications' => ['Notifications', 'bell'],
];
?>
<div class="ph">
    <div>
        <h1>Settings</h1>
        <p>Manage your salon settings</p>
    </div>
</div>

<div class="set-grid">
    <nav class="panel set-nav" aria-label="Settings sections">
        <?php foreach ($tabs as $key => [$label, $ic]): ?>
            <a href="<?= e(url('admin/settings', ['tab' => $key])) ?>" <?= $tab === $key ? 'aria-current="page"' : '' ?>><?= icon($ic) ?> <?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>

    <div>
        <form method="post" action="<?= e(url('admin/settings', ['tab' => $tab])) ?>" class="panel" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="tab" value="<?= e($tab) ?>">

            <?php if ($tab === 'bookings'): ?>
                <div class="set-section">
                    <h3>Booking limits</h3>
                    <p>How many appointments the salon takes and how far ahead customers can book.</p>
                    <div class="f-grid">
                        <div class="field">
                            <label for="daily_appointment_limit">Daily appointment limit</label>
                            <input type="number" id="daily_appointment_limit" name="daily_appointment_limit" value="<?= $val('daily_appointment_limit') ?>" min="1" max="500" required>
                            <small class="hint">Bookings are refused once a day reaches this number.</small>
                        </div>
                        <div class="field">
                            <label for="booking_days_ahead">Customers can book up to (days ahead)</label>
                            <input type="number" id="booking_days_ahead" name="booking_days_ahead" value="<?= $val('booking_days_ahead') ?>" min="1" max="60" required>
                        </div>
                    </div>
                </div>
                <div class="set-section">
                    <h3>Opening hours</h3>
                    <p>Appointments must start and end inside these hours.</p>
                    <div class="f-grid">
                        <div class="field">
                            <label for="opening_time">Opening time</label>
                            <input type="time" id="opening_time" name="opening_time" value="<?= $val('opening_time') ?>" required>
                        </div>
                        <div class="field">
                            <label for="closing_time">Closing time</label>
                            <input type="time" id="closing_time" name="closing_time" value="<?= $val('closing_time') ?>" required>
                        </div>
                        <div class="field">
                            <label for="slot_interval_minutes">Time slot interval</label>
                            <select id="slot_interval_minutes" name="slot_interval_minutes">
                                <?php foreach (['15', '30', '60'] as $m): ?>
                                    <option value="<?= $m ?>" <?= $val('slot_interval_minutes') === $m ? 'selected' : '' ?>>Every <?= $m ?> minutes</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

            <?php elseif ($tab === 'salon'): ?>
                <div class="set-section">
                    <h3>Contact details</h3>
                    <p>Shown on the website and in emails. Leave a box empty to hide it.</p>
                    <div class="f-grid">
                        <div class="field">
                            <label for="salon_phone">Phone</label>
                            <input type="tel" id="salon_phone" name="salon_phone" value="<?= $val('salon_phone') ?>" maxlength="25">
                        </div>
                        <div class="field">
                            <label for="salon_email">Email</label>
                            <input type="email" id="salon_email" name="salon_email" value="<?= $val('salon_email') ?>" maxlength="150">
                        </div>
                        <div class="field full">
                            <label for="salon_address">Address</label>
                            <input type="text" id="salon_address" name="salon_address" value="<?= $val('salon_address') ?>" maxlength="200">
                        </div>
                    </div>
                </div>
                <div class="set-section">
                    <h3>Social media</h3>
                    <p>Full links starting with https://</p>
                    <div class="f-grid">
                        <div class="field">
                            <label for="instagram_url">Instagram</label>
                            <input type="url" id="instagram_url" name="instagram_url" value="<?= $val('instagram_url') ?>" placeholder="https://instagram.com/…">
                        </div>
                        <div class="field">
                            <label for="facebook_url">Facebook</label>
                            <input type="url" id="facebook_url" name="facebook_url" value="<?= $val('facebook_url') ?>" placeholder="https://facebook.com/…">
                        </div>
                        <div class="field">
                            <label for="tiktok_url">TikTok</label>
                            <input type="url" id="tiktok_url" name="tiktok_url" value="<?= $val('tiktok_url') ?>" placeholder="https://tiktok.com/@…">
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="set-section">
                    <h3>Email notifications</h3>
                    <p>Emails sent to customers who have an email address.</p>
                    <label class="switch">
                        <span><strong>Confirmation &amp; cancellation emails</strong><small>Sent when a booking is confirmed or cancelled.</small></span>
                        <span class="toggle"><input type="checkbox" name="booking_emails_enabled" value="1" <?= $checked('booking_emails_enabled') ?>><span></span></span>
                    </label>
                    <label class="switch">
                        <span><strong>Appointment reminders</strong><small>Sent automatically before each confirmed appointment.</small></span>
                        <span class="toggle"><input type="checkbox" name="reminders_enabled" value="1" <?= $checked('reminders_enabled') ?>><span></span></span>
                    </label>
                    <div class="f-grid" style="margin-top:14px">
                        <div class="field">
                            <label for="reminder_hours_before">Send reminders this many hours before</label>
                            <input type="number" id="reminder_hours_before" name="reminder_hours_before" value="<?= $val('reminder_hours_before') ?>" min="1" max="72" required>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-foot">
                <a href="<?= e(url('admin/settings', ['tab' => $tab])) ?>" class="btn btn-ghost">Discard</a>
                <button type="submit" class="btn btn-primary"><?= icon('check') ?> Save changes</button>
            </div>
        </form>

        <?php if ($tab === 'notifications'): ?>
            <section class="panel" style="margin-top:20px">
                <div class="panel-head">
                    <div>
                        <h2>Email setup</h2>
                        <?php if ($mailProblem): ?>
                            <p class="mail-status mail-bad"><?= icon('x', 'icon icon-sm') ?> Not ready: <?= e($mailProblem) ?></p>
                        <?php else: ?>
                            <p class="mail-status mail-ok"><?= icon('check', 'icon icon-sm') ?> Set up. Emails are sent from <strong><?= e($mailFrom) ?></strong>. Send a test to be sure.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <form method="post" action="<?= e(url('admin/settings/test-email')) ?>" class="toolbar" style="margin:0">
                    <?= csrf_field() ?>
                    <input type="email" name="to" required placeholder="Send a test to… e.g. your own Gmail" class="grow" aria-label="Test email address">
                    <button type="submit" class="btn btn-primary"><?= icon('mail') ?> Send test email</button>
                </form>
            </section>

            <section class="panel" style="margin-top:20px">
                <div class="panel-head">
                    <div>
                        <h2>Reminders</h2>
                        <p class="hint" style="margin:4px 0 0">Sent automatically every hour by the scheduled job. Use the button to send the ones due now.</p>
                    </div>
                    <form method="post" action="<?= e(url('admin/settings/send-reminders')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-ghost"><?= icon('mail') ?> Send due reminders now</button>
                    </form>
                </div>
            </section>

            <section class="panel panel-flush" style="margin-top:20px">
                <div class="panel-head"><h2>Email log</h2><span class="hint">Last 25 emails</span></div>
                <?php if (!$logs): ?>
                    <p class="empty">No emails sent yet.</p>
                <?php else: ?>
                    <div class="table-wrap" style="margin-top:16px">
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
                                    <td>
                                        <?php if ($l['status'] === 'sent'): ?>
                                            <span class="badge badge-confirmed">Sent</span>
                                        <?php else: ?>
                                            <span class="badge badge-cancelled">Failed</span>
                                            <span class="cell-sub"><?= e($l['error_message']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</div>
