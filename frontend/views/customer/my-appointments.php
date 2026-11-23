<?php /** @var array $appointments */ ?>
<section class="card">
    <h1 class="card-title">My appointments</h1>

    <?php if (!$appointments): ?>
        <p class="muted">You have no appointments yet.</p>
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
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($appointments as $a): ?>
                    <tr>
                        <td><?= e(date('M j, Y', strtotime($a['appt_date']))) ?></td>
                        <td><?= e(date('g:i A', strtotime($a['start_time']))) ?> – <?= e(date('g:i A', strtotime($a['end_time']))) ?></td>
                        <td><?= e($a['service']) ?></td>
                        <td><?= e($a['stylist']) ?></td>
                        <td><span class="badge badge-<?= e($a['status']) ?>"><?= e(str_replace('_', '-', $a['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
