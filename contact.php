<?php
require 'includes/config.php';
require 'includes/functions.php';
require 'includes/auth.php';
auth_start();

$sent  = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$name || !$email || !$message) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Log to DB so messages aren't lost even without mail configured
        $db->query("CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(255) NOT NULL,
            subject VARCHAR(255),
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $ins = $db->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?,?,?,?)");
        $ins->bind_param('ssss', $name, $email, $subject, $message);
        $ins->execute();
        $ins->close();
        $sent = true;
    }
}

$page_title = 'Contact — ' . SITE_TITLE;
$full_width = true;
require 'includes/header.php';
?>
<main class="main-content">
    <h1 style="font-family:var(--font-display);font-size:2rem;margin-bottom:8px;">Contact</h1>
    <p style="color:var(--muted);font-size:.9rem;margin-bottom:28px;border-bottom:1px solid var(--border-mid);padding-bottom:20px;">
        Got a question or want to get in touch? We'd love to hear from you.
    </p>

    <?php if ($sent): ?>
        <div class="alert alert-success">
            Thanks for your message — we'll get back to you soon!
        </div>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Name <span style="color:var(--tomato)">*</span></label>
                <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Email <span style="color:var(--tomato)">*</span></label>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Subject</label>
                <input type="text" name="subject" value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Message <span style="color:var(--tomato)">*</span></label>
                <textarea name="message" rows="6" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn-primary">Send message</button>
        </form>
    <?php endif; ?>
</main>
<?php require 'includes/footer.php'; ?>
