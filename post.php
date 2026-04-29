<?php
require 'includes/config.php';
require 'includes/functions.php';
require 'includes/auth.php';
auth_start();

$slug = $_GET['slug'] ?? '';
if (!$slug) { header('Location: /'); exit; }

$st = $db->prepare("
    SELECT p.*, c.name AS cat_name, c.slug AS cat_slug, c.color AS cat_color,
           u.display_name, u.username, u.avatar
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    JOIN users u ON p.author_id = u.id
    WHERE p.slug=? AND p.status='published'
");
$st->bind_param('s', $slug);
$st->execute();
$post = $st->get_result()->fetch_assoc();
$st->close();
if (!$post) { header('Location: /'); exit; }

// Average rating
$rating_st = $db->prepare("SELECT AVG(rating) AS avg, COUNT(*) AS cnt FROM ratings WHERE item_type='post' AND item_id=?");
$rating_st->bind_param('i', $post['id']);
$rating_st->execute();
$rating_row = $rating_st->get_result()->fetch_assoc();
$rating_st->close();

$user_rating = 0;
if (is_logged_in()) {
    $ur_st = $db->prepare("SELECT rating FROM ratings WHERE user_id=? AND item_type='post' AND item_id=?");
    $uid = current_user()['id'];
    $ur_st->bind_param('ii', $uid, $post['id']);
    $ur_st->execute();
    $ur = $ur_st->get_result()->fetch_assoc();
    $ur_st->close();
    $user_rating = $ur['rating'] ?? 0;
}

$page_title = $post['title'] . ' — ' . SITE_TITLE;
require 'includes/header.php';
?>
<main class="main-content">
    <a href="javascript:history.back()" class="btn-back">&larr; Back</a>

    <?php if ($post['featured_image']): ?>
        <img class="post-hero" src="/uploads/<?= htmlspecialchars($post['featured_image']) ?>"
             alt="<?= htmlspecialchars($post['title']) ?>">
    <?php endif; ?>

    <header class="post-header">
        <?php if ($post['cat_name']): ?>
            <a class="post-card-cat" href="/category/<?= htmlspecialchars($post['cat_slug']) ?>"
               style="color:<?= htmlspecialchars($post['cat_color']) ?>">
                <?= htmlspecialchars($post['cat_name']) ?>
            </a>
        <?php endif; ?>
        <h1><?= htmlspecialchars($post['title']) ?></h1>
        <div class="post-meta">
            <span><?= date('F j, Y', strtotime($post['created_at'])) ?></span>
            <span>&middot;</span>
            <span><?= read_time($post['content']) ?> min read</span>
            <span>&middot;</span>
            <span>By <?= htmlspecialchars($post['display_name'] ?: $post['username']) ?></span>
            <?php if (round($rating_row['avg'], 1) > 0): ?>
                <span>&middot;</span>
                <span>&#9733; <?= number_format($rating_row['avg'], 1) ?> (<?= $rating_row['cnt'] ?>)</span>
            <?php endif; ?>
        </div>
    </header>

    <div class="post-content">
        <?= render_shortcodes($post['content'], $db) ?>
    </div>

    <!-- Star rating -->
    <?php if (is_logged_in()): ?>
    <div style="margin-top:32px; padding-top:20px; border-top:1px solid var(--border-mid);">
        <p style="font-size:.85rem;font-weight:600;color:var(--muted);margin-bottom:8px;">Rate this post</p>
        <div class="star-rating" id="star-rating" data-type="post" data-id="<?= $post['id'] ?>">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="star <?= $i <= $user_rating ? 'active' : '' ?>" data-val="<?= $i ?>">&#9733;</span>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Favourite -->
    <?php if (is_logged_in()): ?>
    <div style="margin-top:16px;">
        <button class="btn-primary fav-btn" data-type="post" data-id="<?= $post['id'] ?>" style="font-size:.82rem;padding:6px 16px;">
            &#9825; Save to favourites
        </button>
    </div>
    <?php endif; ?>
</main>
<?php require 'includes/sidebar.php'; ?>

<script>
document.querySelectorAll('#star-rating .star').forEach(function(star) {
    star.addEventListener('click', function() {
        var val  = this.dataset.val;
        var type = document.getElementById('star-rating').dataset.type;
        var id   = document.getElementById('star-rating').dataset.id;
        fetch('/api/rate.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({type: type, id: id, rating: val})
        }).then(function(r){ return r.json(); }).then(function(d){
            if (d.ok) {
                document.querySelectorAll('#star-rating .star').forEach(function(s){
                    s.classList.toggle('active', parseInt(s.dataset.val) <= parseInt(val));
                });
            }
        });
    });
});

document.querySelectorAll('.fav-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        fetch('/api/favourite.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({type: this.dataset.type, id: this.dataset.id})
        }).then(function(r){ return r.json(); }).then(function(d){
            btn.textContent = d.saved ? '♥ Saved' : '♡ Save to favourites';
        });
    });
});
</script>

<?php require 'includes/footer.php'; ?>
