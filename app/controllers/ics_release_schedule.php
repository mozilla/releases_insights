<?php

use ReleaseInsights\Utils;

$requested_version = Utils::requestedVersion();

$releases = include MODELS . 'api_release_schedule.php';
ray($releases);
require_once VIEWS . 'ics.php';
