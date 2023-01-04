<?php

declare(strict_types=1);

use ReleaseInsights\Utils;

$api_call = true;
$json = include MODELS . 'api/nightly.php';

Utils::renderJson($json);
