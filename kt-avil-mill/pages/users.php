<?php
// pages/users.php
// Admin: list users, add user, activate/deactivate.

$pageTitle = 'Users';
$activePage = 'users';
require_admin();
require __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { flash('Invalid session token.', 'error'); }
    else {
        $action = post('action');
        if ($action === 'add') {
            $u = post('username');
            $p = post('password');
            $f = post('full_name');
            $r = post('role');
            if (!in_array($r, ['admin','staff'], true)) $r = 'staff';
            $exists = db()->prepare("SELECT 1 FROM users WHERE username=?");
            $exists->execute([$u]);
            if ($exists->fetch()) {
                flash('Username already exists.', 'error');
            } else {
                $st = db()->prepare("INSERT INTO users (username,password_hash,full_name,role) VALUES (?,?,?,?)");
                $st->execute([$u, password_hash($p, PASSWORD_DEFAULT), $f, $r]);
                flash('User created.');
            }
        } elseif ($action === 'toggle') {
            $id = to_int(post('id'));
            $st = db()->prepare("UPDATE users SET is_active = 1 - is_active WHERE id = ? AND id <> ?");
            $st->execute([$id, (int)current_user()['id']]);
            flash('User status updated.');
        } elseif ($action === 'reset') {
            $id = to_int(post('id'));
            $p = post('password');
            if (strlen($p) < 4) { flash('Password too short.', 'error'); }
            else {
                $st = db()->prepare("UPDATE users SET password_hash=? WHERE id=?");
                $st->execute([password_hash($p, PASSWORD_DEFAULT), $id]);
                flash('Password reset.');
            }
        }
    }
    header('Location: index.php?page=users'); exit;
}

$users = db()->query("SELECT id, username, full_name, role, is_active, created_at FROM users ORDER BY id")->fetchAll();
?>

<div class="grid two">
  <section class="panel">
    <h2>Add user</h2>
    <form method="post" action="index.php?page=users" class="form">
      <input type="hidden" name="action" value="add">
      <?= csrf_field() ?>
      <label>Full name <input type="text" name="full_name" required></label>
      <label>Username <input type="text" name="username" required></label>
      <label>Password <input type="password" name="password" required></label>
      <label>Role
        <select name="role"><option value="staff">staff</option><option value="admin">admin</option></select>
      </label>
      <button class="btn primary" type="submit">Add user</button>
    </form>
  </section>

  <section class="panel">
    <h2>Users</h2>
    <table class="table">
      <thead><tr><th>ID</th><th>Username</th><th>Name</th><th>Role</th><th>Active</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($users as $u):
        $self = ((int)$u['id']) === (int)current_user()['id']; ?>
        <tr>
          <td><?= (int)$u['id'] ?></td>
          <td><?= esc($u['username']) ?></td>
          <td><?= esc($u['full_name']) ?></td>
          <td><?= esc($u['role']) ?></td>
          <td><?= $u['is_active'] ? 'Yes' : 'No' ?></td>
          <td>
            <?php if (!$self): ?>
            <form method="post" action="index.php?page=users" class="inline">
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <?= csrf_field() ?>
              <button class="btn ghost small"><?= $u['is_active'] ? 'Disable' : 'Enable' ?></button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <h3>Reset password</h3>
    <form method="post" action="index.php?page=users" class="form inline">
      <input type="hidden" name="action" value="reset">
      <?= csrf_field() ?>
      <select name="id" required>
        <?php foreach ($users as $u): ?>
          <option value="<?= (int)$u['id'] ?>"><?= esc($u['username']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="password" name="password" required placeholder="New password">
      <button class="btn">Reset</button>
    </form>
  </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
