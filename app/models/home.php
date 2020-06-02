<?php

use ReleaseInsights\Utils;

// Get the schedule for the current beta
$requested_version = Utils::requestedVersion(FIREFOX_NIGHTLY);
$cycle_dates = include MODELS . 'api_release_schedule.php';



// Historical data from Product Details, cache an hour
$shipped_releases = Utils::getJson('https://product-details.mozilla.org/1.0/firefox_history_major_releases.json', 3600);
$upcoming_releases = include DATA .'upcoming_releases.php';

$all_releases = array_merge($shipped_releases, $upcoming_releases);

$today_is_release_day = false;

$today = date('Y-m-d');

if (in_array($today, $all_releases)) {
    $today_is_release_day = true;
    $shipping_release = (int) array_search($today, $all_releases);
}
