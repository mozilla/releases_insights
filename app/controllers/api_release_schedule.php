<?php

use ReleaseInsights\Utils;

$requested_version = Utils::requestedVersion();

// Planned releases
$upcoming_releases = include DATA .'upcoming_releases.php';

// our Json view outputs data stored in the $json variable
$json = include MODELS . 'api_release_schedule.php';
require_once VIEWS . 'json.php';
