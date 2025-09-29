<?php

declare(strict_types=1);

use ReleaseInsights\Request;
use function Sentry\captureLastError;
use Tracy\Debugger;

// Allow http resources when ran locally in tests
$https_only = LOCALHOST ? '' : 'default-src https:;';

// This is our production CSP
$csp_headers = "Content-Security-Policy: $https_only object-src 'none'; base-uri 'self'; script-src 'self' 'nonce-" . NONCE ."'; style-src 'self' 'nonce-" . NONCE . "'; style-src-attr 'unsafe-inline'; frame-ancestors 'none'";

// Catch errors via Ignition library in dev mode only
if (LOCALHOST) {
    Debugger::$strictMode = true;
    Debugger::$editor = 'subl://open?url=file://%file&line=%line';
    Debugger::enable();
    // Error handler page is blocked by our production CSP rules
    $csp_headers = '';
}

// Send HTTP security headers (not set by the server)
header('X-Content-Type-Options: nosniff');
header($csp_headers);

// Dispatch urls. The $url object is defined in router.php
$url->loadController();

// Send the last error to Sentry
captureLastError();

// Web request stops here
exit;
