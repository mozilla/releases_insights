<?php

declare(strict_types=1);

use ReleaseInsights\Request;
use function Sentry\captureLastError;

// Catch errors via Ignition library in dev mode only
if (getenv('TESTING_CONTEXT') === false  && LOCALHOST) {
    if (class_exists(\Spatie\Ignition\Ignition::class)) {
        \Spatie\Ignition\Ignition::make()
            ->setEditor('sublime')
            ->register();
    }
}

// Send HTTP security headers
header('X-Content-Type-Options: nosniff');

// Allow http ressources when ran locally
if (! LOCALHOST) {
    header("Content-Security-Policy: default-src https:; object-src 'none'; base-uri 'self'; script-src 'self' 'nonce-" . NONCE . "'; frame-ancestors 'none'");
}

// Dispatch urls
$url = new Request(filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL));

include CONTROLLERS . $url->getController() . '.php';

// Send the last error to Sentry
captureLastError();

// Clean up temp variables from global space
unset($url);

// Web request stops here
exit;
