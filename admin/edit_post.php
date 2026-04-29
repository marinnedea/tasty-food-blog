<?php
require '../includes/config.php';
require '../includes/auth.php';
require '../includes/functions.php';
require_backend();

$id   = (int)($_GET['id'] ?? 0);
$st   = $db->prepare("SELECT * FROM posts WHERE id=?");
$st->bind_param('i', $id); $st->execute();
$post = $st->get_result()->fetch_assoc(); $st->close();
if (!$post) { header('Location: posts.php'); exit; }

$error = '';
$cats  = $db->query("SELECT id, name FROM categories ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) die('Invalid token');
    $title   = trim($_POST['title'] ?? '');
    $content = sanitize_html($_POST['content'] ?? '');
    $cat_id  = (int)($_POST['category_id'] ?? 0) ?: null;
    $status  = $_POST['status'] === 'published' ? 'published' : 'draft';

    if (!$title || !$content) { $error = 'Title and content are required.'; }
    else {
        $slug = unique_slug($db, 'posts', $title, $id);
        $img  = $post['featured_image'];
        if (!empty($_FILES['featured_image']['name'])) {
            $new = save_image($_FILES['featured_image'], dirname(__DIR__) . '/uploads/');
            if ($new) { if ($img) @unlink(dirname(__DIR__) . '/uploads/' . $img); $img = $new; }
        }
        if (isset($_POST['remove_image']) && $img) {
            @unlink(dirname(__DIR__) . '/uploads/' . $img); $img = null;
        }
        $upd = $db->prepare("UPDATE posts SET title=?, slug=?, content=?, featured_image=?, category_id=?, status=? WHERE id=?");
        $upd->bind_param('ssssiisi', $title, $slug, $content, $img, $cat_id, $status, $id);

        // Fix bind: nullable int
        $upd = $db->prepare("UPDATE posts SET title=?,slug=?,content=?,featured_image=?,category_id=?,status=? WHERE id=?");
        $upd->bind_param('ssssisi', $title, $slug, $content, $img, $cat_id, $status, $id);
        $upd->execute(); $upd->close();
        header('Location: posts.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8"><title>Edit Post — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <script>var t=localStorage.getItem('theme');if(t)document.documentElement.setAttribute('data-theme',t);</script>
</head>
<body style="background:var(--bg);">
<div class="admin-header">
    <span class="admin-title">Edit Post</span>
    <div style="display:flex;gap:20px;"><a href="posts.php">All Posts</a><a href="index.php">Dashboard</a><a href="/admin/logout.php">Logout</a></div>
</div>
<div class="admin-wrap">
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="admin-card">
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" value="<?= htmlspecialchars($post['title']) ?>" required>
            </div>
            <div class="form-group">
                <label>Content</label>
                <textarea name="content" id="content"><?= htmlspecialchars($post['content']) ?></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id">
                        <option value="">— None —</option>
                        <?php while ($c = $cats->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>" <?= $c['id'] == $post['category_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="draft" <?= $post['status']==='draft'?'selected':'' ?>>Draft</option>
                        <option value="published" <?= $post['status']==='published'?'selected':'' ?>>Published</option>
                    </select>
                </div>
            </div>
            <?php if ($post['featured_image']): ?>
                <div class="form-group">
                    <label>Current featured image</label>
                    <img src="/uploads/<?= htmlspecialchars($post['featured_image']) ?>" style="max-width:240px;border-radius:6px;display:block;margin-bottom:8px;">
                    <label style="font-weight:400;"><input type="checkbox" name="remove_image"> Remove image</label>
                </div>
            <?php endif; ?>
            <div class="form-group">
                <label>Replace featured image</label>
                <input type="file" name="featured_image" accept="image/*">
            </div>
            <button type="submit" class="btn-primary">Save changes</button>
        </form>
    </div>
</div>
<script src="https://cdn.tiny.cloud/1/<?= TINYMCE_API_KEY ?>/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#content',
    plugins: 'lists link image code table codesample',
    toolbar: 'undo redo | blocks | bold italic | bullist numlist | link image | table | code',
    images_upload_url: '/admin/upload_image.php',
    automatic_uploads: true,
    height: 420,
    skin: localStorage.getItem('theme') === 'dark' ? 'oxide-dark' : 'oxide',
    content_css: localStorage.getItem('theme') === 'dark' ? 'dark' : 'default',
});
</script>
</body></html>
