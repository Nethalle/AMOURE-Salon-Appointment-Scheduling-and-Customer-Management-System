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

<section class="cta-band">
    <div>
        <h2 class="cta-title">Ready to look &amp; feel your best?</h2>
        <p>Create an account and book in a few minutes.</p>
    </div>
    <a href="<?= e(url($user && $user['role'] === 'customer' ? 'customer/book' : 'register')) ?>" class="btn btn-gold">Book your appointment</a>
</section>
