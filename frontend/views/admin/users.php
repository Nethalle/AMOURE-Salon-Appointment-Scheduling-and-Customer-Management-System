<?php /** @var array $users  @var string $role  @var array $me  @var array $roleCounts */
$roleLabels = ['admin' => 'Admin', 'staff' => 'Staff', 'customer' => 'Customer'];
$roleBadge  = ['admin' => 'badge-role-admin', 'staff' => 'badge-role-staff', 'customer' => 'badge-role-customer'];
$tabs = ['' => ['All', array_sum($roleCounts)], 'admin' => ['Admins', $roleCounts['admin']], 'staff' => ['Staff', $roleCounts['staff']], 'customer' => ['Customers', $roleCounts['customer']]];
?>
<div class="ph">
    <div>
        <h1>User Accounts</h1>
        <p>Manage system users and their roles</p>
    </div>
    <button type="button" class="btn btn-primary" data-dialog="add-user"><?= icon('plus') ?> Add User</button>
</div>

<section class="panel panel-flush">
    <nav class="tabbar" aria-label="Filter by role" style="padding:0 16px;margin:0">
        <?php foreach ($tabs as $key => [$label, $n]): ?>
            <a href="<?= e(url('admin/users', $key !== '' ? ['role' => $key] : [])) ?>" <?= $role === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?> <span class="count"><?= (int) $n ?></span></a>
        <?php endforeach; ?>
    </nav>

    <?php if (!$users): ?>
        <p class="empty">No accounts here.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">User ID</th>
                        <th scope="col">Name</th>
                        <th scope="col">Role</th>
                        <th scope="col">Email</th>
                        <th scope="col">Status</th>
                        <th scope="col">Joined</th>
                        <th scope="col" class="t-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): $isMe = (int) $u['id'] === (int) $me['id']; $on = (int) $u['is_active']; ?>
                    <tr class="<?= $on ? '' : 'row-inactive' ?>">
                        <td><span class="ref"><?= e(user_ref($u['id'])) ?></span></td>
                        <td>
                            <div class="cell-person">
                                <span class="avatar-i"><?= e(initials($u['name'])) ?></span>
                                <strong><?= e($u['name']) ?><?= $isMe ? ' <span class="muted" style="font-weight:400">(you)</span>' : '' ?></strong>
                            </div>
                        </td>
                        <td><span class="badge <?= e($roleBadge[$u['role']]) ?>"><?= e($roleLabels[$u['role']]) ?></span></td>
                        <td><?= e($u['email']) ?></td>
                        <td><span class="badge <?= $on ? 'badge-active' : 'badge-inactive' ?>"><?= $on ? 'Active' : 'Inactive' ?></span></td>
                        <td><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
                        <td class="t-right">
                            <details class="dots">
                                <summary aria-label="Actions for <?= e($u['name']) ?>"><?= icon('more') ?></summary>
                                <div class="menu menu-right">
                                    <a href="<?= e(url('admin/users/edit', ['id' => $u['id']])) ?>"><?= icon('edit') ?> Edit / change role</a>
                                    <?php if (!$isMe): ?>
                                        <form method="post" action="<?= e(url('admin/users/toggle')) ?>"
                                              <?= $on ? 'data-confirm="Deactivate ' . e($u['name']) . '? They will be logged out and can\'t log in."' : '' ?>>
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= e($u['id']) ?>">
                                            <button type="submit" class="<?= $on ? 'danger' : '' ?>"><?= icon('power') ?> <?= $on ? 'Deactivate' : 'Reactivate' ?></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<dialog class="modal" id="add-user" <?= has_old() ? 'data-open' : '' ?>>
    <form method="post" action="<?= e(url('admin/users')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="modal-head">
            <h2>Add User</h2>
            <button type="button" class="modal-x" data-close aria-label="Close"><?= icon('x') ?></button>
        </div>
        <div class="modal-body">
            <div class="field">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="<?= old('name') ?>" required maxlength="100" autocomplete="off">
            </div>
            <div class="field">
                <label for="email">Email (login)</label>
                <input type="email" id="email" name="email" value="<?= old('email') ?>" required maxlength="150" autocomplete="off">
            </div>
            <div class="f-grid">
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
                </div>
            </div>
            <small class="hint">At least 8 characters. Customers create their own accounts; change anyone's role with Edit.</small>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-ghost" data-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Create account</button>
        </div>
    </form>
</dialog>
