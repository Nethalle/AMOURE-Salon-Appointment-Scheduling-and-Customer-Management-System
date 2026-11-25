<?php
/**
 * Shared "pick a time" section for New appointment and Reschedule.
 * @var array $pick  ['service','stylist','date','slots','full','problem']
 * @var string $selectedTime
 */
?>
<?php if ($pick['problem']): ?>
    <div class="alert alert-error" role="alert"><?= e($pick['problem']) ?></div>
<?php elseif ($pick['service'] && $pick['stylist']): ?>
    <fieldset class="checks">
        <legend>Times on <?= e(fmt_date($pick['date'])) ?> with <?= e($pick['stylist']['full_name']) ?></legend>
        <?php if ($pick['full']): ?>
            <p class="muted">The salon is fully booked on this date (daily limit reached). Choose another date.</p>
        <?php elseif (!$pick['grid']): ?>
            <p class="muted">No times left on this date. Choose another date.</p>
        <?php else: ?>
            <?php if (!$pick['slots']): ?>
                <p class="muted"><?= e($pick['stylist']['full_name']) ?> is fully booked on this date. Choose another date or stylist.</p>
            <?php endif; ?>
            <div class="slot-legend">
                <span><i class="sl-open"></i> Available</span>
                <span><i class="sl-booked"></i> Booked</span>
                <span><i class="sl-noroom"></i> Not enough time before the next booking</span>
            </div>
            <div class="slot-grid">
                <?php foreach ($pick['grid'] as $s): ?>
                    <?php if ($s['state'] === 'open'): ?>
                        <label class="slot slot-radio">
                            <input type="radio" name="time" value="<?= e($s['time']) ?>" required <?= ($selectedTime ?? '') === $s['time'] ? 'checked' : '' ?>>
                            <span><?= e(fmt_time($s['time'])) ?></span>
                        </label>
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
    </fieldset>
<?php endif; ?>
