<?php

declare(strict_types=1);

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

// Dispatch urls
$url = new ReleaseInsights\Request(filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL));

include CONTROLLERS . $url->getController() . '.php';
