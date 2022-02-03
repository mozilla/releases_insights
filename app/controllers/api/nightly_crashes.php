<?php

declare(strict_types=1);

use ReleaseInsights\Utils;

$json = include MODELS . 'api/nightly_crashes.php';

Utils::renderJson($json);
