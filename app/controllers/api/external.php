<?php

declare(strict_types=1);

$json = include MODELS . 'api/external.php';

ReleaseInsights\Utils::renderJson($json);
