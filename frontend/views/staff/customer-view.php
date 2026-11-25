<?php /** @var array $customer  @var array $history  @var array $summary */
$statusLabels = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'no_show' => 'No-show'];
$sourceLabels = ['online' => 'Online', 'walk_in' => 'Walk-in', 'phone' => 'Phone'];
?>
<div class="page-head head-row">
    <div>
        <p><a href="<?= e(url('staff/customers')) ?>">← Back to customers</a></p>
        <h1 class="card-title"><?= e($customer['full_name']) ?> <span class="ref ref-lg"><?= e(cust_ref($customer['id'])) ?></span></h1>
        <p class="muted">
            <?= $customer['user_id'] ? 'Has an online account (' . e($customer['account_email']) . ')' : 'Walk-in / phone customer, no online account' ?>
            · Customer since <?= e(date('M Y', strtotime($customer['created_at']))) ?>
        </p>
    </div>
    <a href="<?= e(url('staff/appointments/new', ['customer' => $customer['id']])) ?>" class="btn btn-primary">New appointment</a>
</div>

<div class="stat-grid">
    <div class="stat"><span class="stat-label">Appointments</span><span class="stat-value"><?= e($summary['total']) ?></span></div>
    <div class="stat"><span class="stat-label">Completed</span><span class="stat-value"><?= e($summary['completed']) ?></span></div>
    <div class="stat"><span class="stat-label">Cancelled</span><span class="stat-value"><?= e($summary['cancelled']) ?></span></div>
    <div class="stat"><span class="stat-label">No-shows</span><span class="stat-value"><?= e($summary['no_show']) ?></span></div>
    <div class="stat"><span class="stat-label">Total spent</span><span class="stat-value stat-money"><?= e(peso($summary['spent'])) ?></span><span class="stat-note">Completed services</span></div>
</div>

<div class="stack">
    <section class="card">
        <h2 class="section-title">Details</h2>
        <form method="post" action="<?= e(url('staff/customers/view', ['id' => $customer['id']])) ?>" class="form" novalidate>
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="field">
                    <label for="full_name">Full name</label>
                    <input type="text" id="full_name" name="full_name" value="<?= old_or('full_name', $customer['full_name']) ?>" required maxlength="100">
                </div>
                <div class="field">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" value="<?= old_or('phone', $customer['phone'] ?? '') ?>" maxlength="20">
                </div>
                <div class="field">
                    <label for="email">Email for notifications</label>
                    <input type="email" id="email" name="email" value="<?= old_or('email', $customer['email'] ?? '') ?>" maxlength="150">
                    <?php if ($customer['user_id']): ?><small class="hint">This doesn't change the customer's login email.</small><?php endif; ?>
                </div>
            </div>
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="3" maxlength="1000" placeholder="e.g. prefers Sofia, sensitive scalp, usual hair color"><?= old_or('notes', $customer['notes'] ?? '') ?></textarea>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save details</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2 class="section-title">Appointment history</h2>
        <?php if (!$history): ?>
            <p class="muted">No appointments yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Booking #</th>
                            <th scope="col">Date &amp; time</th>
                            <th scope="col">Service</th>
                            <th scope="col">Stylist</th>
                            <th scope="col">Price</th>
                            <th scope="col">Source</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($history as $a): ?>
                        <tr>
                            <td><span class="ref"><?= e(appt_ref($a['id'])) ?></span></td>
                            <td><?= e(fmt_date($a['appt_date'])) ?><br><span class="muted"><?= e(fmt_time($a['start_time'])) ?> – <?= e(fmt_time($a['end_time'])) ?></span></td>
                            <td><?= e($a['service']) ?></td>
                            <td><?= e($a['stylist']) ?></td>
                            <td><?= e(peso($a['price'])) ?></td>
                            <td><?= e($sourceLabels[$a['source']] ?? $a['source']) ?></td>
                            <td><span class="badge badge-<?= e($a['status']) ?>"><?= e($statusLabels[$a['status']] ?? $a['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="hint">Prices shown are the current service prices.</p>
        <?php endif; ?>
    </section>
</div>
