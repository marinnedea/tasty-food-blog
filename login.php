<?php
require 'includes/config.php';
require 'includes/functions.php';
require 'includes/auth.php';
auth_start();

if (is_logged_in()) { header('Location: /'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $st = $db->prepare("SELECT id, username, email, password, role, display_name, nickname, avatar FROM users WHERE username=? OR email=?");
    $st->bind_param('ss', $username, $username);
    $st->execute();
    $user = $st->get_result()->fetch_assoc();
    $st->close();

    if ($user && password_verify($password, $user['password'])) {
        login_user($user);
        $redirect = has_role('admin','editor') ? '/admin/' : '/';
        header('Location: ' . $redirect);
        exit;
    }
    $error = 'Invalid username or password.';
}

$page_title = 'Login — ' . SITE_TITLE;
require 'includes/header.php';
?>
<div style="grid-column:1/-1">
<div class="auth-wrap">
    <div class="auth-card">
        <h1>Welcome back</h1>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-primary" style="width:100%">Log in</button>
            <p style="margin-top:16px;font-size:.85rem;text-align:center;color:var(--muted)">
                No account? <a href="/register.php">Register</a>
            </p>
        </form>
    </div>
</div>
</div>
<?php require 'includes/footer.php'; ?>
