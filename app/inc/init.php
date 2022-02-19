<?php

declare(strict_types=1);

// Dispatch urls
$url = new ReleaseInsights\Request(filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL));
include CONTROLLERS . $url->getController() . '.php';
