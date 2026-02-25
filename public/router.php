<?php

/**
 * PHP CLI built-in server router script.
 *
 * When using `php -S`, the router is called for every request.
 * Returning false tells PHP to serve the file directly (static assets).
 * Otherwise we boot the Laravel application as normal.
 */
if (php_sapi_name() === 'cli-server') {
    $uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

    // Serve existing static files (CSS, JS, images, etc.) directly
    if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
        return false;
    }
}

require __DIR__ . '/index.php';
