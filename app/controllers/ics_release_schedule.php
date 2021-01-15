<?php

use ReleaseInsights\Utils;

$requested_version = Utils::requestedVersion();


if ((int) $requested_version < $main_beta) {
	die("We don't provide schedule calendars for past releases");
}

$releases = include MODELS . 'api_release_schedule.php';

include MODELS . 'ics_release_schedule.php';

require_once VIEWS . 'ics.php';
