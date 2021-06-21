<?php

declare(strict_types=1);

use ReleaseInsights\Utils;

$requested_version = Utils::requestedVersion();

$json = include MODELS . 'api/release_schedule.php';

require_once VIEWS . 'json.php';
