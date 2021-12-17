<?php

declare(strict_types=1);

use ReleaseInsights\Utils;

$json = include MODELS . 'api/esr_releases.php';

Utils::renderJson($json);
