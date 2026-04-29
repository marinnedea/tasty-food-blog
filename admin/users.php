<?php
require '../includes/config.php';
require '../includes/auth.php';
require '../includes/functions.php';
require_admin();

$error = $success = '';
$current_uid = current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) die('Invalid token');
    $action = $_POST['action'] ?? '';

    if ($action === 'change_role') {
        $id   = (int)$_POST['id'];
        $role = in_array($_POST['role'], ['admin','editor','reader']) ? $_POST['role'] : 'reader';
        if ($id === $current_uid) { $error = 'You cannot change your own role.'; }
        else {
            $st = $db->prepare("UPDATE users SET role=? WHERE id=?");
            $st->bind_param('si', $role, $id); $st->execute(); $st->close();
            $success = 'Role updated.';
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id === $current_uid) { $error = 'You cannot delete your own account.'; }
        else {
            $st = $db->prepare("DELETE FROM users WHERE id=?");
            $st->bind_param('i', $id); $st->execute(); $st->close();
            $success = 'User deleted.';
        }
    } elseif ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = in_array($_POST['role'], ['admin','editor','reader']) ? $_POST['role'] : 'reader';
        if (!$username || !$email || !$password) { $error = 'All fields required.'; }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Invalid email.'; }
        else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $st   = $db->prepare("INSERT INTO users (username, email, password, role, display_name, nickname) VALUES (?,?,?,?,?,?)");
            $st->bind_param('ssssss', $username, $email, $hash, $role, $username, $username);
            if (!$st->execute()) $error = 'Username or email already exists.';
            else $success = 'User created.';
            $st->close();
        }
    }
}

$users = $db->query("SELECT id, username, email, role, display_name, created_at FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8"><title>Users — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <script>var t=localStorage.getItem('theme');if(t)document.documentElement.setAttribute('data-theme',t);</script>
</head>
<body style="background:var(--bg);">
<div class="admin-header">
    <span class="admin-title">Users</span>
    <div style="display:flex;gap:20px;"><a href="index.php">Dashboard</a><a href="/admin/logout.php">Logout</a></div>
</div>
<div class="admin-wrap">
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="admin-card" style="margin-bottom:24px;">
        <h2 style="font-size:1rem;margin-bottom:16px;">Add user</h2>
        <form method="post" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;align-items:flex-end;">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <div class="form-group" style="margin:0;"><label>Username</label><input type="text" name="username" required></div>
            <div class="form-group" style="margin:0;"><label>Email</label><input type="email" name="email" required></div>
            <div class="form-group" style="margin:0;"><label>Password</label><input type="password" name="password" required></div>
            <div class="form-group" style="margin:0;">
                <label>Role</label>
                <select name="role">
                    <option value="reader">Reader</option>
                    <option value="editor">Editor</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="height:40px;">Add user</button>
        </form>
    </div>

    <table>
        <thead><tr><th>Username</th><th>Display name</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr></thead>
        <tbody>
        <?php while ($u = $users->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['display_name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td>
                    <?php if ($u['id'] !== $current_uid): ?>
                    <form method="post" style="display:flex;gap:6px;align-items:center;">
                        <input type="hidden" name="action" value="change_role">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <select name="role" style="padding:4px 6px;font-size:.82rem;border:1px solid var(--border-mid);border-radius:4px;background:var(--bg);color:var(--ink);">
                            <?php foreach (['admin','editor','reader'] as $r): ?>
                                <option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-sm btn-edit">Save</button>
                    </form>
                    <?php else: ?>
                        <span style="font-size:.82rem;color:var(--muted);">Admin (you)</span>
                    <?php endif; ?>
                </td>
                <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <?php if ($u['id'] !== $current_uid): ?>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this user?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <button type="submit" class="btn-sm btn-danger">Delete</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body></html>
