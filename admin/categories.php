<?php
require '../includes/config.php';
require '../includes/auth.php';
require '../includes/functions.php';
require_backend();

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) die('Invalid token');
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name  = trim($_POST['name'] ?? '');
        $color = trim($_POST['color'] ?? '#6FB241');
        if ($name) {
            $slug = unique_slug($db, 'categories', $name);
            $st   = $db->prepare("INSERT INTO categories (name, slug, color) VALUES (?,?,?)");
            $st->bind_param('sss', $name, $slug, $color); $st->execute(); $st->close();
            $success = 'Category added.';
        }
    } elseif ($action === 'edit') {
        $id    = (int)$_POST['id'];
        $name  = trim($_POST['name'] ?? '');
        $color = trim($_POST['color'] ?? '#6FB241');
        if ($name && $id) {
            $slug = unique_slug($db, 'categories', $name, $id);
            $st   = $db->prepare("UPDATE categories SET name=?,slug=?,color=? WHERE id=?");
            $st->bind_param('sssi', $name, $slug, $color, $id); $st->execute(); $st->close();
            $success = 'Category updated.';
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $st = $db->prepare("DELETE FROM categories WHERE id=?");
        $st->bind_param('i', $id); $st->execute(); $st->close();
        $success = 'Category deleted. Related posts/recipes now have no category.';
    }
}

$cats = $db->query("
    SELECT c.*, COUNT(p.id) AS post_count, COUNT(r.id) AS recipe_count
    FROM categories c
    LEFT JOIN posts p ON p.category_id=c.id
    LEFT JOIN recipes r ON r.category_id=c.id
    GROUP BY c.id ORDER BY c.id
");
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8"><title>Categories — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <script>var t=localStorage.getItem('theme');if(t)document.documentElement.setAttribute('data-theme',t);</script>
</head>
<body style="background:var(--bg);">
<div class="admin-header">
    <span class="admin-title">Categories</span>
    <div style="display:flex;gap:20px;"><a href="index.php">Dashboard</a><a href="/admin/logout.php">Logout</a></div>
</div>
<div class="admin-wrap">
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="admin-card" style="margin-bottom:24px;">
        <h2 style="font-size:1rem;margin-bottom:16px;">Add category</h2>
        <form method="post" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <div class="form-group" style="margin:0;flex:1;min-width:160px;">
                <label>Name</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group" style="margin:0;">
                <label>Colour</label>
                <input type="color" name="color" value="#6FB241" style="height:40px;padding:2px;border-radius:6px;border:1px solid var(--border-mid);cursor:pointer;">
            </div>
            <button type="submit" class="btn-primary">Add</button>
        </form>
    </div>

    <table>
        <thead><tr><th>Name</th><th>Slug</th><th>Colour</th><th>Posts</th><th>Recipes</th><th>Actions</th></tr></thead>
        <tbody>
        <?php while ($cat = $cats->fetch_assoc()): ?>
            <tr>
                <td>
                    <form method="post" style="display:flex;gap:8px;align-items:center;">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="text" name="name" value="<?= htmlspecialchars($cat['name']) ?>" style="width:130px;padding:5px 8px;font-size:.85rem;border:1px solid var(--border-mid);border-radius:4px;background:var(--bg);color:var(--ink);">
                        <input type="color" name="color" value="<?= htmlspecialchars($cat['color']) ?>" style="height:32px;padding:2px;border-radius:4px;border:1px solid var(--border-mid);cursor:pointer;">
                        <button type="submit" class="btn-sm btn-edit">Save</button>
                    </form>
                </td>
                <td style="color:var(--muted);font-size:.82rem;"><?= htmlspecialchars($cat['slug']) ?></td>
                <td><span style="display:inline-block;width:20px;height:20px;border-radius:4px;background:<?= htmlspecialchars($cat['color']) ?>"></span></td>
                <td><?= $cat['post_count'] ?></td>
                <td><?= $cat['recipe_count'] ?></td>
                <td>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete category? Posts/recipes will keep their data but lose this category.')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <button type="submit" class="btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body></html>
