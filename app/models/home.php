<?php

use ReleaseInsights\Utils;

// Get the schedule for the current beta
$requested_version = Utils::requestedVersion(FIREFOX_NIGHTLY);
$cycle_dates = include MODELS . 'api_release_schedule.php';

