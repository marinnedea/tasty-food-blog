<?php

function sanitize_html(string $html): string {
    $allowed = implode('', [
        '<p><br><strong><b><em><i><u><s>',
        '<h2><h3><h4><h5><h6>',
        '<ul><ol><li><blockquote><pre><code><hr>',
        '<a><img><figure><figcaption>',
        '<table><thead><tbody><tfoot><tr><th><td>',
        '<div><span>',
    ]);
    return strip_tags($html, $allowed);
}

function excerpt(string $html, int $length = 200): string {
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return mb_strimwidth(trim($text), 0, $length, '…');
}

function read_time(string $html): int {
    $words = str_word_count(strip_tags($html));
    return max(1, (int) round($words / 200));
}

function slugify(string $text): string {
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^\w\s-]/u', '', $text);
    $text = preg_replace('/[\s_]+/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

function unique_slug(mysqli $db, string $table, string $base, ?int $exclude_id = null): string {
    $slug = slugify($base);
    $i    = 0;
    do {
        $try   = $i === 0 ? $slug : $slug . '-' . $i;
        $query = "SELECT id FROM $table WHERE slug=?";
        if ($exclude_id) $query .= " AND id != $exclude_id";
        $st = $db->prepare($query);
        $st->bind_param('s', $try);
        $st->execute();
        $exists = $st->get_result()->num_rows > 0;
        $st->close();
        $i++;
    } while ($exists);
    return $try;
}

function save_image(array $file, string $upload_dir): ?string {
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if (!in_array(mime_content_type($file['tmp_name']), $allowed)) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null;
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid('img_', true) . '.' . $ext;
    return move_uploaded_file($file['tmp_name'], $upload_dir . $filename) ? $filename : null;
}

/**
 * Replace [recipe:ID] shortcodes in post content with an embedded recipe card.
 */
function render_shortcodes(string $content, mysqli $db): string {
    return preg_replace_callback('/\[recipe:(\d+)\]/', function ($m) use ($db) {
        $id = (int) $m[1];
        $st = $db->prepare("SELECT id, title, slug, featured_image, prep_time, cook_time, servings, difficulty FROM recipes WHERE id=? AND status='published'");
        $st->bind_param('i', $id);
        $st->execute();
        $r = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$r) return '';
        $img = $r['featured_image']
            ? '<img src="/uploads/' . htmlspecialchars($r['featured_image']) . '" alt="' . htmlspecialchars($r['title']) . '">'
            : '';
        $meta = [];
        if ($r['prep_time']) $meta[] = 'Prep: ' . $r['prep_time'] . ' min';
        if ($r['cook_time']) $meta[] = 'Cook: ' . $r['cook_time'] . ' min';
        if ($r['servings'])  $meta[] = 'Serves: ' . $r['servings'];
        $meta_html = $meta ? '<p class="recipe-card-meta">' . implode(' &middot; ', $meta) . '</p>' : '';
        return '<div class="recipe-card-embed">' .
                   ($img ? '<a href="/recipe/' . htmlspecialchars($r['slug']) . '">' . $img . '</a>' : '') .
                   '<div class="recipe-card-body">' .
                       '<h4><a href="/recipe/' . htmlspecialchars($r['slug']) . '">' . htmlspecialchars($r['title']) . '</a></h4>' .
                       $meta_html .
                       '<a href="/recipe/' . htmlspecialchars($r['slug']) . '" class="btn-readmore">View Recipe</a>' .
                   '</div>' .
               '</div>';
    }, $content);
}

function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . ' min ago';
    if ($diff < 86400)  return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M j, Y', strtotime($datetime));
}

function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_verify(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}
