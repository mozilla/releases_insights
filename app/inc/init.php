<?php

declare(strict_types=1);

use function Sentry\captureLastError;
use Tracy\Debugger;

// This is our production CSP
$csp_headers = "Content-Security-Policy: default-src https:; object-src 'none'; base-uri 'self'; script-src 'self' 'nonce-" . NONCE ."'; style-src 'self' 'nonce-" . NONCE . "'; style-src-attr 'unsafe-inline'; frame-ancestors 'none'";

// The Tracy error page is blocked by our production CSP rules. LOCALHOST relies on
// the Host header which clients control, so we also check that we run on PHP's
// built-in server, which is never used in production.
if (IS_DEV_MODE || (LOCALHOST && PHP_SAPI === 'cli-server')) {
    $csp_headers = '';
}

if (IS_DEV_MODE) {
    // Catch errors via Tracy library in dev mode only
    if (class_exists(Tracy\Debugger::class)) {
        Debugger::$strictMode = true;
        Debugger::$editor = 'subl://open?url=file://%file&line=%line';
        Debugger::enable();
     }
}

// Send our CSP, other security headers are sent in router.php
if ($csp_headers !== '') {
    header($csp_headers);
}

// Dispatch urls. The $url object is defined in router.php
$url->loadController();

// Send the last error to Sentry
captureLastError();

// Make sure web request stops here
exit;
