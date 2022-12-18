<?php

declare(strict_types=1);

include 'vendor/autoload.php';

// Catch errors via Ignition library in dev mode only
if (class_exists('\Spatie\Ignition\Ignition')) {
    \Spatie\Ignition\Ignition::make()
        ->setEditor('sublime')
        ->register();
}

// Dispatch urls
$url = new ReleaseInsights\Request(filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL));
include CONTROLLERS . $url->getController() . '.php';
