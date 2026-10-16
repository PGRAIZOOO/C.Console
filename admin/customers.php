<?php
include('../includes/config.php');
include('../includes/functions.php');
$admin = require_admin();

if (is_post()) {
    csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);
    $target = db_row('SELECT full_name, role, is_active FROM users WHERE user_id = ?', [$id]);
    if (!$target) {
        flash('error', 'User not found.');
    } elseif ($id === $admin['id']) {
        flash('error', 'You cannot change the role or status of your own account.');
    } elseif (isset($_POST['role'])) {
        $role = (string) $_POST['role'];
        if (!in_array($role, ['Admin', 'Customer'], true)) {
            flash('error', 'Choose a valid role.');
        } elseif ((int) $target['is_active'] !== 1) {
            flash('error', 'Activate ' . $target['full_name'] . ' before changing their role.');
        } elseif ($role === $target['role']) {
            flash('info', $target['full_name'] . ' is already ' . ($role === 'Admin' ? 'an Admin' : 'a Customer') . '.');
        } else {
            q('UPDATE users SET role = ? WHERE user_id = ?', [$role, $id]);
            flash('success', $target['full_name'] . ' is now ' . ($role === 'Admin' ? 'an Admin' : 'a Customer') . '.');
        }
    } else {
        $active = ($_POST['active'] ?? '') === '1' ? 1 : 0;
        q('UPDATE users SET is_active = ? WHERE user_id = ?', [$active, $id]);
        flash('success', $target['full_name'] . ($active ? ' was activated.' : ' was deactivated.'));
    }
    redirect('admin/customers.php');
}

$search = trim($_GET['q'] ?? '');
$live = LIVE_STATUSES;
$where = ['1 = 1'];
$params = [];
if ($search !== '') {
    $where[] = '(u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
    array_push($params, '%' . $search . '%', '%' . $search . '%', '%' . $search . '%');
}

$customers = db_all(
    "SELECT u.user_id, u.username, u.email, u.full_name, u.contact_number, u.address, u.is_active, u.role, u.photo,
            (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.user_id AND o.status IN $live) AS order_count,
            (SELECT COALESCE(SUM(o.total_amount), 0) FROM orders o WHERE o.user_id = u.user_id AND o.status IN $live) AS spent
     FROM users u
     WHERE " . implode(' AND ', $where) . ' ORDER BY u.full_name',
    $params
);

$title = 'Users';
include('../includes/adminHeader.php');
?>
<div class="page-head">
    <div>
        <h1>Users</h1>
        <p><?= count($customers) ?> registered user<?= count($customers) === 1 ? '' : 's' ?></p>
    </div>
</div>

<form class="filters compact" method="get" style="grid-template-columns:2fr auto">
    <div class="field">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Name, username or email">
    </div>
    <button class="btn btn-primary" type="submit">Apply</button>
</form>

<div class="card">
    <?php if (!$customers): ?>
        <div class="empty"><h3>No users found</h3></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>User</th><th>Contact</th><th>Address</th><th>Status</th><th>Role</th><th class="num">Orders</th><th class="num">Total spent</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <?php $active = (int) $customer['is_active'] === 1; $self = (int) $customer['user_id'] === $admin['id']; ?>
                        <tr>
                            <td><div class="cell"><?= user_avatar($customer) ?><div><strong><?= e($customer['full_name']) ?></strong><small><?= e($customer['email']) ?> · @<?= e($customer['username']) ?></small></div></div></td>
                            <td><?= e($customer['contact_number'] ?: '—') ?></td>
                            <td><?= e($customer['address'] ?: '—') ?></td>
                            <td><?= $active ? '<span class="badge ok">Active</span>' : '<span class="badge bad">Inactive</span>' ?></td>
                            <td>
                                <?php if ($active && !$self): ?>
                                    <form method="post" class="role-form" data-confirm="Change the role of <?= e($customer['full_name']) ?>?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $customer['user_id'] ?>">
                                        <select name="role" aria-label="Role for <?= e($customer['full_name']) ?>">
                                            <?php foreach (['Customer', 'Admin'] as $role): ?>
                                                <option value="<?= e($role) ?>" <?= $customer['role'] === $role ? 'selected' : '' ?>><?= e($role) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-ghost btn-sm" type="submit">Update</button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge <?= $customer['role'] === 'Admin' ? 'info' : '' ?>"><?= e($customer['role']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="num"><?= (int) $customer['order_count'] ?></td>
                            <td class="num"><?= money($customer['spent']) ?></td>
                            <td class="actions-cell">
                                <?php if ($self): ?>
                                    <span class="badge">You</span>
                                <?php else: ?>
                                <form method="post" data-confirm="<?= $active ? 'Deactivate' : 'Activate' ?> <?= e($customer['full_name']) ?>?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $customer['user_id'] ?>">
                                    <input type="hidden" name="active" value="<?= $active ? '0' : '1' ?>">
                                    <button class="btn <?= $active ? 'btn-danger' : 'btn-ghost' ?> btn-sm" type="submit"><?= $active ? 'Deactivate' : 'Activate' ?></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php include('../includes/footer.php'); ?>
