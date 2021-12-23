<?php

declare(strict_types=1);

use ReleaseInsights\Version;

$requested_version = Version::get();

$json = include MODELS . 'api/release_schedule.php';

require_once VIEWS . 'json.php';
