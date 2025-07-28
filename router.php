<?php
// Display all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug logging function
function log_debug($message) {
    file_put_contents('router_debug.log', date('[Y-m-d H:i:s]') . " $message\n", FILE_APPEND);
}

log_debug("=== NEW REQUEST ===");
log_debug("REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? '/'));

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$uri = rtrim($uri, '/');
$uri = ($uri === '') ? '/' : $uri;

log_debug("Processing URI: '$uri'");

// Routing table for custom endpoints
$routes = [
    // Auth routes
    '/login'             => '/backend/auth/login.php',
    '/logout'            => '/backend/auth/logout.php',
    '/register'          => '/backend/auth/create_admin.php',
    '/forgot-password'   => '/backend/reset/forgot_password.php',
    '/reset-password'    => '/backend/reset/reset_password.php',
    '/verify-otp'        => '/backend/reset/verify_otp.php',
    
    // Admin and user routes
    '/admin'             => '/backend/admin/admin.php',
    '/upload'            => '/backend/upload/Upload_Files.php',
    '/submit'            => '/backend/upload/submit.php',
    '/users'             => '/backend/admin/Users_list.php',
    '/admin-users'       => '/backend/admin/admin_users.php',
 
    // Static HTML pages
    '/'                  => '/public/index.html',
    '/about'             => '/public/pages/about.html',
    '/contact'           => '/public/pages/contact.html',
    '/sectors'           => '/public/pages/sectors.html',
    '/services'          => '/public/pages/services.html',
    '/thank-you'         => '/public/pages/thank-you.html',
    '/error'             => '/public/pages/error.html',
];

// Handle routes to PHP/HTML pages
if (array_key_exists($uri, $routes)) {
    $filePath = __DIR__ . $routes[$uri];
    log_debug("Attempting to load: $filePath");

    if (file_exists($filePath)) {
        log_debug("File found - including: $filePath");
        require $filePath;
        exit;
    } else {
        log_debug("ERROR: File not found - $filePath");
        http_response_code(500);
        require __DIR__ . '/public/pages/error.html';
        exit;
    }
}

// Serve static assets (CSS, JS, images, etc.)
$staticFile = realpath(__DIR__ . '/public' . $uri);
$publicRoot = realpath(__DIR__ . '/public');

log_debug("Static file check - Requested: " . __DIR__ . '/public' . $uri);
log_debug("Static file check - Resolved: " . ($staticFile ?: 'NOT FOUND'));

if (
    $staticFile &&
    str_starts_with($staticFile, $publicRoot) &&
    is_file($staticFile)
) {
    log_debug("Serving static file: $staticFile");

    // Get mime type using fallback method
    $ext = pathinfo($staticFile, PATHINFO_EXTENSION);
    $mimeFallbacks = [
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'svg'   => 'image/svg+xml',
        'gif'   => 'image/gif',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'eot'   => 'application/vnd.ms-fontobject',
        'json'  => 'application/json',
        'pdf'   => 'application/pdf',
    ];

    $mimeType = mime_content_type($staticFile);
    if (!$mimeType || $mimeType === 'text/plain') {
        $mimeType = $mimeFallbacks[$ext] ?? 'application/octet-stream';
    }

    header('Content-Type: ' . $mimeType);
    readfile($staticFile);
    exit;
}

// No route matched → show 404
log_debug("404 - No route found for: $uri");
http_response_code(404);
require __DIR__ . '/public/pages/error.html';
