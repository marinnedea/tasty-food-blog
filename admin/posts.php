<?php
require '../includes/config.php';
require '../includes/auth.php';
require '../includes/functions.php';
require_backend();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!csrf_verify()) die('Invalid token');
    $id = (int)$_POST['id'];
    $st = $db->prepare("SELECT featured_image FROM posts WHERE id=?");
    $st->bind_param('i', $id); $st->execute();
    $row = $st->get_result()->fetch_assoc(); $st->close();
    if ($row['featured_image']) @unlink(dirname(__DIR__) . '/uploads/' . $row['featured_image']);
    $db->prepare("DELETE FROM posts WHERE id=?")->execute([$id] + ['' => $db->prepare("DELETE FROM posts WHERE id=?")->bind_param('i', $id)]);
    $del = $db->prepare("DELETE FROM posts WHERE id=?");
    $del->bind_param('i', $id); $del->execute(); $del->close();
    header('Location: posts.php'); exit;
}

$posts = $db->query("
    SELECT p.id, p.title, p.status, p.created_at, c.name AS cat, u.username AS author
    FROM posts p
    LEFT JOIN categories c ON p.category_id=c.id
    JOIN users u ON p.author_id=u.id
    ORDER BY p.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8"><title>Posts — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <script>var t=localStorage.getItem('theme');if(t)document.documentElement.setAttribute('data-theme',t);</script>
</head>
<body style="background:var(--bg);">
<div class="admin-header">
    <span class="admin-title">Posts</span>
    <div style="display:flex;gap:20px;"><a href="index.php">Dashboard</a><a href="add_post.php">+ New Post</a><a href="/admin/logout.php">Logout</a></div>
</div>
<div class="admin-wrap">
    <table>
        <thead><tr><th>Title</th><th>Category</th><th>Author</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php while ($p = $posts->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($p['title']) ?></td>
                <td><?= htmlspecialchars($p['cat'] ?? '—') ?></td>
                <td><?= htmlspecialchars($p['author']) ?></td>
                <td><?= $p['status'] ?></td>
                <td><?= date('M j, Y', strtotime($p['created_at'])) ?></td>
                <td>
                    <a href="edit_post.php?id=<?= $p['id'] ?>" class="btn-sm btn-edit">Edit</a>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this post?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
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
