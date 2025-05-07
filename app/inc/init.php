<?php

declare(strict_types=1);

use ReleaseInsights\Request;
use function Sentry\captureLastError;

// Allow http resources when ran locally in tests
$https_only = LOCALHOST ? '' : 'default-src https:;';

// This is our production CSP
$csp_headers = "Content-Security-Policy: $https_only object-src 'none'; base-uri 'self'; script-src 'self' 'nonce-" . NONCE ."'; style-src 'self' 'nonce-" . NONCE . "'; style-src-attr 'unsafe-inline'; frame-ancestors 'none'";

// Catch errors via Ignition library in dev mode only
if (getenv('TESTING_CONTEXT') === false  && LOCALHOST) {
    if (class_exists(\Spatie\Ignition\Ignition::class)) {
        \Spatie\Ignition\Ignition::make()
            ->setEditor('sublime')
            ->register();
    }
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

// Make sure web request stops here
exit;
