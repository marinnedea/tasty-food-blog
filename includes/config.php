<?php

// ============================================================
// Database — set via environment variables.
// Apache VirtualHost:
//   SetEnv DB_HOST localhost
//   SetEnv DB_USER tfbuser
//   SetEnv DB_PASS secret
//   SetEnv DB_NAME tfb
//
// Nginx PHP-FPM pool (/etc/php/x.x/fpm/pool.d/app.conf):
//   env[DB_HOST] = localhost
//   env[DB_USER] = tfbuser
//   env[DB_PASS] = secret
//   env[DB_NAME] = tfb
// ============================================================
$db = new mysqli(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
if ($db->connect_error) die('Database connection failed: ' . $db->connect_error);
$db->set_charset('utf8mb4');


// ============================================================
// Site settings
// ============================================================
define('SITE_TITLE',    'Discover Tasty Food');
define('SITE_SUBTITLE', 'Honest food writing.');
define('SITE_FOOTER',   'Discover Tasty Food &copy; ' . date('Y'));
define('SITE_LOGO',     '');       // e.g. 'uploads/logo.png'
define('SITE_FAVICON',  '');       // e.g. 'uploads/favicon.ico'

// ============================================================
// TinyMCE — get a free key at https://www.tiny.cloud/
// ============================================================
define('TINYMCE_API_KEY', 'no-api-key');

// ============================================================
// Homepage category chips — set false to hide
// ============================================================
define('SHOW_CATEGORY_CHIPS', true);
