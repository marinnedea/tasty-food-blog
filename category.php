<?php
require 'includes/config.php';
require 'includes/functions.php';
require 'includes/auth.php';
auth_start();

$slug = $_GET['slug'] ?? '';
$st   = $db->prepare("SELECT * FROM categories WHERE slug=?");
$st->bind_param('s', $slug);
$st->execute();
$cat = $st->get_result()->fetch_assoc();
$st->close();
if (!$cat) { header('Location: /'); exit; }

$posts_st = $db->prepare("
    SELECT p.title, p.slug, p.content, p.featured_image, p.created_at,
           u.display_name, u.username
    FROM posts p
    JOIN users u ON p.author_id = u.id
    WHERE p.category_id=? AND p.status='published'
    ORDER BY p.created_at DESC
");
$posts_st->bind_param('i', $cat['id']);
$posts_st->execute();
$posts = $posts_st->get_result();
$posts_st->close();

$page_title = $cat['name'] . ' — ' . SITE_TITLE;
require 'includes/header.php';
?>
<main class="main-content">
    <a href="/" class="btn-back">&larr; Home</a>

    <div class="section-heading">
        <h2 style="color:<?= htmlspecialchars($cat['color']) ?>;font-size:1.1rem;letter-spacing:.05em;">
            <?= htmlspecialchars(strtoupper($cat['name'])) ?>
        </h2>
    </div>

    <?php if ($posts->num_rows === 0): ?>
        <p style="color:var(--muted)">No posts in this category yet.</p>
    <?php else: ?>
        <?php while ($post = $posts->fetch_assoc()): ?>
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
                <h2 class="post-card-title">
                    <a href="/post/<?= htmlspecialchars($post['slug']) ?>"><?= htmlspecialchars($post['title']) ?></a>
                </h2>
                <p class="post-card-excerpt"><?= excerpt($post['content'], 160) ?></p>
                <div class="post-card-meta">
                    <?= date('M j, Y', strtotime($post['created_at'])) ?>
                    &middot; <?= read_time($post['content']) ?> min read
                </div>
                <a href="/post/<?= htmlspecialchars($post['slug']) ?>" class="btn-readmore">Read More &rarr;</a>
            </div>
        </article>
        <?php endwhile; ?>
    <?php endif; ?>
</main>
<?php require 'includes/sidebar.php'; ?>
<?php require 'includes/footer.php'; ?>
