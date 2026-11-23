<?php /** @var array $stylist  @var array $services  @var int[] $checked */ ?>
<section class="card narrow-card">
    <p><a href="<?= e(url('admin/stylists')) ?>">← Back to stylists</a></p>
    <h1 class="card-title">Edit stylist</h1>

    <form method="post" action="<?= e(url('admin/stylists/edit', ['id' => $stylist['id']])) ?>" class="form" novalidate>
        <?= csrf_field() ?>

        <label for="full_name">Full name</label>
        <input type="text" id="full_name" name="full_name" value="<?= old_or('full_name', $stylist['full_name']) ?>" required maxlength="100">

        <label for="specialty">Specialty (optional)</label>
        <input type="text" id="specialty" name="specialty" value="<?= old_or('specialty', $stylist['specialty'] ?? '') ?>" maxlength="100">

        <fieldset class="checks">
            <legend>Services this stylist performs</legend>
            <?php foreach ($services as $s): ?>
                <label class="check">
                    <input type="checkbox" name="services[]" value="<?= e($s['id']) ?>" <?= in_array((int) $s['id'], $checked, true) ? 'checked' : '' ?>>
                    <?= e($s['name']) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <small class="hint">Only active services are listed. Removing a service doesn't change existing appointments.</small>

        <button type="submit" class="btn btn-primary btn-block">Save changes</button>
    </form>
</section>
