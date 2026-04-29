<?php
require '../includes/config.php';
require '../includes/auth.php';
require '../includes/functions.php';
require_backend();

$id = (int)($_GET['id'] ?? 0);
$st = $db->prepare("SELECT * FROM recipes WHERE id=?");
$st->bind_param('i', $id); $st->execute();
$recipe = $st->get_result()->fetch_assoc(); $st->close();
if (!$recipe) { header('Location: recipes.php'); exit; }

$error = '';
$cats  = $db->query("SELECT id, name FROM categories ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) die('Invalid token');
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $ingredients = trim($_POST['ingredients'] ?? '');
    $steps       = trim($_POST['steps'] ?? '');
    $prep_time   = (int)($_POST['prep_time'] ?? 0) ?: null;
    $cook_time   = (int)($_POST['cook_time'] ?? 0) ?: null;
    $servings    = (int)($_POST['servings'] ?? 0) ?: null;
    $difficulty  = in_array($_POST['difficulty'] ?? '', ['easy','medium','hard']) ? $_POST['difficulty'] : 'medium';
    $cat_id      = (int)($_POST['category_id'] ?? 0) ?: null;
    $status      = $_POST['status'] === 'published' ? 'published' : 'draft';

    if (!$title || !$ingredients || !$steps) { $error = 'Title, ingredients and steps are required.'; }
    else {
        $slug = unique_slug($db, 'recipes', $title, $id);
        $img  = $recipe['featured_image'];
        if (!empty($_FILES['featured_image']['name'])) {
            $new = save_image($_FILES['featured_image'], dirname(__DIR__) . '/uploads/');
            if ($new) { if ($img) @unlink(dirname(__DIR__) . '/uploads/' . $img); $img = $new; }
        }
        if (isset($_POST['remove_image']) && $img) { @unlink(dirname(__DIR__) . '/uploads/' . $img); $img = null; }
        $upd = $db->prepare("UPDATE recipes SET title=?,slug=?,featured_image=?,description=?,prep_time=?,cook_time=?,servings=?,difficulty=?,ingredients=?,steps=?,category_id=?,status=? WHERE id=?");
        $upd->bind_param('ssssiiiissisi', $title, $slug, $img, $description, $prep_time, $cook_time, $servings, $difficulty, $ingredients, $steps, $cat_id, $status, $id);
        $upd->execute(); $upd->close();
        header('Location: recipes.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8"><title>Edit Recipe — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <script>var t=localStorage.getItem('theme');if(t)document.documentElement.setAttribute('data-theme',t);</script>
</head>
<body style="background:var(--bg);">
<div class="admin-header">
    <span class="admin-title">Edit Recipe</span>
    <div style="display:flex;gap:20px;"><a href="recipes.php">All Recipes</a><a href="index.php">Dashboard</a><a href="/admin/logout.php">Logout</a></div>
</div>
<div class="admin-wrap">
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="admin-card">
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <div class="form-group"><label>Title</label><input type="text" name="title" value="<?= htmlspecialchars($recipe['title']) ?>" required></div>
            <div class="form-group"><label>Description</label><textarea name="description" style="min-height:80px;"><?= htmlspecialchars($recipe['description']) ?></textarea></div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;">
                <div class="form-group"><label>Prep time (min)</label><input type="number" name="prep_time" value="<?= $recipe['prep_time'] ?>" min="0"></div>
                <div class="form-group"><label>Cook time (min)</label><input type="number" name="cook_time" value="<?= $recipe['cook_time'] ?>" min="0"></div>
                <div class="form-group"><label>Servings</label><input type="number" name="servings" value="<?= $recipe['servings'] ?>" min="1"></div>
                <div class="form-group">
                    <label>Difficulty</label>
                    <select name="difficulty">
                        <?php foreach (['easy','medium','hard'] as $d): ?>
                            <option value="<?= $d ?>" <?= $recipe['difficulty']===$d?'selected':'' ?>><?= ucfirst($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Ingredients (one per line)</label><textarea name="ingredients" style="min-height:160px;"><?= htmlspecialchars($recipe['ingredients']) ?></textarea></div>
            <div class="form-group"><label>Steps (one per line)</label><textarea name="steps" id="steps" style="min-height:200px;"><?= htmlspecialchars($recipe['steps']) ?></textarea></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id">
                        <option value="">— None —</option>
                        <?php while ($c = $cats->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>" <?= $c['id']==$recipe['category_id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="draft" <?= $recipe['status']==='draft'?'selected':'' ?>>Draft</option>
                        <option value="published" <?= $recipe['status']==='published'?'selected':'' ?>>Published</option>
                    </select>
                </div>
            </div>
            <?php if ($recipe['featured_image']): ?>
                <div class="form-group">
                    <label>Current image</label>
                    <img src="/uploads/<?= htmlspecialchars($recipe['featured_image']) ?>" style="max-width:240px;border-radius:6px;display:block;margin-bottom:8px;">
                    <label style="font-weight:400;"><input type="checkbox" name="remove_image"> Remove image</label>
                </div>
            <?php endif; ?>
            <div class="form-group"><label>Replace image</label><input type="file" name="featured_image" accept="image/*"></div>
            <button type="submit" class="btn-primary">Save changes</button>
        </form>
    </div>
</div>
<script src="https://cdn.tiny.cloud/1/<?= TINYMCE_API_KEY ?>/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#steps', plugins: 'lists',
    toolbar: 'bold italic | bullist numlist', menubar: false, height: 260,
    skin: localStorage.getItem('theme') === 'dark' ? 'oxide-dark' : 'oxide',
    content_css: localStorage.getItem('theme') === 'dark' ? 'dark' : 'default',
});
</script>
</body></html>
