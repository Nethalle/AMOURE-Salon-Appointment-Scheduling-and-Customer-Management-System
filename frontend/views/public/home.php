<?php
/** @var array $services */ /** @var array $stylists */ /** @var array $reviews */
/** @var int $reviewCount */ /** @var ?float $avgRating */ /** @var array $stats */ /** @var ?array $user */
$bookUrl = url($user && $user['role'] === 'customer' ? 'customer/book' : 'register');
$gallery = [
    ['gallery-1.jpg', 'Hair styling'],
    ['gallery-2.jpg', 'Facial treatment'],
    ['gallery-3.jpg', 'Nail art'],
    ['gallery-4.jpg', 'Makeup look'],
    ['gallery-5.jpg', 'Bridal hair'],
    ['gallery-6.jpg', 'Spa corner'],
];
?>

<!-- ============ HERO ============ -->
<section class="hero-split">
    <div class="hero-copy">
        <p class="eyebrow eyebrow-rose">Beauty &nbsp;•&nbsp; Self care &nbsp;•&nbsp; Confidence</p>
        <h1 class="display">Look Good<br><span class="display-rose">Feel Amazing</span></h1>
        <p class="script hero-script">Every Day</p>
        <p class="lead">Professional beauty services designed to bring out your natural beauty. Choose your service, stylist and time online, and we'll take care of the rest.</p>
        <div class="hero-actions">
            <a href="<?= e($bookUrl) ?>" class="btn btn-primary"><?= icon('calendar', 'icon icon-sm') ?> Book appointment</a>
            <a href="#services" class="btn btn-ghost">Explore services</a>
        </div>
        <ul class="hero-features">
            <li><?= icon('leaf') ?><span>Expert<br>stylists</span></li>
            <li><?= icon('gem') ?><span>Premium<br>products</span></li>
            <li><?= icon('flower') ?><span>Relaxing<br>atmosphere</span></li>
            <li><?= icon('heart') ?><span>Personalized<br>care</span></li>
        </ul>
    </div>
    <div class="hero-media">
        <?= photo('hero.jpg', 'Hero photo of a client', 'hero-photo') ?>
        <p class="badge-circle script" aria-hidden="true">Beauty<br>starts<br>with you</p>
    </div>
</section>

<!-- ============ SERVICES ============ -->
<section class="home-section" id="services" aria-labelledby="services-title">
    <div class="section-head">
        <div>
            <p class="eyebrow eyebrow-rose">Our services</p>
            <h2 id="services-title" class="section-heading">Beauty Services for a Better You</h2>
        </div>
        <p class="muted section-intro">From hair to skin and nails, every service is done by our trained stylists.</p>
    </div>

    <?php if (!$services): ?>
        <p class="muted">Services will appear here once the salon adds them.</p>
    <?php else: ?>
        <div class="service-grid">
            <?php foreach ($services as $s): ?>
                <article class="service-card">
                    <?= photo(category_photo($s['name']), $s['name'], 'service-photo') ?>
                    <div class="service-body">
                        <span class="service-icon"><?= icon(category_icon($s['name'])) ?></span>
                        <h3><?= e($s['name']) ?></h3>
                        <p class="muted"><?= (int) $s['n'] ?> service<?= (int) $s['n'] === 1 ? '' : 's' ?> · from <?= e(peso($s['from_price'])) ?></p>
                        <a href="<?= e($user && $user['role'] === 'customer' ? url('customer/book') . '#cat-' . slugify($s['name']) : url('register')) ?>" class="link-arrow">Book now <?= icon('arrow', 'icon icon-xs') ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- ============ ABOUT ============ -->
<section class="about-band" id="about" aria-labelledby="about-title">
    <div class="about-media">
        <?= photo('salon-interior.jpg', 'Photo of the salon interior', 'about-photo') ?>
    </div>
    <div class="about-copy">
        <p class="eyebrow eyebrow-rose">About us</p>
        <h2 id="about-title" class="section-heading">More Than a Beauty Salon</h2>
        <p>At AMOURÉ SALON, beauty is a form of self-care. Our team gives every client high-quality service in a warm, relaxing and friendly space, and our online booking means no more waiting or double-booked stylists.</p>
        <ul class="about-points">
            <li><?= icon('gem') ?> Experienced stylists</li>
            <li><?= icon('leaf') ?> Quality products</li>
            <li><?= icon('heart') ?> A relaxing space</li>
        </ul>
        <a href="<?= e($bookUrl) ?>" class="btn btn-primary">Book your visit <?= icon('arrow', 'icon icon-sm') ?></a>
    </div>
    <ul class="about-stats">
        <li><?= icon('sparkle') ?><strong><?= e($stats['services']) ?></strong><span>Beauty services</span></li>
        <li><?= icon('users') ?><strong><?= e($stats['stylists']) ?></strong><span>Expert stylists</span></li>
        <li><?= icon('heart') ?><strong><?= e($stats['completed']) ?></strong><span>Appointments completed</span></li>
        <li><?= icon('star') ?><strong><?= $stats['rating'] !== null ? e(number_format($stats['rating'], 1)) : '—' ?></strong><span>Average rating</span></li>
    </ul>
</section>

<!-- ============ OFFER / CTA ============ -->
<section class="offer-band" aria-labelledby="offer-title">
    <div class="offer-media"><?= photo('offer.jpg', 'Photo for the booking banner', 'offer-photo') ?></div>
    <div class="offer-copy">
        <p class="eyebrow eyebrow-rose">Book online</p>
        <h2 id="offer-title" class="section-heading">Your Next Visit Is a Few Clicks Away</h2>
        <p>Pick your service, stylist and time. We'll email you a confirmation and a reminder before your appointment.</p>
        <a href="<?= e($bookUrl) ?>" class="btn btn-primary">Book now <?= icon('arrow', 'icon icon-sm') ?></a>
    </div>
    <p class="badge-circle badge-circle-light script" aria-hidden="true">Pamper<br>yourself<br>today</p>
</section>

<!-- ============ GALLERY ============ -->
<section class="home-section" id="gallery" aria-labelledby="gallery-title">
    <div class="section-head">
        <div>
            <p class="eyebrow eyebrow-rose">Our gallery</p>
            <h2 id="gallery-title" class="section-heading">Real Beauty, Real Results</h2>
        </div>
    </div>
    <div class="gallery-grid">
        <?php foreach ($gallery as [$file, $alt]): ?>
            <?= photo($file, $alt, 'gallery-photo') ?>
        <?php endforeach; ?>
    </div>
</section>

<!-- ============ STYLISTS ============ -->
<?php if ($stylists): ?>
<section class="home-section" aria-labelledby="team-title">
    <div class="section-head">
        <div>
            <p class="eyebrow eyebrow-rose">Our team</p>
            <h2 id="team-title" class="section-heading">Meet Our Stylists</h2>
        </div>
    </div>
    <div class="team-grid">
        <?php foreach ($stylists as $st): ?>
            <figure class="team-card">
                <?= stylist_photo($st, 'team-photo') ?>
                <figcaption>
                    <strong><?= e($st['full_name']) ?></strong>
                    <span class="muted"><?= e($st['specialty'] ?? 'Stylist') ?></span>
                </figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ============ REVIEWS ============ -->
<section class="home-section" id="reviews" aria-labelledby="reviews-title">
    <div class="section-head">
        <div>
            <p class="eyebrow eyebrow-rose">Client love</p>
            <h2 id="reviews-title" class="section-heading">What Our Clients Say</h2>
        </div>
        <?php if ($reviewCount): ?>
            <p class="muted section-intro">
                <span class="stars" aria-hidden="true"><?= e(stars((int) round($avgRating))) ?></span>
                <?= e(number_format($avgRating, 1)) ?> average from <?= e($reviewCount) ?> review<?= $reviewCount === 1 ? '' : 's' ?>
            </p>
        <?php endif; ?>
    </div>

    <?php if (!$reviews): ?>
        <p class="muted">Reviews from our clients will appear here.</p>
    <?php else: ?>
        <div class="vouch-wall">
            <?php foreach ($reviews as $i => $r): ?>
                <figure class="vouch2 <?= $r['comment'] === null ? 'is-short' : '' ?> <?= $i === 0 && $r['comment'] !== null ? 'is-featured' : '' ?>">
                    <div class="vouch2-top">
                        <span class="vouch2-stars" aria-label="<?= e($r['rating']) ?> out of 5 stars"><?= e(stars((int) $r['rating'])) ?></span>
                        <?php if ($r['visits'] >= 2): ?>
                            <span class="vouch2-badge" title="Completed visits at the salon">Returning client · <?= $r['visits'] ?> visits</span>
                        <?php else: ?>
                            <span class="vouch2-badge is-verified">Verified client</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($r['comment'] !== null): ?>
                        <blockquote><?= e($r['comment']) ?></blockquote>
                    <?php else: ?>
                        <p class="vouch2-short">Rated <?= (int) $r['rating'] ?> out of 5 for <?= e($r['service']) ?>.</p>
                    <?php endif; ?>
                    <figcaption>
                        <span class="vouch2-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($r['name'], 0, 1))) ?></span>
                        <span class="vouch2-who">
                            <strong><?= e($r['name']) ?></strong>
                            <span><?= e($r['service']) ?> with <?= e($r['stylist']) ?><?= !empty($r['created_at']) ? ' · ' . e(date('M Y', strtotime($r['created_at']))) : '' ?></span>
                        </span>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- ============ CTA ============ -->
<section class="cta-band">
    <div>
        <h2 class="cta-title">Ready to Look &amp; Feel Your Best?</h2>
        <p>Book your appointment today and let our stylists pamper you.</p>
    </div>
    <a href="<?= e($bookUrl) ?>" class="btn btn-gold"><?= icon('calendar', 'icon icon-sm') ?> Book your appointment</a>
</section>
