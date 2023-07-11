<?php

declare(strict_types=1);

$json = include MODELS . 'api/release_schedule.php';

ReleaseInsights\Utils::renderJson($json);
