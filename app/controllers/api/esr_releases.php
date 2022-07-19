<?php

declare(strict_types=1);

$json = include MODELS . 'api/esr_releases.php';

ReleaseInsights\Utils::renderJson($json);
