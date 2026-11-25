<?php /** @var array $customers  @var string $q  @var int $page  @var int $pages  @var int $total  @var int $per */ ?>
<div class="ph">
    <div>
        <h1>Customers</h1>
        <p>Manage your salon customers</p>
    </div>
    <button type="button" class="btn btn-primary" data-dialog="add-customer"><?= icon('plus') ?> Add Customer</button>
</div>

<section class="panel panel-flush">
    <form method="get" action="index.php" class="toolbar" role="search" style="padding:20px 24px 0">
        <input type="hidden" name="page" value="staff/customers">
        <div class="search-box grow" style="max-width:420px">
            <?= icon('search') ?>
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, phone, email or CUS-…" aria-label="Search customers">
        </div>
        <button type="submit" class="btn btn-ghost">Search</button>
        <?php if ($q !== ''): ?><a href="<?= e(url('staff/customers')) ?>" class="btn btn-ghost">Clear</a><?php endif; ?>
    </form>

    <?php if (!$customers): ?>
        <p class="empty">No customers found.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Customer ID</th>
                        <th scope="col">Name</th>
                        <th scope="col">Contact</th>
                        <th scope="col">Total Visits</th>
                        <th scope="col">Last Appointment</th>
                        <th scope="col" class="t-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($customers as $c): ?>
                    <tr>
                        <td><span class="ref"><?= e(cust_ref($c['id'])) ?></span></td>
                        <td>
                            <div class="cell-person">
                                <span class="avatar-i"><?= e(initials($c['full_name'])) ?></span>
                                <span>
                                    <strong><?= e($c['full_name']) ?></strong>
                                    <small><?= $c['user_id'] ? 'Online account' : 'Walk-in / phone' ?></small>
                                </span>
                            </div>
                        </td>
                        <td>
                            <?= e($c['phone'] ?: '—') ?>
                            <span class="cell-sub"><?= e($c['email'] ?: 'No email') ?></span>
                        </td>
                        <td><strong><?= (int) $c['completed'] ?></strong> <span class="cell-sub" style="display:inline"><?= (int) $c['total'] ?> booked</span></td>
                        <td><?= $c['last_appt'] ? e(fmt_date($c['last_appt'])) : '<span class="muted">—</span>' ?></td>
                        <td class="t-right">
                            <details class="dots">
                                <summary aria-label="Actions for <?= e($c['full_name']) ?>"><?= icon('more') ?></summary>
                                <div class="menu menu-right">
                                    <a href="<?= e(url('staff/customers/view', ['id' => $c['id']])) ?>"><?= icon('eye') ?> View &amp; edit</a>
                                    <a href="<?= e(url('staff/appointments/new', ['customer' => $c['id']])) ?>"><?= icon('calendar') ?> New appointment</a>
                                </div>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php
    $pgRoute = 'staff/customers'; $pgQuery = $q !== '' ? ['q' => $q] : [];
    $pgPage = $page; $pgPages = $pages; $pgTotal = $total; $pgPer = $per; $pgNoun = 'customers';
    require __DIR__ . '/../layouts/_pager.php';
    ?>
</section>

<dialog class="modal" id="add-customer" <?= has_old() ? 'data-open' : '' ?>>
    <form method="post" action="<?= e(url('staff/customers')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="modal-head">
            <h2>Add Customer</h2>
            <button type="button" class="modal-x" data-close aria-label="Close"><?= icon('x') ?></button>
        </div>
        <div class="modal-body">
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
            <div class="field">
                <label for="notes">Notes (optional)</label>
                <textarea id="notes" name="notes" maxlength="1000"><?= old('notes') ?></textarea>
            </div>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-ghost" data-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Add customer</button>
        </div>
    </form>
</dialog>
