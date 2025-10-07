<?php

declare(strict_types=1);

use function Sentry\captureLastError;
use Tracy\Debugger;

// This is our production CSP
$csp_headers = "Content-Security-Policy: default-src https:; object-src 'none'; base-uri 'self'; script-src 'self' 'nonce-" . NONCE ."'; style-src 'self' 'nonce-" . NONCE . "'; style-src-attr 'unsafe-inline'; frame-ancestors 'none'";

if (LOCALHOST) {
    // Error handler page is blocked by our production CSP rules
    $csp_headers = '';

    // Catch errors via Tracy library in dev mode only
    if (class_exists(Tracy\Debugger::class)) {
        Debugger::$strictMode = true;
        Debugger::$editor = 'subl://open?url=file://%file&line=%line';
        Debugger::enable();
     }
}

// Send HTTP security headers (not set by the server)
header('X-Content-Type-Options: nosniff');
header($csp_headers);

// Dispatch urls. The $url object is defined in router.php
$url->loadController();

// Send the last error to Sentry
captureLastError();

// Make sure web request stops here
exit;
