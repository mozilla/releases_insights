<?php

declare(strict_types=1);

use ReleaseInsights\Utils;

$json = include MODELS . 'api/nightly.php';

Utils::renderJson($json);
