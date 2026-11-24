<?php /** @var array $service */ ?>
<section class="card narrow-card">
    <p><a href="<?= e(url('admin/services', isset(SERVICE_CATEGORIES[$service['category'] ?? '']) ? ['category' => $service['category']] : [])) ?>">← Back to services</a></p>
    <h1 class="card-title">Edit service</h1>

    <form method="post" action="<?= e(url('admin/services/edit', ['id' => $service['id']])) ?>" class="form" novalidate>
        <?= csrf_field() ?>

        <label for="name">Service name</label>
        <input type="text" id="name" name="name" value="<?= old_or('name', $service['name']) ?>" required maxlength="100">

        <label for="category">Category</label>
        <select id="category" name="category" required><?= category_options($_SESSION['old']['category'] ?? ($service['category'] ?? '')) ?></select>

        <label for="price">Price (₱)</label>
        <input type="number" id="price" name="price" value="<?= old_or('price', $service['price']) ?>" required min="0" max="999999" step="0.01">

        <label for="duration_minutes">Duration (minutes)</label>
        <input type="number" id="duration_minutes" name="duration_minutes" value="<?= old_or('duration_minutes', $service['duration_minutes']) ?>" required min="5" max="600" step="5">
        <small class="hint">Changing the duration only affects new bookings. Existing appointments keep their times.</small>

        <button type="submit" class="btn btn-primary btn-block">Save changes</button>
    </form>
</section>
