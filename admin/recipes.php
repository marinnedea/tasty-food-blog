<?php
require '../includes/config.php';
require '../includes/auth.php';
require '../includes/functions.php';
require_backend();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!csrf_verify()) die('Invalid token');
    $id = (int)$_POST['id'];
    $st = $db->prepare("SELECT featured_image FROM recipes WHERE id=?");
    $st->bind_param('i', $id); $st->execute();
    $row = $st->get_result()->fetch_assoc(); $st->close();
    if ($row['featured_image']) @unlink(dirname(__DIR__) . '/uploads/' . $row['featured_image']);
    $del = $db->prepare("DELETE FROM recipes WHERE id=?");
    $del->bind_param('i', $id); $del->execute(); $del->close();
    header('Location: recipes.php'); exit;
}

$recipes = $db->query("
    SELECT r.id, r.title, r.status, r.difficulty, r.created_at, c.name AS cat, u.username AS author
    FROM recipes r
    LEFT JOIN categories c ON r.category_id=c.id
    JOIN users u ON r.author_id=u.id
    ORDER BY r.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8"><title>Recipes — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <script>var t=localStorage.getItem('theme');if(t)document.documentElement.setAttribute('data-theme',t);</script>
</head>
<body style="background:var(--bg);">
<div class="admin-header">
    <span class="admin-title">Recipes</span>
    <div style="display:flex;gap:20px;"><a href="index.php">Dashboard</a><a href="add_recipe.php">+ New Recipe</a><a href="/admin/logout.php">Logout</a></div>
</div>
<div class="admin-wrap">
    <table>
        <thead><tr><th>Title</th><th>Category</th><th>Difficulty</th><th>Author</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php while ($r = $recipes->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($r['title']) ?></td>
                <td><?= htmlspecialchars($r['cat'] ?? '—') ?></td>
                <td><?= ucfirst($r['difficulty']) ?></td>
                <td><?= htmlspecialchars($r['author']) ?></td>
                <td><?= $r['status'] ?></td>
                <td><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                <td>
                    <a href="edit_recipe.php?id=<?= $r['id'] ?>" class="btn-sm btn-edit">Edit</a>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this recipe?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
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
