<?php
require '../includes/config.php';
require '../includes/functions.php';
require '../includes/auth.php';
auth_start();
if (has_role('admin','editor')) { header('Location: /admin/'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $st = $db->prepare("SELECT id, username, email, password, role, display_name, nickname, avatar FROM users WHERE username=? OR email=?");
    $st->bind_param('ss', $username, $username);
    $st->execute();
    $user = $st->get_result()->fetch_assoc();
    $st->close();
    if ($user && password_verify($password, $user['password']) && in_array($user['role'], ['admin','editor'])) {
        login_user($user);
        header('Location: /admin/');
        exit;
    }
    $error = 'Invalid credentials or insufficient permissions.';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&family=Playfair+Display:wght@400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <script>var t=localStorage.getItem('theme');if(t)document.documentElement.setAttribute('data-theme',t);</script>
</head>
<body style="justify-content:center;align-items:center;display:flex;">
<div class="auth-wrap" style="margin:0;width:100%">
    <div class="auth-card">
        <h1>Admin Login</h1>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-primary" style="width:100%">Log in</button>
            <p style="margin-top:16px;font-size:.85rem;text-align:center;">
                <a href="/">&larr; Back to blog</a>
            </p>
        </form>
    </div>
</div>
</body>
</html>
