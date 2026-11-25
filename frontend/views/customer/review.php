<?php /** @var array $appt  @var bool $editing */
$oldRating  = (int) ($_SESSION['old']['rating'] ?? ($appt['review_rating'] ?? 0));
$oldComment = isset($_SESSION['old']['comment']) ? old('comment') : e((string) ($appt['review_comment'] ?? ''));
$statusText = [
    'pending'  => ['Waiting for approval', 'badge-pending', 'The salon will check your review before it shows on our page.'],
    'approved' => ['Published', 'badge-approved', 'Your review is showing on our website. Thank you!'],
    'removed'  => ['Not published', 'badge-removed', 'The salon did not publish this review. You can edit it and send it again.'],
];
?>
<section class="card narrow-card centered-card">
    <p><a href="<?= e(url('customer/my-appointments')) ?>">← Back to my appointments</a></p>
    <h1 class="card-title"><?= $editing ? 'Your review' : 'How was your visit?' ?></h1>
    <p class="muted"><?= e($appt['service']) ?> with <?= e($appt['stylist']) ?> · <?= e(fmt_date($appt['appt_date'])) ?></p>

    <?php if ($editing): [$label, $badge, $note] = $statusText[$appt['review_status']] ?? $statusText['pending']; ?>
        <div class="review-status">
            <span class="badge <?= e($badge) ?>"><?= e($label) ?></span>
            <small class="muted">Posted <?= e(fmt_date(substr((string) $appt['review_date'], 0, 10))) ?></small>
            <p class="muted"><?= e($note) ?></p>
        </div>
    <?php endif; ?>

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
        <textarea id="comment" name="comment" rows="4" maxlength="1000" placeholder="What did you love about your visit?"><?= $oldComment ?></textarea>
        <small class="hint">Your first name and last initial are shown with your review once the salon approves it.</small>

        <?php if ($editing): ?>
            <small class="hint">Saving changes sends your review to the salon for approval again.</small>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary btn-block"><?= $editing ? 'Save changes' : 'Submit review' ?></button>
    </form>
</section>
