<?php

use ReleaseInsights\Utils;

// Get the schedule for the current nightly
$requested_version = Utils::requestedVersion(FIREFOX_NIGHTLY);
$nightly_cycle_dates = include MODELS . 'api/release_schedule.php';

// Get the schedule for the current beta
$requested_version = Utils::requestedVersion(FIREFOX_BETA);
$beta_cycle_dates = include MODELS . 'api/release_schedule.php';

// Historical data from Product Details, cache an hour
$shipped_releases = Utils::getJson('https://product-details.mozilla.org/1.0/firefox_history_major_releases.json', 3600);
$upcoming_releases = include DATA .'upcoming_releases.php';

$all_releases = array_merge($shipped_releases, $upcoming_releases);

$today_is_release_day = false;

$today = date('Y-m-d');

$shipping_release = (int) array_search($today, $all_releases);

if (in_array($today, $all_releases)) {
    $today_is_release_day = true;
}

// Calculation of rc_week interval
$is_rc_week = false;
$today = new DateTime();
$rc_week_start = new DateTime($beta_cycle_dates['rc_gtb']);
$rc_week_end = new DateTime($nightly_cycle_dates['merge_day']);

if ((int) FIREFOX_BETA !== (int) FIREFOX_RELEASE) {
    if (Utils::isDateBetweenDates($today, $rc_week_start, $rc_week_end)) {
        $is_rc_week = true;
    }
}
