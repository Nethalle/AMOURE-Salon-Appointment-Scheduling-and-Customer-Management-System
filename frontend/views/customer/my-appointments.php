<?php /** @var array $upcoming  @var array $past */ ?>
<div class="page-head head-row">
    <div>
        <h1 class="card-title">My appointments</h1>
        <p class="muted">Your upcoming bookings and history.</p>
    </div>
    <a href="<?= e(url('customer/book')) ?>" class="btn btn-primary">Book appointment</a>
</div>

<div class="stack">
    <section class="card">
        <h2 class="section-title">Upcoming</h2>
        <?php if (!$upcoming): ?>
            <p class="muted">No upcoming appointments. <a href="<?= e(url('customer/book')) ?>">Book one now</a>.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Time</th>
                            <th scope="col">Service</th>
                            <th scope="col">Stylist</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($upcoming as $a): ?>
                        <tr>
                            <td><?= e(fmt_date($a['appt_date'])) ?></td>
                            <td><?= e(fmt_time($a['start_time'])) ?> – <?= e(fmt_time($a['end_time'])) ?></td>
                            <td><?= e($a['service']) ?></td>
                            <td><?= e($a['stylist']) ?></td>
                            <td><span class="badge badge-<?= e($a['status']) ?>"><?= e($a['status'] === 'pending' ? 'Waiting for confirmation' : 'Confirmed') ?></span></td>
                            <td>
                                <?php if ($a['can_cancel']): ?>
                                    <form method="post" action="<?= e(url('customer/cancel')) ?>" class="inline-form"
                                          data-confirm="Cancel your <?= e($a['service']) ?> appointment on <?= e(fmt_date($a['appt_date'])) ?>?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= e($a['id']) ?>">
                                        <button type="submit" class="btn btn-small btn-ghost">Cancel</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2 class="section-title">History</h2>
        <?php if (!$past): ?>
            <p class="muted">No past appointments yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Time</th>
                            <th scope="col">Service</th>
                            <th scope="col">Stylist</th>
                            <th scope="col">Status</th>
                            <th scope="col">Review</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($past as $a): ?>
                        <tr>
                            <td><?= e(fmt_date($a['appt_date'])) ?></td>
                            <td><?= e(fmt_time($a['start_time'])) ?></td>
                            <td><?= e($a['service']) ?></td>
                            <td><?= e($a['stylist']) ?></td>
                            <td><span class="badge badge-<?= e($a['status']) ?>"><?= e(ucfirst(str_replace('_', '-', $a['status']))) ?></span></td>
                            <td>
                                <?php if ($a['review_rating']): ?>
                                    <span class="stars" aria-label="<?= e($a['review_rating']) ?> out of 5 stars"><?= e(stars((int) $a['review_rating'])) ?></span>
                                <?php elseif ($a['status'] === 'completed'): ?>
                                    <a href="<?= e(url('customer/review', ['id' => $a['id']])) ?>" class="btn btn-small btn-ghost">Leave a review</a>
                                <?php else: ?>
                                    <span class="muted">—</span>
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
