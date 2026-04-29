<?php
require '../includes/config.php';
require '../includes/auth.php';
require_backend();

$counts = [
    'posts'    => $db->query("SELECT COUNT(*) c FROM posts")->fetch_assoc()['c'],
    'recipes'  => $db->query("SELECT COUNT(*) c FROM recipes")->fetch_assoc()['c'],
    'guides'   => $db->query("SELECT COUNT(*) c FROM guides")->fetch_assoc()['c'],
    'users'    => $db->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'],
    'cats'     => $db->query("SELECT COUNT(*) c FROM categories")->fetch_assoc()['c'],
];
$u = current_user();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Admin — <?= SITE_TITLE ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <script>var t=localStorage.getItem('theme');if(t)document.documentElement.setAttribute('data-theme',t);</script>
    <style>
        .dash-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:16px; margin-bottom:32px; }
        .dash-card { background:var(--bg-card); border-radius:var(--radius-lg); padding:20px; box-shadow:var(--shadow-sm); text-align:center; }
        .dash-card .num { font-size:2.5rem; font-weight:700; color:var(--basil); line-height:1; }
        .dash-card .lbl { font-size:.78rem; font-weight:600; letter-spacing:.07em; text-transform:uppercase; color:var(--muted); margin-top:4px; }
        .dash-links { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; }
        .dash-link { background:var(--bg-card); border:1px solid var(--border-mid); border-radius:var(--radius-lg); padding:16px 20px; text-decoration:none; color:var(--ink); font-weight:600; font-size:.9rem; transition:background var(--transition); }
        .dash-link:hover { background:var(--bg-warm); text-decoration:none; }
    </style>
</head>
<body style="background:var(--bg);">
<div class="admin-header">
    <span class="admin-title"><?= SITE_TITLE ?> — Admin</span>
    <div style="display:flex;gap:20px;align-items:center;">
        <span style="color:#8892a4;font-size:.82rem;"><?= htmlspecialchars($u['display_name'] ?: $u['username']) ?></span>
        <a href="/">View blog</a>
        <a href="/admin/logout.php">Logout</a>
    </div>
</div>
<div class="admin-wrap">
    <h1 style="font-size:1.3rem;margin-bottom:20px;">Dashboard</h1>

    <div class="dash-grid">
        <div class="dash-card"><div class="num"><?= $counts['posts'] ?></div><div class="lbl">Posts</div></div>
        <div class="dash-card"><div class="num"><?= $counts['recipes'] ?></div><div class="lbl">Recipes</div></div>
        <div class="dash-card"><div class="num"><?= $counts['guides'] ?></div><div class="lbl">Guides</div></div>
        <div class="dash-card"><div class="num"><?= $counts['users'] ?></div><div class="lbl">Users</div></div>
        <div class="dash-card"><div class="num"><?= $counts['cats'] ?></div><div class="lbl">Categories</div></div>
    </div>

    <div class="dash-links">
        <a href="posts.php" class="dash-link">📄 Manage Posts</a>
        <a href="add_post.php" class="dash-link">✏️ New Post</a>
        <a href="recipes.php" class="dash-link">🍽️ Manage Recipes</a>
        <a href="add_recipe.php" class="dash-link">➕ New Recipe</a>
        <a href="guides.php" class="dash-link">🗺️ Manage Guides</a>
        <a href="add_guide.php" class="dash-link">📍 New Guide</a>
        <a href="categories.php" class="dash-link">🏷️ Categories</a>
        <?php if (has_role('admin')): ?>
            <a href="users.php" class="dash-link">👥 Users</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
