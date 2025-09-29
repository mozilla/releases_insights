<?php

declare(strict_types=1);

use ReleaseInsights\{Bugzilla, Data, ESR, Json, Nightly, URL, Utils, Version};

// Get the schedule for the current nightly
$requested_version = Version::get(FIREFOX_NIGHTLY);
$nightly_cycle_dates = include MODELS . 'api/release_schedule.php';

// Get the schedule for the current beta
$requested_version = Version::get(FIREFOX_BETA);
$beta_cycle_dates = include MODELS . 'api/release_schedule.php';

$today_is_release_day = new Data()->isTodayReleaseDay();

if ($today_is_release_day) {
    $firefox_version_on_release_day = array_search(date('Y-m-d'), new Data()->getMajorReleases());
} else {
    $firefox_version_on_release_day = FIREFOX_BETA;
}

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

        // Remote balrog API can give a 404, we have a fallback to N/A
        $rc_build = Json::load(URL::Balrog->value . 'rules/firefox-beta', 900)['mapping'] ?? 'N/A';

        if ($rc_build !== 'N/A') {
            $rc_build = explode('-', (string) $rc_build)[1];
            $rc_build = str_contains($rc_build, 'b') ? FIREFOX_BETA : BETA . ' RC';
        }
    }

    if ($today_is_release_day) {
        $is_rc_week = false;
    }
}

// Get the latest nightly build ID, used as a tooltip on the nightly version number
$latest_nightly = Json::load(
    URL::Balrog->value . 'releases/Firefox-mozilla-central-nightly-latest',
    900
);


$beta_version = new Version(FIREFOX_BETA)->int;
$beta_is_the_next_ESR = $beta_version == (int) ESR::getVersion($beta_version);

/* Only for the current Nightly view, this makes an HTTP request */
$nightly_state = new Nightly();
$nightly_emergency_message = Bugzilla::linkify($nightly_state->emergency_message);

// Temp test to check that we now DO get a 500 on the home page in CI only
// We try to open a file to workaround the static analyzer
fopen('/some/file', 'z');

return [
    $beta_cycle_dates,
    $nightly_cycle_dates,
    $today_is_release_day,
    $is_rc_week,
    $rc_build,
    $nightly_state->getLatestBuildID(),
    $firefox_version_on_release_day,
    $beta_is_the_next_ESR,
    $nightly_state->auto_updates,
    $nightly_emergency_message,
];
