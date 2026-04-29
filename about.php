<?php
require 'includes/config.php';
require 'includes/functions.php';
require 'includes/auth.php';
auth_start();

$page_title = 'About — ' . SITE_TITLE;
$full_width = true;
require 'includes/header.php';
?>
<main class="main-content">
    <article class="post-content">
        <h1 style="font-family:var(--font-display);font-size:2rem;margin-bottom:8px;">About</h1>
        <p style="color:var(--muted);font-size:.9rem;margin-bottom:28px;border-bottom:1px solid var(--border-mid);padding-bottom:20px;">
            Honest food writing since <?= date('Y') ?>.
        </p>

        <p>Welcome to <strong><?= SITE_TITLE ?></strong> — a place for straightforward, honest writing about food.
        No paid promotions. No fluff. Just real recipes, real opinions, and a genuine love of eating well.</p>

        <p>We believe great food doesn't need to be complicated. Whether it's a quick weeknight pasta, a slow weekend
        roast, or the perfect street-side snack, every dish has a story worth telling.</p>

        <h2>What you'll find here</h2>
        <ul>
            <li><strong>Recipes</strong> — tested at home, written clearly, with honest notes on what works and what doesn't.</li>
            <li><strong>Posts</strong> — thoughts on ingredients, techniques, and the culture around food.</li>
            <li><strong>Reviews</strong> — restaurants, products, and everything in between, written without an agenda.</li>
        </ul>

        <h2>Get in touch</h2>
        <p>Have a question, a recipe suggestion, or just want to say hello?
        <a href="/contact">Drop us a message</a> — we read everything.</p>
    </article>
</main>
<?php require 'includes/footer.php'; ?>
