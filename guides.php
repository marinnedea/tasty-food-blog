<?php
require 'includes/config.php';
require 'includes/functions.php';
require 'includes/auth.php';
auth_start();

$per_page = 12;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $per_page;

$total = (int)$db->query("SELECT COUNT(*) c FROM guides WHERE status='published'")->fetch_assoc()['c'];
$pages = (int)ceil($total / $per_page);

$st = $db->prepare("
    SELECT g.*, u.display_name, u.username
    FROM guides g
    JOIN users u ON g.author_id = u.id
    WHERE g.status='published'
    ORDER BY g.created_at DESC
    LIMIT ? OFFSET ?
");
$st->bind_param('ii', $per_page, $offset);
$st->execute();
$guides = $st->get_result();
$st->close();

$page_title = 'Guides' . ($page > 1 ? " — Page $page" : '') . ' — ' . SITE_TITLE;
$full_width = true;
require 'includes/header.php';
?>
<main class="main-content">
    <h1 style="font-family:var(--font-display);font-size:1.8rem;margin-bottom:6px;">Guides</h1>
    <p style="color:var(--muted);font-size:.9rem;margin-bottom:28px;">Places worth visiting — one dish at a time.</p>

    <?php if ($guides->num_rows === 0): ?>
        <p style="color:var(--muted);">No guides published yet.</p>
    <?php endif; ?>

    <div class="guides-grid">
    <?php while ($g = $guides->fetch_assoc()):
        $maps_url = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($g['place_name'] . ' ' . $g['location']);
    ?>
    <article class="guide-card">
        <?php if ($g['featured_image']): ?>
            <a href="/guide/<?= htmlspecialchars($g['slug']) ?>">
                <img class="guide-card-img" src="/uploads/<?= htmlspecialchars($g['featured_image']) ?>"
                     alt="<?= htmlspecialchars($g['title']) ?>">
            </a>
        <?php else: ?>
            <a href="/guide/<?= htmlspecialchars($g['slug']) ?>">
                <div class="guide-card-img guide-card-img-placeholder"></div>
            </a>
        <?php endif; ?>
        <div class="guide-card-body">
            <div class="guide-card-meta-top">
                <span class="guide-badge">Guide</span>
                <?php if ($g['price_range']): ?>
                    <span class="guide-price"><?= htmlspecialchars($g['price_range']) ?></span>
                <?php endif; ?>
                <?php if ($g['score']): ?>
                    <span style="font-size:.8rem;color:var(--tomato);">
                        <?= str_repeat('★', $g['score']) ?><?= str_repeat('☆', 5 - $g['score']) ?>
                    </span>
                <?php endif; ?>
            </div>
            <h2 class="guide-card-title">
                <a href="/guide/<?= htmlspecialchars($g['slug']) ?>"><?= htmlspecialchars($g['title']) ?></a>
            </h2>
            <div class="guide-card-where">
                <span><?= htmlspecialchars($g['dish']) ?></span>
                <span>&middot;</span>
                <span><?= htmlspecialchars($g['place_name']) ?></span>
                <span>&middot;</span>
                <a href="<?= htmlspecialchars($maps_url) ?>" target="_blank" rel="noopener"
                   style="color:var(--cobalt);"><?= htmlspecialchars($g['location']) ?> ↗</a>
            </div>
            <?php if ($g['excerpt']): ?>
                <p class="guide-card-excerpt"><?= htmlspecialchars($g['excerpt']) ?></p>
            <?php endif; ?>
            <a href="/guide/<?= htmlspecialchars($g['slug']) ?>" class="btn-readmore">Read more &rarr;</a>
        </div>
    </article>
    <?php endwhile; ?>
    </div>

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
<?php require 'includes/footer.php'; ?>
