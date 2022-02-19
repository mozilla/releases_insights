<?php

declare(strict_types=1);

// Dispatch urls
$url = new ReleaseInsights\Request(
    htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8')
);
include CONTROLLERS . $url->getController() . '.php';
