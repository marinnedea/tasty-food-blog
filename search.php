<?php
require 'includes/config.php';
require 'includes/functions.php';
require 'includes/auth.php';
auth_start();

$q = trim($_GET['q'] ?? '');
$results = [];

if ($q !== '') {
    $like = '%' . $db->real_escape_string($q) . '%';
    $st = $db->prepare("
        SELECT p.title, p.slug, p.content, p.featured_image, p.created_at,
               c.name AS cat_name, c.slug AS cat_slug, c.color AS cat_color
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status='published' AND (p.title LIKE ? OR p.content LIKE ?)
        ORDER BY p.created_at DESC LIMIT 20
    ");
    $st->bind_param('ss', $like, $like);
    $st->execute();
    $results = $st->get_result();
    $st->close();
}

$page_title = 'Search — ' . SITE_TITLE;
require 'includes/header.php';
?>
<main class="main-content">
    <h1 style="font-family:var(--font-display);margin-bottom:20px;">
        <?= $q ? 'Results for "' . htmlspecialchars($q) . '"' : 'Search' ?>
    </h1>

    <?php if ($q && $results->num_rows === 0): ?>
        <p style="color:var(--muted)">No results found.</p>
    <?php elseif ($q): ?>
        <?php while ($post = $results->fetch_assoc()): ?>
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
                <div class="post-card-meta"><?= date('M j, Y', strtotime($post['created_at'])) ?></div>
            </div>
        </article>
        <?php endwhile; ?>
    <?php endif; ?>
</main>
<?php require 'includes/sidebar.php'; ?>
<?php require 'includes/footer.php'; ?>
