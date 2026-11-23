<?php
// Variables come from AppointmentController::book() through view().
// Step 1 uses $services; step 2 $service, $stylists; step 3 adds $stylist, $days, $date, $slots; step 4 adds $time, $endTime.
/** @var int $step */
/** @var array $services */
/** @var array $service */
/** @var array $stylists */
/** @var array $stylist */
/** @var array $days */
/** @var string $date */
/** @var array $slots */
/** @var string $time */
/** @var string $endTime */
$steps = [1 => 'Service', 2 => 'Stylist', 3 => 'Date & time', 4 => 'Confirm'];
?>
<div class="page-head center">
    <p class="eyebrow">Book your appointment</p>
    <h1 class="card-title">
        <?= match ($step) {
            1 => 'Choose a service',
            2 => 'Choose your stylist',
            3 => 'Pick a date and time',
            default => 'Confirm your booking',
        } ?>
    </h1>
</div>

<ol class="steps" aria-label="Booking progress">
    <?php foreach ($steps as $n => $label): ?>
        <li class="<?= $n < $step ? 'done' : ($n === $step ? 'current' : '') ?>" <?= $n === $step ? 'aria-current="step"' : '' ?>>
            <span class="step-num"><?= $n ?></span> <?= e($label) ?>
        </li>
    <?php endforeach; ?>
</ol>

<?php if ($step === 1): ?>
    <?php if (!$services): ?>
        <div class="card center"><p class="muted">No services are available for booking right now. Please check back soon.</p></div>
    <?php else: ?>
        <div class="choice-grid">
            <?php foreach ($services as $s): ?>
                <a class="choice" href="<?= e(url('customer/book', ['service' => $s['id']])) ?>">
                    <span class="choice-title"><?= e($s['name']) ?></span>
                    <span class="choice-meta"><?= e($s['duration_minutes']) ?> min</span>
                    <span class="choice-price"><?= e(peso($s['price'])) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php elseif ($step === 2): ?>
    <p class="summary-line">
        <strong><?= e($service['name']) ?></strong> · <?= e($service['duration_minutes']) ?> min · <?= e(peso($service['price'])) ?>
        <a href="<?= e(url('customer/book')) ?>">Change</a>
    </p>
    <?php if (!$stylists): ?>
        <div class="card center"><p class="muted">No stylist is available for this service right now.</p></div>
    <?php else: ?>
        <div class="choice-grid">
            <?php foreach ($stylists as $st): ?>
                <a class="choice choice-person" href="<?= e(url('customer/book', ['service' => $service['id'], 'stylist' => $st['id']])) ?>">
                    <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($st['full_name'], 0, 1))) ?></span>
                    <span class="choice-title"><?= e($st['full_name']) ?></span>
                    <span class="choice-meta"><?= e($st['specialty'] ?? 'Stylist') ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php elseif ($step === 3): ?>
    <p class="summary-line">
        <strong><?= e($service['name']) ?></strong> with <strong><?= e($stylist['full_name']) ?></strong>
        <a href="<?= e(url('customer/book', ['service' => $service['id']])) ?>">Change</a>
    </p>

    <section class="card">
        <h2 class="section-title">Date</h2>
        <div class="day-strip" role="list">
            <?php foreach ($days as $d => $info): ?>
                <?php if ($info['full']): ?>
                    <span class="day day-full" role="listitem" aria-label="<?= e(fmt_date($d)) ?>, fully booked">
                        <span class="day-week"><?= e($info['weekday']) ?></span>
                        <span class="day-num"><?= e($info['day']) ?></span>
                        <span class="day-month">Full</span>
                    </span>
                <?php else: ?>
                    <a role="listitem" class="day <?= $d === $date ? 'day-selected' : '' ?>"
                       href="<?= e(url('customer/book', ['service' => $service['id'], 'stylist' => $stylist['id'], 'date' => $d])) ?>"
                       aria-label="<?= e(fmt_date($d)) ?>" <?= $d === $date ? 'aria-current="date"' : '' ?>>
                        <span class="day-week"><?= e($info['weekday']) ?></span>
                        <span class="day-num"><?= e($info['day']) ?></span>
                        <span class="day-month"><?= e($info['month']) ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <h2 class="section-title slots-title">
            Available times<?= $date !== '' ? ' on ' . e(fmt_date($date)) : '' ?>
        </h2>
        <?php if (!$slots): ?>
            <p class="muted">No free times on this day. Please pick another date.</p>
        <?php else: ?>
            <div class="slot-grid">
                <?php foreach ($slots as $slot): ?>
                    <a class="slot" href="<?= e(url('customer/book', ['service' => $service['id'], 'stylist' => $stylist['id'], 'date' => $date, 'time' => $slot])) ?>">
                        <?= e(fmt_time($slot)) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

<?php else: ?>
    <section class="card narrow-card centered-card">
        <dl class="summary">
            <div><dt>Service</dt><dd><?= e($service['name']) ?></dd></div>
            <div><dt>Stylist</dt><dd><?= e($stylist['full_name']) ?></dd></div>
            <div><dt>Date</dt><dd><?= e(fmt_date($date)) ?></dd></div>
            <div><dt>Time</dt><dd><?= e(fmt_time($time)) ?> – <?= e(fmt_time($endTime)) ?></dd></div>
            <div><dt>Price</dt><dd><?= e(peso($service['price'])) ?></dd></div>
        </dl>

        <form method="post" action="<?= e(url('customer/book')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="service_id" value="<?= e($service['id']) ?>">
            <input type="hidden" name="stylist_id" value="<?= e($stylist['id']) ?>">
            <input type="hidden" name="date" value="<?= e($date) ?>">
            <input type="hidden" name="time" value="<?= e($time) ?>">
            <button type="submit" class="btn btn-primary btn-block">Request booking</button>
        </form>
        <p class="hint center">The salon will confirm your request. You can cancel anytime before your appointment.</p>
        <p class="center">
            <a href="<?= e(url('customer/book', ['service' => $service['id'], 'stylist' => $stylist['id'], 'date' => $date])) ?>">← Choose a different time</a>
        </p>
    </section>
<?php endif; ?>
