<?php

use ReleaseInsights\Utils;

$requested_version = Utils::requestedVersion();

// our Json view outputs data stored in the $json variable
$json = include MODELS . 'api_release_schedule.php';
require_once VIEWS . 'json.php';
