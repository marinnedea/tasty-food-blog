<?php
require '../includes/config.php';
require '../includes/auth.php';
require '../includes/functions.php';
require_backend();

$id = (int)($_GET['id'] ?? 0);
$st = $db->prepare("SELECT * FROM guides WHERE id=?");
$st->bind_param('i', $id); $st->execute();
$guide = $st->get_result()->fetch_assoc(); $st->close();
if (!$guide) { header('Location: guides.php'); exit; }

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

    if (!$title || !$place_name || !$location || !$dish || !$content) {
        $error = 'Title, place, location, dish and content are required.';
    } else {
        $slug = unique_slug($db, 'guides', $title, $id);
        $img  = $guide['featured_image'];
        if (!empty($_FILES['featured_image']['name'])) {
            $new = save_image($_FILES['featured_image'], dirname(__DIR__) . '/uploads/');
            if ($new) { if ($img) @unlink(dirname(__DIR__) . '/uploads/' . $img); $img = $new; }
        }
        if (isset($_POST['remove_image']) && $img) { @unlink(dirname(__DIR__) . '/uploads/' . $img); $img = null; }
        $upd = $db->prepare("UPDATE guides SET title=?,slug=?,place_name=?,location=?,dish=?,price_range=?,score=?,excerpt=?,content=?,featured_image=?,status=? WHERE id=?");
        $upd->bind_param('ssssssississi', $title, $slug, $place_name, $location, $dish, $price_range, $score, $excerpt, $content, $img, $status, $id);
        $upd->execute(); $upd->close();
        header('Location: guides.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8"><title>Edit Guide — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <script>var t=localStorage.getItem('theme');if(t)document.documentElement.setAttribute('data-theme',t);</script>
</head>
<body style="background:var(--bg);">
<div class="admin-header">
    <span class="admin-title">Edit Guide</span>
    <div style="display:flex;gap:20px;"><a href="guides.php">All Guides</a><a href="index.php">Dashboard</a><a href="/admin/logout.php">Logout</a></div>
</div>
<div class="admin-wrap">
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="admin-card">
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" value="<?= htmlspecialchars($guide['title']) ?>" required>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label>Place name</label>
                    <input type="text" name="place_name" value="<?= htmlspecialchars($guide['place_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" value="<?= htmlspecialchars($guide['location']) ?>" required>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label>Dish</label>
                    <input type="text" name="dish" value="<?= htmlspecialchars($guide['dish']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Price range</label>
                    <select name="price_range">
                        <?php foreach (['€','€€','€€€'] as $p): ?>
                            <option value="<?= $p ?>" <?= $guide['price_range']===$p?'selected':'' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Score</label>
                    <select name="score">
                        <option value="">— None —</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>" <?= $guide['score']==$i?'selected':'' ?>><?= str_repeat('★',$i) ?><?= str_repeat('☆',5-$i) ?> — <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Excerpt</label>
                <textarea name="excerpt" style="min-height:70px;"><?= htmlspecialchars($guide['excerpt']) ?></textarea>
            </div>
            <div class="form-group">
                <label>Content</label>
                <textarea name="content" id="content"><?= htmlspecialchars($guide['content']) ?></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <?php if ($guide['featured_image']): ?>
                <div class="form-group">
                    <label>Current image</label>
                    <img src="/uploads/<?= htmlspecialchars($guide['featured_image']) ?>" style="max-width:240px;border-radius:6px;display:block;margin-bottom:8px;">
                    <label style="font-weight:400;"><input type="checkbox" name="remove_image"> Remove image</label>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label><?= $guide['featured_image'] ? 'Replace image' : 'Featured image' ?></label>
                    <input type="file" name="featured_image" accept="image/*">
                </div>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="draft" <?= $guide['status']==='draft'?'selected':'' ?>>Draft</option>
                    <option value="published" <?= $guide['status']==='published'?'selected':'' ?>>Published</option>
                </select>
            </div>
            <button type="submit" class="btn-primary">Save changes</button>
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
