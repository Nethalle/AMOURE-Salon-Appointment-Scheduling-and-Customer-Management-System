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
/** @var array $grid */
/** @var string $time */
/** @var string $endTime */
$steps = [1 => 'Service', 2 => 'Stylist', 3 => 'Date & Time', 4 => 'Confirm'];
?>
<div class="book-hero">
    <div class="book-hero-text">
        <p class="book-kicker">Book your appointment</p>
        <h1><?= match ($step) {
            1 => 'Choose a Service',
            2 => 'Choose your Stylist',
            3 => 'Pick a Date &amp; Time',
            default => 'Confirm your Booking',
        } ?></h1>
        <p><?= match ($step) {
            1 => 'Select a service, stylist, and schedule your appointment with ease.',
            2 => 'Pick who you would like to do your service.',
            3 => 'Choose a day and an open time. Red times are already taken.',
            default => 'Check the details below, then confirm your booking.',
        } ?></p>
    </div>
    <svg class="book-hero-art" viewBox="0 0 520 200" preserveAspectRatio="xMaxYMid slice" aria-hidden="true">
        <g class="bh-petals">
            <path d="M420 92c-18-40-6-72 14-86 14 26 10 58-14 86Z"/><path d="M420 92c34-28 68-26 86-8-24 20-56 22-86 8Z"/>
            <path d="M420 92c40 6 60 32 58 56-30-4-52-24-58-56Z"/><path d="M420 92c-4 40-30 62-56 62 2-30 24-52 56-62Z"/>
            <path d="M420 92c-38-10-56-40-50-62 30 8 48 30 50 62Z"/>
        </g>
        <g class="bh-petals bh-small">
            <path d="M330 40c-8-18-2-32 6-38 6 12 4 26-6 38Z"/><path d="M330 40c16-12 30-12 38-4-10 9-24 10-38 4Z"/>
            <path d="M330 40c18 2 26 14 26 24-14-2-24-10-26-24Z"/><path d="M330 40c-2 18-14 28-24 28 0-14 10-24 24-28Z"/><path d="M330 40c-16-4-24-18-22-28 14 4 22 14 22 28Z"/>
        </g>
        <circle cx="420" cy="92" r="9" class="bh-heart"/><circle cx="330" cy="40" r="4" class="bh-heart"/>
        <path d="M250 190 360 118" class="bh-handle"/><path d="M352 124c8-14 22-22 34-20-2 14-12 26-28 30Z" class="bh-brush"/>
    </svg>
</div>

<?php
// Finished steps link back, so customers can go back without losing their place.
$stepLinks = [
    1 => url('customer/book'),
    2 => isset($service['id']) ? url('customer/book', ['service' => $service['id']]) : null,
    3 => isset($service['id'], $stylist['id']) ? url('customer/book', ['service' => $service['id'], 'stylist' => $stylist['id'], 'date' => $date ?? '']) : null,
];
?>
<ol class="steps" aria-label="Booking progress">
    <?php foreach ($steps as $n => $label): $done = $n < $step; ?>
        <li class="<?= $done ? 'done' : ($n === $step ? 'current' : '') ?>" <?= $n === $step ? 'aria-current="step"' : '' ?>>
            <?php if ($done && !empty($stepLinks[$n])): ?>
                <a href="<?= e($stepLinks[$n]) ?>" title="Go back to <?= e($label) ?>"><span class="step-num"><?= icon('check') ?></span> <?= e($label) ?></a>
            <?php else: ?>
                <span class="step-num"><?= $n ?></span> <?= e($label) ?>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ol>

<?php if ($step === 1): ?>
    <?php if (!$services): ?>
        <div class="card center"><p class="muted">No services are available for booking right now. Please check back soon.</p></div>
    <?php else: ?>
        <?php $groups = group_services($services); ?>
        <div class="book-filter">
            <div class="svc-search search-box">
                <?= icon('search') ?>
                <input type="search" id="service-search" placeholder="Search services, e.g. rebond, gel, color…" aria-label="Search services" autocomplete="off">
            </div>
            <nav class="cat-chips" aria-label="Show a category">
            <a href="#all" class="is-all" aria-current="true"><?= icon('grid', 'icon icon-sm') ?> All Services</a>
            <?php foreach ($groups as $cat => $rows): ?>
                <a href="#cat-<?= e(slugify($cat)) ?>"><?= icon(category_icon($cat), 'icon icon-sm') ?> <?= e($cat) ?> <span><?= count($rows) ?></span></a>
            <?php endforeach; ?>
            </nav>
        </div>
        <?php foreach ($groups as $cat => $rows): ?>
            <section class="cat-section book-cat" id="cat-<?= e(slugify($cat)) ?>">
                <h2 class="cat-title"><span class="kpi-icon"><?= icon(category_icon($cat)) ?></span> <?= e($cat) ?>
                    <small>from <?= e(peso(min(array_column($rows, 'price')))) ?></small></h2>
                <div class="choice-list">
                    <?php foreach ($rows as $s): ?>
                        <a class="choice-row" data-search="<?= e(mb_strtolower($s['name'] . ' ' . $cat)) ?>" href="<?= e(url('customer/book', ['service' => $s['id']])) ?>">
                            <span class="choice-row-icon"><?= icon(stripos($s['name'], 'senior') !== false ? 'star' : category_icon($cat)) ?></span>
                            <span class="choice-row-box">
                                <span class="choice-row-name"><?= e($s['name']) ?><small><?= icon('clock', 'icon icon-xs') ?> <?= e(fmt_duration((int) $s['duration_minutes'])) ?></small></span>
                                <span class="choice-row-price"><?= e(peso($s['price'])) ?></span>
                                <?= icon('chev-right', 'icon icon-sm') ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
        <p class="empty svc-none" hidden>No service matches "<span></span>". Try another word, like "hair", "nail" or "wax".</p>
        <p class="hint">Prices are starting prices and may vary with hair length. Your stylist will confirm before starting.</p>
    <?php endif; ?>

<?php elseif ($step === 2): ?>
    <div class="bk-summary">
        <span class="bk-summary-icon"><?= icon(category_icon($service['category'] ?? '')) ?></span>
        <strong><?= e($service['name']) ?></strong>
        <span class="bk-summary-meta"><?= e(fmt_duration((int) $service['duration_minutes'])) ?> · <?= e(peso($service['price'])) ?></span>
        <a href="<?= e(url('customer/book')) ?>" class="btn btn-ghost btn-small bk-change"><?= icon('edit') ?> Change</a>
    </div>
    <?php if (!$stylists): ?>
        <div class="card center"><p class="muted">No stylist is available for this service right now.</p></div>
        <div class="bk-actions"><a href="<?= e(url('customer/book')) ?>" class="btn btn-back"><?= icon('chev-left') ?> Back</a></div>
    <?php else: ?>
        <form method="get" action="index.php" class="bk-stylists">
            <input type="hidden" name="page" value="customer/book">
            <input type="hidden" name="service" value="<?= e($service['id']) ?>">
            <h2 class="bk-h2"><?= icon('users') ?> Available Stylists</h2>
            <div class="sty-pick-grid">
                <?php foreach ($stylists as $i => $st): $r = $ratings[(int) $st['id']] ?? null; ?>
                    <label class="sty-pick">
                        <input type="radio" name="stylist" value="<?= e($st['id']) ?>" required <?= count($stylists) === 1 ? 'checked' : '' ?>>
                        <span class="sty-pick-check" aria-hidden="true"><?= icon('check') ?></span>
                        <span class="sty-pick-photo"><?= stylist_photo($st) ?></span>
                        <strong><?= e($st['full_name']) ?></strong>
                        <span class="sty-pick-spec"><?= e($st['specialty'] ?: 'Stylist') ?></span>
                        <?php if ($r): ?>
                            <span class="sty-pick-rate"><span class="star" aria-hidden="true">★</span> <b><?= number_format($r['avg'], 1) ?></b> (<?= $r['count'] ?> review<?= $r['count'] === 1 ? '' : 's' ?>)</span>
                        <?php else: ?>
                            <span class="sty-pick-rate is-new">New — no reviews yet</span>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="bk-actions">
                <a href="<?= e(url('customer/book')) ?>" class="btn btn-back"><?= icon('chev-left') ?> Back</a>
                <button type="submit" class="btn btn-primary">Continue to Date &amp; Time <?= icon('chev-right') ?></button>
            </div>
        </form>
    <?php endif; ?>

<?php elseif ($step === 3):
    $picked   = preg_match('/^\d{2}:\d{2}(:\d{2})?$/', (string) ($_GET['picked'] ?? '')) ? substr($_GET['picked'], 0, 5) : '';
    $monthKey = $date !== '' ? $date : (array_key_first($days) ?? date('Y-m-d'));
?>
    <div class="bk-summary bk-summary-wide">
        <span class="bk-summary-photo"><?= stylist_photo($stylist) ?></span>
        <div class="bk-summary-main">
            <strong><?= e($service['name']) ?></strong>
            <span>with <b><?= e($stylist['full_name']) ?></b></span>
        </div>
        <div class="bk-fact"><?= icon('calendar') ?><span><small>Preferred Date</small><b><?= $date !== '' ? e(fmt_date($date)) : 'Pick a day' ?></b></span></div>
        <div class="bk-fact"><?= icon('clock') ?><span><small>Duration</small><b><?= e(fmt_duration((int) $service['duration_minutes'])) ?></b></span></div>
        <div class="bk-fact"><?= icon('card') ?><span><small>Price</small><b><?= e(peso($service['price'])) ?></b></span></div>
        <a href="<?= e(url('customer/book', ['service' => $service['id']])) ?>" class="btn btn-ghost btn-small bk-change"><?= icon('edit') ?> Change</a>
    </div>

    <section class="card bk-when">
        <div class="bk-when-head">
            <h2 class="bk-h2"><?= icon('calendar') ?> Select a Date</h2>
            <strong class="bk-month"><?= e(date('F Y', strtotime($monthKey))) ?></strong>
            <div class="slot-legend">
                <span><i class="sl-open"></i> Available</span>
                <span><i class="sl-booked"></i> Booked</span>
                <span><i class="sl-noroom"></i> Not enough time before the next booking</span>
            </div>
        </div>
        <div class="day-scroller">
            <button type="button" class="day-arrow" data-scroll-days="-1" aria-label="Earlier days"><?= icon('chev-left') ?></button>
            <div class="day-strip" role="list" data-day-strip>
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
            <button type="button" class="day-arrow" data-scroll-days="1" aria-label="Later days"><?= icon('chev-right') ?></button>
        </div>

        <h2 class="bk-h2 bk-time-title"><?= icon('clock') ?> Select a Time<?= $date !== '' ? '<small> on ' . e(fmt_date($date)) . '</small>' : '' ?></h2>
        <?php if (!$grid): ?>
            <p class="muted">No times left on this day. Please pick another date.</p>
        <?php else: ?>
            <?php if (!$slots): ?>
                <p class="muted"><?= e($stylist['full_name']) ?> is fully booked on this day. Pick another date or stylist.</p>
            <?php endif; ?>
            <div class="slot-grid">
                <?php foreach ($grid as $s): ?>
                    <?php if ($s['state'] === 'open'): $isPicked = substr($s['time'], 0, 5) === $picked; ?>
                        <a class="slot <?= $isPicked ? 'slot-picked' : '' ?>" href="<?= e(url('customer/book', ['service' => $service['id'], 'stylist' => $stylist['id'], 'date' => $date, 'time' => $s['time']])) ?>">
                            <?php if ($isPicked): ?><?= icon('check') ?><?php endif; ?><?= e(fmt_time($s['time'])) ?>
                        </a>
                    <?php else: ?>
                        <span class="slot slot-<?= $s['state'] === 'booked' ? 'booked' : 'noroom' ?>" aria-disabled="true"
                              title="<?= $s['state'] === 'booked' ? 'Already booked' : 'Not enough time before the next booking' ?>">
                            <?= e(fmt_time($s['time'])) ?>
                            <small><?= $s['state'] === 'booked' ? 'Booked' : 'No room' ?></small>
                        </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <div class="bk-actions bk-actions-left">
        <a href="<?= e(url('customer/book', ['service' => $service['id']])) ?>" class="btn btn-back"><?= icon('chev-left') ?> Back to Stylists</a>
    </div>

<?php else:
    $mins  = (int) $service['duration_minutes'];
    $img   = category_photo($service['category'] ?? '');
    $photo = photo_exists($img) ? photo($img, $service['name']) : (stylist_photo_file($stylist) ? stylist_photo($stylist) : '');
?>
    <section class="card bk-confirm">
        <h2 class="bk-h2 bk-confirm-title"><?= icon('calendar') ?> Booking Details</h2>
        <div class="bk-confirm-body <?= $photo === '' ? 'no-photo' : '' ?>">
            <dl class="bk-details">
                <div><dt><span class="bk-ic"><?= icon(category_icon($service['category'] ?? '')) ?></span> Service</dt><dd><?= e($service['name']) ?></dd></div>
                <div><dt><span class="bk-ic"><?= icon('user') ?></span> Stylist</dt><dd><?= e($stylist['full_name']) ?></dd></div>
                <div><dt><span class="bk-ic"><?= icon('calendar') ?></span> Date</dt><dd><?= e(fmt_date($date)) ?></dd></div>
                <div><dt><span class="bk-ic"><?= icon('clock') ?></span> Time</dt><dd><?= e(fmt_time($time)) ?> – <?= e(fmt_time($endTime)) ?> <span class="bk-chip"><?= e(fmt_duration($mins)) ?></span></dd></div>
                <div class="bk-price"><dt><span class="bk-ic"><?= icon('card') ?></span> Price</dt><dd><?= e(peso($service['price'])) ?></dd></div>
            </dl>
            <?php if ($photo !== ''): ?><div class="bk-confirm-photo"><?= $photo ?></div><?php endif; ?>
        </div>

        <form method="post" action="<?= e(url('customer/book')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="service_id" value="<?= e($service['id']) ?>">
            <input type="hidden" name="stylist_id" value="<?= e($stylist['id']) ?>">
            <input type="hidden" name="date" value="<?= e($date) ?>">
            <input type="hidden" name="time" value="<?= e($time) ?>">
            <button type="submit" class="btn btn-primary btn-block bk-submit"><?= icon('check') ?> Request Booking</button>
        </form>
        <p class="hint center">The salon will confirm your request. You can cancel anytime before your appointment.</p>
        <div class="bk-actions bk-actions-center">
            <a href="<?= e(url('customer/book', ['service' => $service['id'], 'stylist' => $stylist['id'], 'date' => $date, 'picked' => $time])) ?>" class="btn btn-back"><?= icon('chev-left') ?> Choose a different time</a>
        </div>
    </section>
<?php endif; ?>
