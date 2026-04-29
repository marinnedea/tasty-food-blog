<?php
require '../includes/config.php';
require '../includes/auth.php';
require '../includes/functions.php';
require_backend();

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
    $author      = current_user()['id'];

    if (!$title || !$ingredients || !$steps) { $error = 'Title, ingredients and steps are required.'; }
    else {
        $slug = unique_slug($db, 'recipes', $title);
        $img  = null;
        if (!empty($_FILES['featured_image']['name'])) {
            $img = save_image($_FILES['featured_image'], dirname(__DIR__) . '/uploads/');
        }
        $st = $db->prepare("INSERT INTO recipes (title,slug,featured_image,description,prep_time,cook_time,servings,difficulty,ingredients,steps,category_id,author_id,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $st->bind_param('ssssiiiissiis', $title, $slug, $img, $description, $prep_time, $cook_time, $servings, $difficulty, $ingredients, $steps, $cat_id, $author, $status);
        $st->execute(); $st->close();
        header('Location: recipes.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8"><title>New Recipe — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <script>var t=localStorage.getItem('theme');if(t)document.documentElement.setAttribute('data-theme',t);</script>
</head>
<body style="background:var(--bg);">
<div class="admin-header">
    <span class="admin-title">New Recipe</span>
    <div style="display:flex;gap:20px;"><a href="recipes.php">All Recipes</a><a href="index.php">Dashboard</a><a href="/admin/logout.php">Logout</a></div>
</div>
<div class="admin-wrap">
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="admin-card">
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" required>
            </div>
            <div class="form-group">
                <label>Description <small style="font-weight:400;color:var(--muted)">(short intro)</small></label>
                <textarea name="description" style="min-height:80px;"></textarea>
            </div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;">
                <div class="form-group">
                    <label>Prep time (min)</label>
                    <input type="number" name="prep_time" min="0">
                </div>
                <div class="form-group">
                    <label>Cook time (min)</label>
                    <input type="number" name="cook_time" min="0">
                </div>
                <div class="form-group">
                    <label>Servings</label>
                    <input type="number" name="servings" min="1">
                </div>
                <div class="form-group">
                    <label>Difficulty</label>
                    <select name="difficulty">
                        <option value="easy">Easy</option>
                        <option value="medium" selected>Medium</option>
                        <option value="hard">Hard</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Ingredients <small style="font-weight:400;color:var(--muted)">(one per line)</small></label>
                <textarea name="ingredients" style="min-height:160px;" placeholder="2 cups flour&#10;1 tsp salt&#10;..."></textarea>
            </div>
            <div class="form-group">
                <label>Steps <small style="font-weight:400;color:var(--muted)">(one per line)</small></label>
                <textarea name="steps" id="steps" style="min-height:200px;" placeholder="Preheat oven to 180°C.&#10;Mix dry ingredients.&#10;..."></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id">
                        <option value="">— None —</option>
                        <?php while ($c = $cats->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Featured image</label>
                <input type="file" name="featured_image" accept="image/*">
            </div>
            <button type="submit" class="btn-primary">Save recipe</button>
        </form>
    </div>
</div>
<script src="https://cdn.tiny.cloud/1/<?= TINYMCE_API_KEY ?>/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#steps',
    plugins: 'lists',
    toolbar: 'bold italic | bullist numlist',
    menubar: false,
    height: 260,
    skin: localStorage.getItem('theme') === 'dark' ? 'oxide-dark' : 'oxide',
    content_css: localStorage.getItem('theme') === 'dark' ? 'dark' : 'default',
});
</script>
</body></html>
