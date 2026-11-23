<?php /** @var array $appt */
$oldRating = (int) ($_SESSION['old']['rating'] ?? 0);
?>
<section class="card narrow-card centered-card">
    <p><a href="<?= e(url('customer/my-appointments')) ?>">← Back to my appointments</a></p>
    <h1 class="card-title">How was your visit?</h1>
    <p class="muted"><?= e($appt['service']) ?> with <?= e($appt['stylist']) ?> · <?= e(fmt_date($appt['appt_date'])) ?></p>

    <form method="post" action="<?= e(url('customer/review', ['id' => $appt['id']])) ?>" class="form" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="appointment_id" value="<?= e($appt['id']) ?>">

        <fieldset class="rating">
            <legend>Your rating</legend>
            <div class="rating-stars">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" <?= $oldRating === $i ? 'checked' : '' ?> required>
                    <label for="star<?= $i ?>" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">
                        <span aria-hidden="true">★</span><span class="sr-only"><?= $i ?> star<?= $i > 1 ? 's' : '' ?></span>
                    </label>
                <?php endfor; ?>
            </div>
        </fieldset>

        <label for="comment">Your review (optional)</label>
        <textarea id="comment" name="comment" rows="4" maxlength="1000" placeholder="What did you love about your visit?"><?= old('comment') ?></textarea>
        <small class="hint">Your first name and last initial are shown with your review once the salon approves it.</small>

        <button type="submit" class="btn btn-primary btn-block">Submit review</button>
    </form>
</section>
