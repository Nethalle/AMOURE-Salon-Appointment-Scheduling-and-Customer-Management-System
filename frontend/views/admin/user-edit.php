<?php /** @var array $account */ /** @var bool $isMe */
$currentRole = $_SESSION['old']['role'] ?? $account['role'];
?>
<div class="page-head">
    <p><a href="<?= e(url('admin/users')) ?>">← Back to user accounts</a></p>
    <h1 class="card-title">Edit account</h1>
    <p class="muted"><?= e($account['name']) ?> · <?= (int) $account['is_active'] ? 'Active' : 'Deactivated' ?> · created <?= e(date('M j, Y', strtotime($account['created_at']))) ?></p>
</div>

<div class="stack narrow-stack">
    <section class="card">
        <h2 class="section-title">Details</h2>
        <form method="post" action="<?= e(url('admin/users/edit', ['id' => $account['id']])) ?>" class="form" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="details">

            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="<?= old_or('name', $account['name']) ?>" required maxlength="100">

            <label for="email">Email (login)</label>
            <input type="email" id="email" name="email" value="<?= old_or('email', $account['email']) ?>" required maxlength="150">

            <label for="role">Role</label>
            <select id="role" name="role" <?= $isMe ? 'disabled' : '' ?> data-original="<?= e($account['role']) ?>">
                <option value="customer" <?= $currentRole === 'customer' ? 'selected' : '' ?>>Customer (books appointments)</option>
                <option value="staff" <?= $currentRole === 'staff' ? 'selected' : '' ?>>Staff (receptionist)</option>
                <option value="admin" <?= $currentRole === 'admin' ? 'selected' : '' ?>>Admin (owner)</option>
            </select>
            <?php if ($isMe): ?>
                <input type="hidden" name="role" value="<?= e($account['role']) ?>">
                <small class="hint">You can't change your own role.</small>
            <?php else: ?>
                <small class="hint">
                    The change applies on their next click. Changing to Customer gives them a customer profile for booking;
                    their appointment history is kept if they switch roles.
                </small>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save details</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2 class="section-title">Reset password</h2>
        <?php if ($isMe): ?>
            <p class="muted">To change your own password, use <a href="<?= e(url('account/password')) ?>">Change password</a>.</p>
        <?php else: ?>
            <p class="muted">Use this if they forgot their password. Share the new one privately.</p>
            <form method="post" action="<?= e(url('admin/users/edit', ['id' => $account['id']])) ?>" class="form" novalidate
                  data-confirm="Reset <?= e($account['name']) ?>'s password? Their old password will stop working.">
                <?= csrf_field() ?>
                <input type="hidden" name="form" value="password">
                <label for="new_password">New password</label>
                <input type="text" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
                <small class="hint">At least 8 characters.</small>
                <div class="form-actions">
                    <button type="submit" class="btn btn-ghost">Reset password</button>
                </div>
            </form>
        <?php endif; ?>
    </section>
</div>
