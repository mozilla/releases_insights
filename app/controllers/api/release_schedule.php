<?php

use ReleaseInsights\Utils;

$requested_version = Utils::requestedVersion();

$json = include MODELS . 'api/release_schedule.php';

require_once VIEWS . 'json.php';
