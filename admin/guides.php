<?php
require '../includes/config.php';
require '../includes/auth.php';
require '../includes/functions.php';
require_backend();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!csrf_verify()) die('Invalid token');
    $id = (int)$_POST['id'];
    $st = $db->prepare("SELECT featured_image FROM guides WHERE id=?");
    $st->bind_param('i', $id); $st->execute();
    $row = $st->get_result()->fetch_assoc(); $st->close();
    if ($row['featured_image']) @unlink(dirname(__DIR__) . '/uploads/' . $row['featured_image']);
    $del = $db->prepare("DELETE FROM guides WHERE id=?");
    $del->bind_param('i', $id); $del->execute(); $del->close();
    header('Location: guides.php'); exit;
}

$guides = $db->query("
    SELECT g.id, g.title, g.place_name, g.location, g.dish, g.price_range, g.score, g.status, g.created_at,
           u.username AS author
    FROM guides g
    JOIN users u ON g.author_id = u.id
    ORDER BY g.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8"><title>Guides — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <script>var t=localStorage.getItem('theme');if(t)document.documentElement.setAttribute('data-theme',t);</script>
</head>
<body style="background:var(--bg);">
<div class="admin-header">
    <span class="admin-title">Guides</span>
    <div style="display:flex;gap:20px;"><a href="index.php">Dashboard</a><a href="add_guide.php">+ New Guide</a><a href="/admin/logout.php">Logout</a></div>
</div>
<div class="admin-wrap">
    <table>
        <thead><tr><th>Title</th><th>Place</th><th>Location</th><th>Dish</th><th>Price</th><th>Score</th><th>Author</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php while ($g = $guides->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($g['title']) ?></td>
                <td><?= htmlspecialchars($g['place_name']) ?></td>
                <td><?= htmlspecialchars($g['location']) ?></td>
                <td><?= htmlspecialchars($g['dish']) ?></td>
                <td><?= htmlspecialchars($g['price_range'] ?? '—') ?></td>
                <td><?= $g['score'] ? str_repeat('★', $g['score']) : '—' ?></td>
                <td><?= htmlspecialchars($g['author']) ?></td>
                <td><?= $g['status'] ?></td>
                <td><?= date('M j, Y', strtotime($g['created_at'])) ?></td>
                <td>
                    <a href="edit_guide.php?id=<?= $g['id'] ?>" class="btn-sm btn-edit">Edit</a>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this guide?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $g['id'] ?>">
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
