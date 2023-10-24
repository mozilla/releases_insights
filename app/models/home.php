<?php

declare(strict_types=1);

use ReleaseInsights\Data;
use ReleaseInsights\ESR;
use ReleaseInsights\Utils;
use ReleaseInsights\Version;

// Get the schedule for the current nightly
$requested_version = Version::get(FIREFOX_NIGHTLY);
$nightly_cycle_dates = include MODELS . 'api/release_schedule.php';

// Get the schedule for the current beta
$requested_version = Version::get(FIREFOX_BETA);
$beta_cycle_dates = include MODELS . 'api/release_schedule.php';

$today_is_release_day = (new Data())->isTodayReleaseDay();

if ($today_is_release_day) {
    $firefox_version_on_release_day = array_search(date('Y-m-d'), (new Data())->getMajorReleases());
} else {
    $firefox_version_on_release_day = FIREFOX_BETA;
}

$aus_url = 'https://aus-api.mozilla.org/api/v1/';

// Calculation of rc_week interval
$is_rc_week = false;
$today = new DateTime();
$rc_week_start = new DateTime($beta_cycle_dates['rc_gtb']);
$rc_week_end = new DateTime($nightly_cycle_dates['merge_day']);
$rc_build = FIREFOX_BETA;

if ((int) FIREFOX_BETA !== (int) FIREFOX_RELEASE) {
    if (Utils::isDateBetweenDates($today, $rc_week_start, $rc_week_end)) {
        $is_rc_week = true;
        // Check if we have already shipped a Release Candidate build to the beta channel
        $rc_build = Utils::getJson($aus_url . 'rules/firefox-beta', 900)['mapping'];
        $rc_build = explode('-', $rc_build)[1];
        $rc_build = str_contains($rc_build, 'b') ? FIREFOX_BETA : BETA . ' RC';
    }
    if ($today_is_release_day) {
        $is_rc_week = false;
    }
}

// Get the latest nightly build ID, used as a tooltip on the nightly version number
$latest_nightly = Utils::getJson(
    $aus_url . 'releases/Firefox-mozilla-central-nightly-latest',
    900
);

$latest_nightly = $latest_nightly['platforms']['WINNT_x86_64-msvc']['locales']['en-US']['buildID'] ?? 'N/A';

$beta_is_the_next_ESR = Version::getMajor(FIREFOX_BETA) == (int) ESR::getVersion(Version::getMajor(FIREFOX_BETA));

return [
    $beta_cycle_dates,
    $nightly_cycle_dates,
    $today_is_release_day,
    $is_rc_week,
    $rc_build,
    $latest_nightly,
    $firefox_version_on_release_day,
    $beta_is_the_next_ESR,
];
