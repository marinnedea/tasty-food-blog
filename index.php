<?php
require 'includes/config.php';
require 'includes/functions.php';
require 'includes/auth.php';
auth_start();

// Featured post (most recent published)
$featured_st = $db->prepare("
    SELECT p.id, p.title, p.slug, p.content, p.featured_image, p.created_at,
           c.name AS cat_name, c.slug AS cat_slug, c.color AS cat_color,
           u.display_name, u.username
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    JOIN users u ON p.author_id = u.id
    WHERE p.status='published'
    ORDER BY p.created_at DESC LIMIT 1
");
$featured_st->execute();
$featured = $featured_st->get_result()->fetch_assoc();
$featured_st->close();

// Latest posts (skip the featured one)
$latest_st = $db->prepare("
    SELECT p.id, p.title, p.slug, p.content, p.featured_image, p.created_at,
           c.name AS cat_name, c.slug AS cat_slug, c.color AS cat_color,
           u.display_name, u.username
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    JOIN users u ON p.author_id = u.id
    WHERE p.status='published'" . ($featured ? " AND p.id != {$featured['id']}" : "") . "
    ORDER BY p.created_at DESC LIMIT 10
");
$latest_st->execute();
$latest = $latest_st->get_result();
$latest_st->close();

$page_title = SITE_TITLE . ' — ' . SITE_SUBTITLE;
require 'includes/header.php';
?>
<main class="main-content">

    <?php if ($featured): ?>
    <div class="featured-post">
        <?php if ($featured['featured_image']): ?>
            <a href="/post/<?= htmlspecialchars($featured['slug']) ?>">
                <img class="featured-post-img" src="/uploads/<?= htmlspecialchars($featured['featured_image']) ?>"
                     alt="<?= htmlspecialchars($featured['title']) ?>">
            </a>
        <?php endif; ?>
        <div class="featured-post-body">
            <?php if ($featured['cat_name']): ?>
                <a class="post-card-cat" href="/category/<?= htmlspecialchars($featured['cat_slug']) ?>"
                   style="color:<?= htmlspecialchars($featured['cat_color']) ?>">
                    <?= htmlspecialchars($featured['cat_name']) ?>
                </a>
            <?php endif; ?>
            <h2 class="featured-post-title">
                <a href="/post/<?= htmlspecialchars($featured['slug']) ?>"><?= htmlspecialchars($featured['title']) ?></a>
            </h2>
            <p class="post-card-excerpt"><?= excerpt($featured['content'], 180) ?></p>
            <div class="post-meta">
                <?= date('M j, Y', strtotime($featured['created_at'])) ?>
                &middot; <?= read_time($featured['content']) ?> min read
            </div>
            <a href="/post/<?= htmlspecialchars($featured['slug']) ?>" class="btn-readmore">Read the full story &rarr;</a>
        </div>
    </div>
    <?php endif; ?>

    <div class="section-heading">
        <h2>Latest Posts</h2>
        <a href="/blog">View all &rarr;</a>
    </div>

    <?php while ($post = $latest->fetch_assoc()): ?>
    <article class="post-card">
        <?php if ($post['featured_image']): ?>
            <a href="/post/<?= htmlspecialchars($post['slug']) ?>">
                <img class="post-card-thumb" src="/uploads/<?= htmlspecialchars($post['featured_image']) ?>"
                     alt="<?= htmlspecialchars($post['title']) ?>">
            </a>
        <?php else: ?>
            <div class="post-card-thumb-placeholder"></div>
        <?php endif; ?>
        <div class="post-card-body">
            <?php if ($post['cat_name']): ?>
                <a class="post-card-cat" href="/category/<?= htmlspecialchars($post['cat_slug']) ?>"
                   style="color:<?= htmlspecialchars($post['cat_color']) ?>">
                    <?= htmlspecialchars($post['cat_name']) ?>
                </a>
            <?php endif; ?>
            <h2 class="post-card-title">
                <a href="/post/<?= htmlspecialchars($post['slug']) ?>"><?= htmlspecialchars($post['title']) ?></a>
            </h2>
            <p class="post-card-excerpt"><?= excerpt($post['content'], 160) ?></p>
            <div class="post-card-meta">
                <?= date('M j', strtotime($post['created_at'])) ?>
                &middot; <?= read_time($post['content']) ?> min read
            </div>
            <a href="/post/<?= htmlspecialchars($post['slug']) ?>" class="btn-readmore">Read More &rarr;</a>
        </div>
    </article>
    <?php endwhile; ?>

</main>
<?php require 'includes/sidebar.php'; ?>
<?php require 'includes/footer.php'; ?>
