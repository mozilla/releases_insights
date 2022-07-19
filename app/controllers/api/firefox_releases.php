<?php

declare(strict_types=1);

use ReleaseInsights\Utils;

$json = include MODELS . 'api/firefox_releases.php';

Utils::renderJson($json);
