<?php
global $db;

// Recent posts
$recent_posts = $db->query("
    SELECT p.title, p.slug, p.created_at, u.display_name, u.username
    FROM posts p
    JOIN users u ON p.author_id = u.id
    WHERE p.status='published'
    ORDER BY p.created_at DESC LIMIT 5
");

// Categories
$sidebar_cats = $db->query("SELECT name, slug, color FROM categories ORDER BY id");

// Search query
$search_q = htmlspecialchars($_GET['q'] ?? '');
?>
<aside class="sidebar">

    <!-- Search -->
    <div class="widget">
        <h3 class="widget-title">Search</h3>
        <form class="search-form" action="/search" method="get">
            <input type="text" name="q" value="<?= $search_q ?>" placeholder="Search…">
            <button type="submit">Go</button>
        </form>
    </div>

    <!-- Recent posts -->
    <div class="widget">
        <h3 class="widget-title">Recent Posts</h3>
        <ul class="widget-list">
            <?php while ($p = $recent_posts->fetch_assoc()): ?>
                <li>
                    <a href="/post/<?= htmlspecialchars($p['slug']) ?>"><?= htmlspecialchars($p['title']) ?></a>
                    <span class="widget-meta"><?= date('M j', strtotime($p['created_at'])) ?></span>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>

    <!-- Categories -->
    <div class="widget">
        <h3 class="widget-title">Categories</h3>
        <ul class="widget-list">
            <?php while ($cat = $sidebar_cats->fetch_assoc()): ?>
                <li>
                    <a href="/category/<?= htmlspecialchars($cat['slug']) ?>"
                       style="color:<?= htmlspecialchars($cat['color']) ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </a>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>

</aside>
