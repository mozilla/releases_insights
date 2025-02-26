<?php

declare(strict_types=1);

use ReleaseInsights\Request;
use function Sentry\captureLastError;

// Catch errors via Ignition library in dev mode only
if (getenv('TESTING_CONTEXT') === false  && LOCALHOST) {
    if (class_exists(\Spatie\Ignition\Ignition::class)) {
        \Spatie\Ignition\Ignition::make()
            ->setEditor('vscode')
            ->register();
    }
}

// Send HTTP security headers (not set by the server)
header('X-Content-Type-Options: nosniff');

// Allow http resources when ran locally
$https_only = LOCALHOST ? '' : 'default-src https:;';
header("Content-Security-Policy: $https_only object-src 'none'; base-uri 'self'; script-src 'self' 'nonce-" . NONCE ."'; style-src 'self' 'nonce-" . NONCE . "'; style-src-attr 'unsafe-inline'; frame-ancestors 'none'");

// Dispatch urls. The $url object is defined in router.php
$url->loadController();

// Send the last error to Sentry
captureLastError();

// Web request stops here
exit;
