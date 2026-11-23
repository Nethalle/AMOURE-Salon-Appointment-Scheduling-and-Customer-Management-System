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
        <legend>Available times on <?= e(fmt_date($pick['date'])) ?> with <?= e($pick['stylist']['full_name']) ?></legend>
        <?php if ($pick['full']): ?>
            <p class="muted">The salon is fully booked on this date (daily limit reached). Choose another date.</p>
        <?php elseif (!$pick['slots']): ?>
            <p class="muted">No free times on this date. Choose another date or stylist.</p>
        <?php else: ?>
            <div class="slot-grid">
                <?php foreach ($pick['slots'] as $slot): ?>
                    <label class="slot slot-radio">
                        <input type="radio" name="time" value="<?= e($slot) ?>" required <?= ($selectedTime ?? '') === $slot ? 'checked' : '' ?>>
                        <span><?= e(fmt_time($slot)) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </fieldset>
<?php endif; ?>
