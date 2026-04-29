<?php
require 'includes/config.php';
require 'includes/functions.php';
require 'includes/auth.php';
auth_start();
if (!is_logged_in()) { header('Location: /login.php'); exit; }

$uid  = current_user()['id'];
$tab  = $_GET['tab'] ?? 'favourites';
$error = $success = '';

// Fetch full user record
$st = $db->prepare("SELECT * FROM users WHERE id=?");
$st->bind_param('i', $uid);
$st->execute();
$user = $st->get_result()->fetch_assoc();
$st->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $display  = trim($_POST['display_name'] ?? '');
    $nickname = trim($_POST['nickname'] ?? '');
    $bio      = trim($_POST['bio'] ?? '');
    $email    = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        $avatar = $user['avatar'];
        if (!empty($_FILES['avatar']['name'])) {
            $new = save_image($_FILES['avatar'], __DIR__ . '/uploads/');
            if ($new) $avatar = $new;
        }
        $upd = $db->prepare("UPDATE users SET display_name=?, nickname=?, bio=?, email=?, avatar=? WHERE id=?");
        $upd->bind_param('sssssi', $display, $nickname, $bio, $email, $avatar, $uid);
        $upd->execute();
        $upd->close();

        // Refresh session
        $_SESSION['user']['display_name'] = $display;
        $_SESSION['user']['nickname']     = $nickname;
        $_SESSION['user']['avatar']       = $avatar;
        $user = array_merge($user, compact('display_name', 'nickname', 'bio', 'email', 'avatar'));
        $success = 'Profile updated.';
    }
}

// Favourites
$fav_posts = $db->prepare("
    SELECT p.title, p.slug, p.created_at FROM user_favorites f
    JOIN posts p ON f.item_id=p.id
    WHERE f.user_id=? AND f.item_type='post' AND p.status='published'
    ORDER BY f.created_at DESC
");
$fav_posts->bind_param('i', $uid);
$fav_posts->execute();
$fav_posts_result = $fav_posts->get_result();
$fav_posts->close();

$fav_recipes = $db->prepare("
    SELECT r.title, r.slug, r.created_at FROM user_favorites f
    JOIN recipes r ON f.item_id=r.id
    WHERE f.user_id=? AND f.item_type='recipe' AND r.status='published'
    ORDER BY f.created_at DESC
");
$fav_recipes->bind_param('i', $uid);
$fav_recipes->execute();
$fav_recipes_result = $fav_recipes->get_result();
$fav_recipes->close();

$page_title = 'My Profile — ' . SITE_TITLE;
require 'includes/header.php';
?>
<main class="main-content">

    <div class="profile-header">
        <?php if ($user['avatar']): ?>
            <img class="profile-avatar" src="/uploads/<?= htmlspecialchars($user['avatar']) ?>" alt="">
        <?php else: ?>
            <div class="profile-avatar-placeholder">👤</div>
        <?php endif; ?>
        <div>
            <div class="profile-name"><?= htmlspecialchars($user['display_name'] ?: $user['username']) ?></div>
            <?php if ($user['nickname']): ?>
                <div style="font-size:.85rem;color:var(--muted)">@<?= htmlspecialchars($user['nickname']) ?></div>
            <?php endif; ?>
            <?php if ($user['bio']): ?>
                <p class="profile-bio"><?= htmlspecialchars($user['bio']) ?></p>
            <?php endif; ?>
            <div style="font-size:.78rem;color:var(--muted);margin-top:6px;">
                Member since <?= date('F Y', strtotime($user['created_at'])) ?> &middot;
                Role: <?= ucfirst($user['role']) ?>
            </div>
        </div>
    </div>

    <div class="profile-tabs">
        <a href="?tab=favourites" class="profile-tab <?= $tab==='favourites'?'active':'' ?>">Favourites</a>
        <a href="?tab=settings"   class="profile-tab <?= $tab==='settings'?'active':'' ?>">Settings</a>
    </div>

    <?php if ($tab === 'favourites'): ?>

        <h3 style="font-family:var(--font-display);margin-bottom:12px;">Saved Posts</h3>
        <?php if ($fav_posts_result->num_rows === 0): ?>
            <p style="color:var(--muted);font-size:.9rem;">No saved posts yet.</p>
        <?php else: ?>
            <ul class="widget-list" style="margin-bottom:24px;">
                <?php while ($p = $fav_posts_result->fetch_assoc()): ?>
                    <li>
                        <a href="/post/<?= htmlspecialchars($p['slug']) ?>"><?= htmlspecialchars($p['title']) ?></a>
                        <span class="widget-meta"><?= date('M j, Y', strtotime($p['created_at'])) ?></span>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php endif; ?>

        <h3 style="font-family:var(--font-display);margin-bottom:12px;">Saved Recipes</h3>
        <?php if ($fav_recipes_result->num_rows === 0): ?>
            <p style="color:var(--muted);font-size:.9rem;">No saved recipes yet.</p>
        <?php else: ?>
            <ul class="widget-list">
                <?php while ($r = $fav_recipes_result->fetch_assoc()): ?>
                    <li>
                        <a href="/recipe/<?= htmlspecialchars($r['slug']) ?>"><?= htmlspecialchars($r['title']) ?></a>
                        <span class="widget-meta"><?= date('M j, Y', strtotime($r['created_at'])) ?></span>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php endif; ?>

    <?php elseif ($tab === 'settings'): ?>

        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>Display name</label>
                <input type="text" name="display_name" value="<?= htmlspecialchars($user['display_name']) ?>">
            </div>
            <div class="form-group">
                <label>Nickname <small style="font-weight:400;color:var(--muted)">(shown in comments)</small></label>
                <input type="text" name="nickname" value="<?= htmlspecialchars($user['nickname']) ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>">
            </div>
            <div class="form-group">
                <label>Bio</label>
                <textarea name="bio"><?= htmlspecialchars($user['bio']) ?></textarea>
            </div>
            <div class="form-group">
                <label>Avatar image</label>
                <input type="file" name="avatar" accept="image/*">
            </div>
            <button type="submit" name="update_profile" class="btn-primary">Save changes</button>
        </form>

    <?php endif; ?>

</main>
<?php require 'includes/sidebar.php'; ?>
<?php require 'includes/footer.php'; ?>
