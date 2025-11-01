<?php
// /artisan-web.php — TEMP helper. Delete after use.
$token = 'Tok-Only-Once-1u6Qp9L'; // <- keep your token or change as you like
if (!isset($_GET['token']) || $_GET['token'] !== $token) {
    http_response_code(403);
    exit('Forbidden');
}

chdir(__DIR__ . '/..'); // go to Laravel root where artisan is
$cmd  = $_GET['cmd'] ?? 'about';
$php  = PHP_BINARY ?: 'php';
$full = $php . ' artisan ' . escapeshellcmd($cmd) . ' 2>&1';

header('Content-Type: text/plain; charset=utf-8');
echo "$ $full\n\n";
passthru($full);