<?php /** @var array $users */ /** @var string $role */ /** @var array $me */
$roleLabels = ['admin' => 'Admin', 'staff' => 'Staff', 'customer' => 'Customer'];
$roleBadge  = ['admin' => 'badge-role-admin', 'staff' => 'badge-role-staff', 'customer' => 'badge-role-customer'];
?>
<div class="page-head">
    <h1 class="card-title">User accounts</h1>
    <p class="muted">Staff and admin logins, and customer accounts.</p>
</div>

<div class="stack">
    <section class="card">
        <h2 class="section-title">Add a staff or admin account</h2>
        <form method="post" action="<?= e(url('admin/users')) ?>" class="form form-grid" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="<?= old('name') ?>" required maxlength="100" autocomplete="off">
            </div>
            <div class="field">
                <label for="email">Email (login)</label>
                <input type="email" id="email" name="email" value="<?= old('email') ?>" required maxlength="150" autocomplete="off">
            </div>
            <div class="field">
                <label for="role">Role</label>
                <select id="role" name="role">
                    <option value="staff" <?= ($_SESSION['old']['role'] ?? 'staff') === 'staff' ? 'selected' : '' ?>>Staff (receptionist)</option>
                    <option value="admin" <?= ($_SESSION['old']['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin (owner)</option>
                </select>
            </div>
            <div class="field">
                <label for="password">Starting password</label>
                <input type="text" id="password" name="password" required minlength="8" autocomplete="new-password">
                <small class="hint">At least 8 characters. They can change it after logging in.</small>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create account</button>
            </div>
        </form>
    </section>

    <section class="card">
        <nav class="tabs" aria-label="Filter by role">
            <?php foreach (['' => 'All', 'admin' => 'Admins', 'staff' => 'Staff', 'customer' => 'Customers'] as $key => $label): ?>
                <a href="<?= e(url('admin/users', $key !== '' ? ['role' => $key] : [])) ?>" class="tab" <?= $role === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>

        <?php if (!$users): ?>
            <p class="muted">No accounts here.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Role</th>
                            <th scope="col">Status</th>
                            <th scope="col">Created</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $u): $isMe = (int) $u['id'] === (int) $me['id']; ?>
                        <tr class="<?= (int) $u['is_active'] ? '' : 'row-inactive' ?>">
                            <td><?= e($u['name']) ?><?= $isMe ? ' <span class="muted">(you)</span>' : '' ?></td>
                            <td><?= e($u['email']) ?></td>
                            <td><span class="badge <?= e($roleBadge[$u['role']]) ?>"><?= e($roleLabels[$u['role']]) ?></span></td>
                            <td>
                                <span class="badge <?= (int) $u['is_active'] ? 'badge-completed' : 'badge-cancelled' ?>">
                                    <?= (int) $u['is_active'] ? 'Active' : 'Deactivated' ?>
                                </span>
                            </td>
                            <td><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
                            <td>
                                <div class="actions">
                                    <a href="<?= e(url('admin/users/edit', ['id' => $u['id']])) ?>" class="btn btn-small btn-ghost">Edit</a>
                                    <?php if (!$isMe): ?>
                                        <form method="post" action="<?= e(url('admin/users/toggle')) ?>" class="inline-form"
                                              <?= (int) $u['is_active'] ? 'data-confirm="Deactivate ' . e($u['name']) . '? They will be logged out and can\'t log in."' : '' ?>>
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= e($u['id']) ?>">
                                            <button type="submit" class="btn btn-small btn-ghost"><?= (int) $u['is_active'] ? 'Deactivate' : 'Reactivate' ?></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
