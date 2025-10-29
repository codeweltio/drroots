<?php
// Built-in server router to support /api/* and sitemap/robots when running locally.

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$file = __DIR__ . $uri;

// If the requested path matches a real file, let the server handle it (CSS/JS/images/etc.)
if ($uri !== '/' && file_exists($file) && is_file($file)) {
    return false; // serve the requested resource as-is
}

// Route API and utility endpoints to PHP API router
if (preg_match('#^/api/.*$#', $uri) || $uri === '/sitemap.xml' || $uri === '/robots.txt') {
    require __DIR__ . '/api/index.php';
    return true;
}

// Admin console routes
if ($uri === '/admin' || preg_match('#^/admin/.*$#', $uri)) {
    require __DIR__ . '/admin/index.php';
    return true;
}

// Static policy pages
if ($uri === '/privacy' || $uri === '/terms') {
    $file = __DIR__ . ($uri === '/privacy' ? '/privacy.html' : '/terms.html');
    if (is_file($file)) {
        readfile($file);
        return true;
    }
}

// Fallback: serve index.html (SPA-style)
readfile(__DIR__ . '/index.html');
return true;
