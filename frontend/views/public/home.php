<section class="hero">
    <div class="hero-copy">
        <p class="eyebrow">Premium salon experience</p>
        <h1 class="display">Look and feel<br><em>your most beautiful</em></h1>
        <p class="lead">Book your next appointment online. Choose your service, stylist and time, and we'll email you a confirmation and a reminder.</p>
        <div class="hero-actions">
            <?php if ($user && $user['role'] === 'customer'): ?>
                <a href="<?= e(url('customer/book')) ?>" class="btn btn-primary">Book appointment</a>
                <a href="<?= e(url('customer/my-appointments')) ?>" class="btn btn-ghost">My appointments</a>
            <?php else: ?>
                <a href="<?= e(url('register')) ?>" class="btn btn-primary">Book appointment</a>
                <a href="<?= e(url('login')) ?>" class="btn btn-ghost">Log in</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ($reviews): ?>
<section class="vouches" aria-labelledby="vouches-title">
    <div class="vouches-head">
        <h2 id="vouches-title" class="cta-title">Our happy clients</h2>
        <p class="muted">
            <span class="stars" aria-hidden="true"><?= e(stars((int) round($avgRating))) ?></span>
            <?= e(number_format($avgRating, 1)) ?> average from <?= e($reviewCount) ?> review<?= $reviewCount === 1 ? '' : 's' ?>
        </p>
    </div>
    <div class="vouch-grid">
        <?php foreach ($reviews as $r): ?>
            <figure class="vouch">
                <span class="stars" aria-label="<?= e($r['rating']) ?> out of 5 stars"><?= e(stars((int) $r['rating'])) ?></span>
                <?php if ($r['comment'] !== null): ?>
                    <blockquote>“<?= e($r['comment']) ?>”</blockquote>
                <?php endif; ?>
                <figcaption>— <?= e($r['name']) ?> · <?= e($r['service']) ?></figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="cta-band">
    <div>
        <h2 class="cta-title">Ready to look &amp; feel your best?</h2>
        <p>Create an account and book in a few minutes.</p>
    </div>
    <a href="<?= e(url($user && $user['role'] === 'customer' ? 'customer/book' : 'register')) ?>" class="btn btn-gold">Book your appointment</a>
</section>
