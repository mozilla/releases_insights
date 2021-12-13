<?php

declare(strict_types=1);

$requested_version = ReleaseInsights\Utils::requestedVersion();

$json = include MODELS . 'api/release_schedule.php';

require_once VIEWS . 'json.php';
