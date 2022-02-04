<?php

declare(strict_types=1);

use ReleaseInsights\Utils;

$json = include MODELS . 'api/release_owners.php';

Utils::renderJson($json);
