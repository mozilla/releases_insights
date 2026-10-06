<?php

declare(strict_types=1);

use ReleaseInsights\Request;

// We import the Request class manually as we haven't autoloaded classes yet
include dirname(__DIR__, 2) . '/app/classes/ReleaseInsights/Request.php';

// Security headers are sent by PHP so that we don't have to keep the nginx and
// Apache configurations in sync. Sent first so that early redirects and 404s get them.
// Static files served directly by the web server don't get them, which is fine.
// The CSP needs a nonce and is sent in init.php.
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

$url = new Request(filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL));

// Real files and folders don't get pre-processed
if (file_exists($_SERVER['DOCUMENT_ROOT'] . $url->path) && $url->path !== '/') {
    return false;
}

// Don't process non-PHP files, even if they don't exist on the server
if (isset(pathinfo($url->path)['extension'])) {
    http_response_code(404);
    return false;
}

// Always redirect to an url ending with a single slash
if ($url->invalid_slashes) {
    header('Location:' . $url->path);
    exit;
}

// We can now initialize the application, load all dependencies and dispatch urls
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/init.php';
