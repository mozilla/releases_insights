<?php

declare(strict_types=1);

// Dispatch urls
$url = new ReleaseInsights\Request($_SERVER['REQUEST_URI']);
include CONTROLLERS . $url->getController() . '.php';
