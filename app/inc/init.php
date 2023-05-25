<?php

declare(strict_types=1);

use function \Sentry\{init, captureLastError};

// Load the .env file to get local vs CI CONTEXT environment value
$dotenv = Dotenv\Dotenv::createImmutable(INSTALL_ROOT);
$dotenv->safeLoad();

// Catch errors via Ignition library in dev mode only
if (isset($_ENV['CONTEXT']) && $_ENV['CONTEXT'] == 'local') {
    if (class_exists('\Spatie\Ignition\Ignition')) {
        \Spatie\Ignition\Ignition::make()
            ->setEditor('sublime')
            ->register();
    }
}

// Set up Sentry endpoint, don't send errors while in dev mode
if (STAGING) {
    init(['dsn' => 'https://e17dcdc892db4ee08a6937603e407f76@o1069899.ingest.sentry.io/4505243444772864']);
}

if (PRODUCTION) {
    init(['dsn' => 'https://20bef71984594e16add1d2c69146ad88@o1069899.ingest.sentry.io/4505243430092800']);
}

// Dispatch urls
$url = new ReleaseInsights\Request(filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL));

include CONTROLLERS . $url->getController() . '.php';
