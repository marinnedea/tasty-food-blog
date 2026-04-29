<?php
require_once __DIR__ . '/auth.php';
auth_start();
$_user = current_user();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? SITE_TITLE) ?></title>
    <?php if (SITE_FAVICON): ?>
        <link rel="icon" href="/<?= htmlspecialchars(SITE_FAVICON) ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@100;300&family=Source+Sans+3:wght@300;400;500;600&family=Playfair+Display:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <!-- Top nav bar -->
    <div class="topbar">
        <div class="site-wrap topbar-inner">
            <nav class="topbar-nav">
                <a href="/">Home</a>
                <a href="/blog">Blog</a>
                <a href="/about">About</a>
            </nav>
            <div class="topbar-right">
                <?php if ($_user): ?>
                    <?php if (has_role('admin','editor')): ?>
                        <a href="/admin/">Admin</a>
                    <?php endif; ?>
                    <a href="/profile.php"><?= htmlspecialchars($_user['display_name'] ?: $_user['username']) ?></a>
                    <a href="/logout.php">Logout</a>
                <?php else: ?>
                    <a href="/login.php">Login</a>
                    <a href="/register.php">Register</a>
                <?php endif; ?>
                <button class="theme-toggle" id="theme-toggle" aria-label="Toggle dark mode">
                    <span class="icon-moon">☽</span>
                    <span class="icon-sun">☀</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Site header / wordmark -->
    <header class="site-header">
        <div class="site-wrap site-header-inner">
            <div class="site-wordmark">
                <a href="/">
                    <?php if (SITE_LOGO): ?>
                        <img src="/<?= htmlspecialchars(SITE_LOGO) ?>" alt="<?= htmlspecialchars(SITE_TITLE) ?>" class="site-logo">
                    <?php else: ?>
                        <span class="wm-discover">DISCOVER</span><span class="wm-tasty">TASTY</span>
                    <?php endif; ?>
                </a>
            </div>
            <nav class="cat-pills">
                <?php
                global $db;
                $cats = $db->query("SELECT name, slug, color FROM categories ORDER BY id");
                while ($cat = $cats->fetch_assoc()):
                ?>
                    <a href="/category/<?= htmlspecialchars($cat['slug']) ?>"
                       class="cat-pill"
                       style="background:<?= htmlspecialchars($cat['color']) ?>">
                        <?= htmlspecialchars(strtoupper($cat['name'])) ?>
                    </a>
                <?php endwhile; ?>
            </nav>
        </div>
    </header>

    <div class="site-wrap content-wrap">
