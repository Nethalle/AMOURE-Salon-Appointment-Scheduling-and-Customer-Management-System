<?php /** @var array $customers  @var string $q */ ?>
<div class="page-head">
    <h1 class="card-title">Customers</h1>
    <p class="muted">Customer details and appointment history.</p>
</div>

<div class="stack">
    <section class="card">
        <h2 class="section-title">Add a customer</h2>
        <form method="post" action="<?= e(url('staff/customers')) ?>" class="form form-grid" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label for="full_name">Full name</label>
                <input type="text" id="full_name" name="full_name" value="<?= old('full_name') ?>" required maxlength="100">
            </div>
            <div class="field">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" value="<?= old('phone') ?>" maxlength="20">
            </div>
            <div class="field">
                <label for="email">Email (optional)</label>
                <input type="email" id="email" name="email" value="<?= old('email') ?>" maxlength="150">
                <small class="hint">Needed for confirmation and reminder emails.</small>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add customer</button>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="head-row">
            <h2 class="section-title"><?= count($customers) ?> customer<?= count($customers) === 1 ? '' : 's' ?><?= $q !== '' ? ' matching "' . e($q) . '"' : '' ?></h2>
            <form method="get" action="index.php" class="search-form" role="search">
                <input type="hidden" name="page" value="staff/customers">
                <label for="q" class="sr-only">Search customers</label>
                <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Name, phone or email">
                <button type="submit" class="btn btn-ghost btn-small">Search</button>
                <?php if ($q !== ''): ?><a href="<?= e(url('staff/customers')) ?>" class="btn btn-ghost btn-small">Clear</a><?php endif; ?>
            </form>
        </div>

        <?php if (!$customers): ?>
            <p class="muted">No customers found.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Phone</th>
                            <th scope="col">Email</th>
                            <th scope="col">Appointments</th>
                            <th scope="col">Last visit</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($customers as $c): ?>
                        <tr>
                            <td>
                                <?= e($c['full_name']) ?>
                                <?php if ($c['user_id']): ?><span class="badge badge-confirmed badge-inline">Account</span><?php endif; ?>
                            </td>
                            <td><?= e($c['phone'] ?? '—') ?></td>
                            <td><?= e($c['email'] ?? '—') ?></td>
                            <td><?= e($c['total']) ?> <span class="muted">(<?= e((int) $c['completed']) ?> completed)</span></td>
                            <td><?= $c['last_visit'] ? e(fmt_date($c['last_visit'])) : '<span class="muted">—</span>' ?></td>
                            <td><a href="<?= e(url('staff/customers/view', ['id' => $c['id']])) ?>" class="btn btn-small btn-ghost">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
