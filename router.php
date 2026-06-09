<?php
/**
 * Router script for PHP built-in development server.
 * Mimics the .htaccess rewrite rules: serves static files directly,
 * routes everything else through index.php.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// If the request is for an actual file (not a directory), serve it directly
if ($uri !== '/' && is_file(__DIR__ . $uri)) {
    return false;
}

// Otherwise, route through index.php
require_once __DIR__ . '/index.php';
