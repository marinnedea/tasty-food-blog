<?php
require 'includes/config.php';
require 'includes/functions.php';
require 'includes/auth.php';
auth_start();

$per_page = 10;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $per_page;

$total = (int)$db->query("SELECT COUNT(*) c FROM posts WHERE status='published'")->fetch_assoc()['c'];
$pages = (int)ceil($total / $per_page);

$st = $db->prepare("
    SELECT p.*, c.name AS cat_name, c.slug AS cat_slug, c.color AS cat_color,
           u.display_name, u.username
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    JOIN users u ON p.author_id = u.id
    WHERE p.status='published'
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$st->bind_param('ii', $per_page, $offset);
$st->execute();
$posts = $st->get_result();
$st->close();

$page_title = 'Blog' . ($page > 1 ? " — Page $page" : '') . ' — ' . SITE_TITLE;
require 'includes/header.php';
?>
<main class="main-content">
    <h1 style="font-family:var(--font-display);font-size:1.8rem;margin-bottom:24px;">All Posts</h1>

    <?php if ($posts->num_rows === 0): ?>
        <p style="color:var(--muted);">No posts yet.</p>
    <?php endif; ?>

    <?php while ($post = $posts->fetch_assoc()): ?>
    <article class="post-card">
        <?php if ($post['featured_image']): ?>
            <a href="/post/<?= htmlspecialchars($post['slug']) ?>">
                <img class="post-card-img" src="/uploads/<?= htmlspecialchars($post['featured_image']) ?>"
                     alt="<?= htmlspecialchars($post['title']) ?>">
            </a>
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
            <div class="post-card-meta">
                <span><?= date('F j, Y', strtotime($post['created_at'])) ?></span>
                <span>&middot;</span>
                <span><?= read_time($post['content']) ?> min read</span>
            </div>
            <p class="post-card-excerpt"><?= excerpt($post['content'], 180) ?></p>
            <a href="/post/<?= htmlspecialchars($post['slug']) ?>" class="btn-readmore">Read more &rarr;</a>
        </div>
    </article>
    <?php endwhile; ?>

    <?php if ($pages > 1): ?>
    <nav class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="btn-readmore">&larr; Newer</a>
        <?php endif; ?>
        <span style="color:var(--muted);font-size:.85rem;">Page <?= $page ?> of <?= $pages ?></span>
        <?php if ($page < $pages): ?>
            <a href="?page=<?= $page + 1 ?>" class="btn-readmore">Older &rarr;</a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
</main>
<?php require 'includes/sidebar.php'; ?>
<?php require 'includes/footer.php'; ?>
