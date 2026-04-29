<?php
require 'includes/config.php';
require 'includes/functions.php';
require 'includes/auth.php';
auth_start();

if (is_logged_in()) { header('Location: /'); exit; }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    $display  = trim($_POST['display_name'] ?? '') ?: $username;

    if (!$username || !$email || !$password) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $chk = $db->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $chk->bind_param('ss', $username, $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = 'Username or email already taken.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = $db->prepare("INSERT INTO users (username, email, password, display_name, nickname) VALUES (?,?,?,?,?)");
            $ins->bind_param('sssss', $username, $email, $hash, $display, $username);
            $ins->execute();
            $ins->close();
            $success = 'Account created! You can now <a href="/login.php">log in</a>.';
        }
        $chk->close();
    }
}

$page_title = 'Register — ' . SITE_TITLE;
require 'includes/header.php';
?>
<div style="grid-column:1/-1">
<div class="auth-wrap">
    <div class="auth-card">
        <h1>Create an account</h1>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <?php if (!$success): ?>
        <form method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Display name</label>
                <input type="text" name="display_name" value="<?= htmlspecialchars($_POST['display_name'] ?? '') ?>" placeholder="How your name appears publicly">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Password <small style="font-weight:400;color:var(--muted)">(min 8 chars)</small></label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Confirm password</label>
                <input type="password" name="confirm" required>
            </div>
            <button type="submit" class="btn-primary" style="width:100%">Create account</button>
            <p style="margin-top:16px;font-size:.85rem;text-align:center;color:var(--muted)">
                Already have an account? <a href="/login.php">Log in</a>
            </p>
        </form>
        <?php endif; ?>
    </div>
</div>
</div>
<?php require 'includes/footer.php'; ?>
