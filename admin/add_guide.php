<?php
require '../includes/config.php';
require '../includes/auth.php';
require '../includes/functions.php';
require_backend();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) die('Invalid token');
    $title       = trim($_POST['title']      ?? '');
    $place_name  = trim($_POST['place_name'] ?? '');
    $location    = trim($_POST['location']   ?? '');
    $dish        = trim($_POST['dish']       ?? '');
    $price_range = in_array($_POST['price_range'] ?? '', ['€','€€','€€€']) ? $_POST['price_range'] : '€€';
    $score       = (int)($_POST['score'] ?? 0) ?: null;
    $excerpt     = trim($_POST['excerpt']    ?? '');
    $content     = sanitize_html($_POST['content'] ?? '');
    $status      = $_POST['status'] === 'published' ? 'published' : 'draft';
    $author      = current_user()['id'];

    if (!$title || !$place_name || !$location || !$dish || !$content) {
        $error = 'Title, place, location, dish and content are required.';
    } else {
        $slug = unique_slug($db, 'guides', $title);
        $img  = null;
        if (!empty($_FILES['featured_image']['name'])) {
            $img = save_image($_FILES['featured_image'], dirname(__DIR__) . '/uploads/');
        }
        $st = $db->prepare("INSERT INTO guides (title,slug,place_name,location,dish,price_range,score,excerpt,content,featured_image,author_id,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $st->bind_param('ssssssississs', $title, $slug, $place_name, $location, $dish, $price_range, $score, $excerpt, $content, $img, $author, $status);
        $st->execute(); $st->close();
        header('Location: guides.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8"><title>New Guide — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <script>var t=localStorage.getItem('theme');if(t)document.documentElement.setAttribute('data-theme',t);</script>
</head>
<body style="background:var(--bg);">
<div class="admin-header">
    <span class="admin-title">New Guide</span>
    <div style="display:flex;gap:20px;"><a href="guides.php">All Guides</a><a href="index.php">Dashboard</a><a href="/admin/logout.php">Logout</a></div>
</div>
<div class="admin-wrap">
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="admin-card">
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" required placeholder="e.g. Tonkotsu Ramen at Ippudo">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label>Place name</label>
                    <input type="text" name="place_name" required placeholder="e.g. Ippudo">
                </div>
                <div class="form-group">
                    <label>Location <small style="font-weight:400;color:var(--muted)">(links to Google Maps)</small></label>
                    <input type="text" name="location" required placeholder="e.g. New York, USA">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label>Dish</label>
                    <input type="text" name="dish" required placeholder="e.g. Tonkotsu Ramen">
                </div>
                <div class="form-group">
                    <label>Price range</label>
                    <select name="price_range">
                        <option value="€">€ — Budget</option>
                        <option value="€€" selected>€€ — Mid-range</option>
                        <option value="€€€">€€€ — Pricey</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Score <small style="font-weight:400;color:var(--muted)">(your rating)</small></label>
                    <select name="score">
                        <option value="">— None —</option>
                        <option value="1">★☆☆☆☆ — 1</option>
                        <option value="2">★★☆☆☆ — 2</option>
                        <option value="3">★★★☆☆ — 3</option>
                        <option value="4">★★★★☆ — 4</option>
                        <option value="5">★★★★★ — 5</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Excerpt <small style="font-weight:400;color:var(--muted)">(shown on listing page)</small></label>
                <textarea name="excerpt" style="min-height:70px;" placeholder="A short teaser — 1 or 2 sentences."></textarea>
            </div>
            <div class="form-group">
                <label>Content</label>
                <textarea name="content" id="content"></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label>Featured image</label>
                    <input type="file" name="featured_image" accept="image/*">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn-primary">Save guide</button>
        </form>
    </div>
</div>
<script src="https://cdn.tiny.cloud/1/<?= TINYMCE_API_KEY ?>/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#content',
    plugins: 'lists link image code table',
    toolbar: 'undo redo | blocks | bold italic | bullist numlist | link image | code',
    images_upload_url: '/admin/upload_image.php',
    automatic_uploads: true,
    height: 420,
    skin: localStorage.getItem('theme') === 'dark' ? 'oxide-dark' : 'oxide',
    content_css: localStorage.getItem('theme') === 'dark' ? 'dark' : 'default',
});
</script>
</body></html>
