<?php /** @var array $pick  @var array $services  @var array $stylists  @var array $customers */
$ready = $pick['service'] && $pick['stylist'] && !$pick['problem'] && !$pick['full'] && $pick['slots'];
// Customer chosen earlier (from a customer's page, or kept after an error).
$chosenCustomer = (int) ($_SESSION['old']['customer_id'] ?? $_GET['customer'] ?? 0);
?>
<div class="page-head">
    <p><a href="<?= e(url('staff/appointments')) ?>">← Back to appointments</a></p>
    <h1 class="card-title">New appointment</h1>
    <p class="muted">For walk-in and phone customers. Saved as confirmed.</p>
</div>

<div class="stack">
    <section class="card">
        <h2 class="section-title">1. Service, stylist and date</h2>
        <form method="get" action="index.php" class="form form-grid">
            <input type="hidden" name="page" value="staff/appointments/new">
            <?php if ($chosenCustomer): ?><input type="hidden" name="customer" value="<?= e($chosenCustomer) ?>"><?php endif; ?>

            <div class="field">
                <label for="service">Service</label>
                <select id="service" name="service" required data-filters="stylist">
                    <option value="">Choose a service</option>
                    <?= service_options($services, (int) $pick['service_id'], true) ?>
                </select>
            </div>
            <div class="field">
                <label for="stylist">Stylist</label>
                <select id="stylist" name="stylist" required>
                    <option value="">Choose a stylist</option>
                    <?php foreach ($stylists as $st): ?>
                        <option value="<?= e($st['id']) ?>" data-services="<?= e(implode(',', $st['service_ids'])) ?>"
                            <?= $pick['stylist_id'] === (int) $st['id'] ? 'selected' : '' ?>><?= e($st['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="date">Date</label>
                <input type="date" id="date" name="date" value="<?= e($pick['date']) ?>" min="<?= e(date('Y-m-d')) ?>" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-ghost btn-small">Show available times</button>
            </div>
        </form>
    </section>

    <?php if ($pick['service'] || $pick['problem']): ?>
    <section class="card">
        <h2 class="section-title">2. Time and customer</h2>
        <form method="post" action="<?= e(url('staff/appointments/new')) ?>" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="service_id" value="<?= e($pick['service_id']) ?>">
            <input type="hidden" name="stylist_id" value="<?= e($pick['stylist_id']) ?>">
            <input type="hidden" name="date" value="<?= e($pick['date']) ?>">

            <?php $selectedTime = $_SESSION['old']['time'] ?? ''; require __DIR__ . '/_slot-picker.php'; ?>

            <?php if ($ready): ?>
                <fieldset class="checks">
                    <legend>How did they book?</legend>
                    <div class="check-list">
                        <label class="check"><input type="radio" name="source" value="walk_in" <?= ($_SESSION['old']['source'] ?? 'walk_in') === 'walk_in' ? 'checked' : '' ?>> Walk-in</label>
                        <label class="check"><input type="radio" name="source" value="phone" <?= ($_SESSION['old']['source'] ?? '') === 'phone' ? 'checked' : '' ?>> Phone call</label>
                    </div>
                </fieldset>

                <div class="form-grid">
                    <div class="field">
                        <label for="customer_id">Existing customer</label>
                        <select id="customer_id" name="customer_id">
                            <option value="0">— New customer —</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= e($c['id']) ?>" <?= $chosenCustomer === (int) $c['id'] ? 'selected' : '' ?>>
                                    <?= e($c['full_name']) ?><?= $c['phone'] ? ' · ' . e($c['phone']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <p class="hint">Or, for a new customer, fill in:</p>
                <div class="form-grid" id="new-customer">
                    <div class="field">
                        <label for="new_name">Full name</label>
                        <input type="text" id="new_name" name="new_name" value="<?= old('new_name') ?>" maxlength="100">
                    </div>
                    <div class="field">
                        <label for="new_phone">Phone</label>
                        <input type="tel" id="new_phone" name="new_phone" value="<?= old('new_phone') ?>" maxlength="20">
                    </div>
                    <div class="field">
                        <label for="new_email">Email (optional)</label>
                        <input type="email" id="new_email" name="new_email" value="<?= old('new_email') ?>" maxlength="150">
                        <small class="hint">Needed for confirmation and reminder emails.</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Book appointment</button>
                </div>
            <?php endif; ?>
        </form>
    </section>
    <?php endif; ?>
</div>
