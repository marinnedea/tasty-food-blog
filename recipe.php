<?php
require 'includes/config.php';
require 'includes/functions.php';
require 'includes/auth.php';
auth_start();

$slug = $_GET['slug'] ?? '';
if (!$slug) { header('Location: /'); exit; }

$st = $db->prepare("
    SELECT r.*, c.name AS cat_name, c.slug AS cat_slug, c.color AS cat_color,
           u.display_name, u.username
    FROM recipes r
    LEFT JOIN categories c ON r.category_id = c.id
    JOIN users u ON r.author_id = u.id
    WHERE r.slug=? AND r.status='published'
");
$st->bind_param('s', $slug);
$st->execute();
$recipe = $st->get_result()->fetch_assoc();
$st->close();
if (!$recipe) { header('Location: /'); exit; }

$rating_st = $db->prepare("SELECT AVG(rating) AS avg, COUNT(*) AS cnt FROM ratings WHERE item_type='recipe' AND item_id=?");
$rating_st->bind_param('i', $recipe['id']);
$rating_st->execute();
$rating_row = $rating_st->get_result()->fetch_assoc();
$rating_st->close();

$user_rating = 0;
if (is_logged_in()) {
    $ur_st = $db->prepare("SELECT rating FROM ratings WHERE user_id=? AND item_type='recipe' AND item_id=?");
    $uid = current_user()['id'];
    $ur_st->bind_param('ii', $uid, $recipe['id']);
    $ur_st->execute();
    $ur = $ur_st->get_result()->fetch_assoc();
    $ur_st->close();
    $user_rating = $ur['rating'] ?? 0;
}

$difficulty_label = ['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard'];

$page_title = $recipe['title'] . ' — ' . SITE_TITLE;
require 'includes/header.php';
?>
<main class="main-content">
    <a href="javascript:history.back()" class="btn-back">&larr; Back</a>

    <?php if ($recipe['featured_image']): ?>
        <img class="post-hero" src="/uploads/<?= htmlspecialchars($recipe['featured_image']) ?>"
             alt="<?= htmlspecialchars($recipe['title']) ?>">
    <?php endif; ?>

    <header class="post-header">
        <?php if ($recipe['cat_name']): ?>
            <a class="post-card-cat" href="/category/<?= htmlspecialchars($recipe['cat_slug']) ?>"
               style="color:<?= htmlspecialchars($recipe['cat_color']) ?>">
                <?= htmlspecialchars($recipe['cat_name']) ?>
            </a>
        <?php endif; ?>
        <h1><?= htmlspecialchars($recipe['title']) ?></h1>
        <div class="post-meta">
            <span><?= date('F j, Y', strtotime($recipe['created_at'])) ?></span>
            <span>&middot;</span>
            <span>By <?= htmlspecialchars($recipe['display_name'] ?: $recipe['username']) ?></span>
            <?php if (round($rating_row['avg'], 1) > 0): ?>
                <span>&middot;</span>
                <span>&#9733; <?= number_format($rating_row['avg'], 1) ?> (<?= $rating_row['cnt'] ?>)</span>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($recipe['description']): ?>
        <p style="font-size:1.05rem;color:var(--ink-soft);margin-bottom:16px;"><?= htmlspecialchars($recipe['description']) ?></p>
    <?php endif; ?>

    <div class="recipe-meta-bar">
        <?php if ($recipe['prep_time']): ?>
            <div class="recipe-meta-item">
                <span class="recipe-meta-label">Prep time</span>
                <span class="recipe-meta-value"><?= $recipe['prep_time'] ?> min</span>
            </div>
        <?php endif; ?>
        <?php if ($recipe['cook_time']): ?>
            <div class="recipe-meta-item">
                <span class="recipe-meta-label">Cook time</span>
                <span class="recipe-meta-value"><?= $recipe['cook_time'] ?> min</span>
            </div>
        <?php endif; ?>
        <?php if ($recipe['prep_time'] && $recipe['cook_time']): ?>
            <div class="recipe-meta-item">
                <span class="recipe-meta-label">Total time</span>
                <span class="recipe-meta-value"><?= $recipe['prep_time'] + $recipe['cook_time'] ?> min</span>
            </div>
        <?php endif; ?>
        <?php if ($recipe['servings']): ?>
            <div class="recipe-meta-item">
                <span class="recipe-meta-label">Servings</span>
                <span class="recipe-meta-value"><?= $recipe['servings'] ?></span>
            </div>
        <?php endif; ?>
        <div class="recipe-meta-item">
            <span class="recipe-meta-label">Difficulty</span>
            <span class="recipe-meta-value"><?= $difficulty_label[$recipe['difficulty']] ?></span>
        </div>
    </div>

    <div class="recipe-section">
        <h3>Ingredients</h3>
        <ul class="recipe-ingredients">
            <?php foreach (array_filter(array_map('trim', explode("\n", $recipe['ingredients']))) as $ing): ?>
                <li><?= htmlspecialchars($ing) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="recipe-section">
        <h3>Instructions</h3>
        <ol class="recipe-steps">
            <?php foreach (array_filter(array_map('trim', explode("\n", $recipe['steps']))) as $step): ?>
                <li><?= htmlspecialchars($step) ?></li>
            <?php endforeach; ?>
        </ol>
    </div>

    <!-- Star rating -->
    <?php if (is_logged_in()): ?>
    <div style="margin-top:32px;padding-top:20px;border-top:1px solid var(--border-mid);">
        <p style="font-size:.85rem;font-weight:600;color:var(--muted);margin-bottom:8px;">Rate this recipe</p>
        <div class="star-rating" id="star-rating" data-type="recipe" data-id="<?= $recipe['id'] ?>">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="star <?= $i <= $user_rating ? 'active' : '' ?>" data-val="<?= $i ?>">&#9733;</span>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (is_logged_in()): ?>
    <div style="margin-top:16px;">
        <button class="btn-primary fav-btn" data-type="recipe" data-id="<?= $recipe['id'] ?>" style="font-size:.82rem;padding:6px 16px;">
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
            method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({type:type, id:id, rating:val})
        }).then(r=>r.json()).then(d=>{
            if (d.ok) document.querySelectorAll('#star-rating .star').forEach(s=>{
                s.classList.toggle('active', parseInt(s.dataset.val) <= parseInt(val));
            });
        });
    });
});

document.querySelectorAll('.fav-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var self = this;
        fetch('/api/favourite.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({type: self.dataset.type, id: self.dataset.id})
        }).then(function(r){ return r.json(); }).then(function(d){
            if (d.ok) {
                self.textContent = d.saved ? '♥ Saved' : '♡ Save to favourites';
                self.classList.toggle('fav-saved', d.saved);
            }
        });
    });
});
</script>

<?php require 'includes/footer.php'; ?>
