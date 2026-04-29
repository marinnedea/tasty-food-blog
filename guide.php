<?php
require 'includes/config.php';
require 'includes/functions.php';
require 'includes/auth.php';
auth_start();

$slug = $_GET['slug'] ?? '';
if (!$slug) { header('Location: /guides'); exit; }

$st = $db->prepare("
    SELECT g.*, u.display_name, u.username
    FROM guides g
    JOIN users u ON g.author_id = u.id
    WHERE g.slug=? AND g.status='published'
");
$st->bind_param('s', $slug);
$st->execute();
$guide = $st->get_result()->fetch_assoc();
$st->close();
if (!$guide) { header('Location: /guides'); exit; }

$rating_st = $db->prepare("SELECT AVG(rating) AS avg, COUNT(*) AS cnt FROM ratings WHERE item_type='guide' AND item_id=?");
$rating_st->bind_param('i', $guide['id']);
$rating_st->execute();
$rating_row = $rating_st->get_result()->fetch_assoc();
$rating_st->close();

$user_rating = 0;
if (is_logged_in()) {
    $uid   = current_user()['id'];
    $ur_st = $db->prepare("SELECT rating FROM ratings WHERE user_id=? AND item_type='guide' AND item_id=?");
    $ur_st->bind_param('ii', $uid, $guide['id']);
    $ur_st->execute();
    $ur = $ur_st->get_result()->fetch_assoc();
    $ur_st->close();
    $user_rating = $ur['rating'] ?? 0;
}

$maps_url = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($guide['place_name'] . ' ' . $guide['location']);

$page_title = $guide['title'] . ' — ' . SITE_TITLE;
require 'includes/header.php';
?>
<main class="main-content">
    <a href="/guides" class="btn-back">&larr; All Guides</a>

    <?php if ($guide['featured_image']): ?>
        <img class="post-hero" src="/uploads/<?= htmlspecialchars($guide['featured_image']) ?>"
             alt="<?= htmlspecialchars($guide['title']) ?>">
    <?php endif; ?>

    <header class="post-header">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
            <span class="guide-badge">Guide</span>
            <?php if ($guide['price_range']): ?>
                <span class="guide-price"><?= htmlspecialchars($guide['price_range']) ?></span>
            <?php endif; ?>
            <?php if ($guide['score']): ?>
                <span class="guide-score">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span style="color:<?= $i <= $guide['score'] ? 'var(--tomato)' : 'var(--border-mid)' ?>">&#9733;</span>
                    <?php endfor; ?>
                </span>
            <?php endif; ?>
        </div>

        <h1><?= htmlspecialchars($guide['title']) ?></h1>

        <?php if ($guide['excerpt']): ?>
            <p class="guide-excerpt"><?= htmlspecialchars($guide['excerpt']) ?></p>
        <?php endif; ?>

        <div class="guide-meta-bar">
            <div class="guide-meta-item">
                <span class="guide-meta-label">Dish</span>
                <span class="guide-meta-value"><?= htmlspecialchars($guide['dish']) ?></span>
            </div>
            <div class="guide-meta-item">
                <span class="guide-meta-label">Place</span>
                <span class="guide-meta-value"><?= htmlspecialchars($guide['place_name']) ?></span>
            </div>
            <div class="guide-meta-item">
                <span class="guide-meta-label">Location</span>
                <span class="guide-meta-value">
                    <a href="<?= htmlspecialchars($maps_url) ?>" target="_blank" rel="noopener"
                       style="color:var(--cobalt);">
                        <?= htmlspecialchars($guide['location']) ?> ↗
                    </a>
                </span>
            </div>
            <div class="guide-meta-item">
                <span class="guide-meta-label">By</span>
                <span class="guide-meta-value"><?= htmlspecialchars($guide['display_name'] ?: $guide['username']) ?></span>
            </div>
            <?php if (round($rating_row['avg'], 1) > 0): ?>
            <div class="guide-meta-item">
                <span class="guide-meta-label">Reader score</span>
                <span class="guide-meta-value">&#9733; <?= number_format($rating_row['avg'], 1) ?> (<?= $rating_row['cnt'] ?>)</span>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <div class="post-content">
        <?= $guide['content'] ?>
    </div>

    <?php if (is_logged_in()): ?>
    <div style="margin-top:32px;padding-top:20px;border-top:1px solid var(--border-mid);">
        <p style="font-size:.85rem;font-weight:600;color:var(--muted);margin-bottom:8px;">Rate this guide</p>
        <div class="star-rating" id="star-rating" data-type="guide" data-id="<?= $guide['id'] ?>">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="star <?= $i <= $user_rating ? 'active' : '' ?>" data-val="<?= $i ?>">&#9733;</span>
            <?php endfor; ?>
        </div>
    </div>
    <div style="margin-top:16px;">
        <button class="btn-primary fav-btn" data-type="guide" data-id="<?= $guide['id'] ?>" style="font-size:.82rem;padding:6px 16px;">
            ♡ Save to favourites
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
            method: 'POST', credentials: 'same-origin',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({type: type, id: id, rating: val})
        }).then(r => r.json()).then(d => {
            if (d.ok) document.querySelectorAll('#star-rating .star').forEach(s => {
                s.classList.toggle('active', parseInt(s.dataset.val) <= parseInt(val));
            });
        });
    });
});

document.querySelectorAll('.fav-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var self = this;
        fetch('/api/favourite.php', {
            method: 'POST', credentials: 'same-origin',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({type: self.dataset.type, id: self.dataset.id})
        }).then(r => r.json()).then(d => {
            if (d.ok) {
                self.textContent = d.saved ? '♥ Saved' : '♡ Save to favourites';
                self.classList.toggle('fav-saved', d.saved);
            }
        });
    });
});
</script>

<?php require 'includes/footer.php'; ?>
