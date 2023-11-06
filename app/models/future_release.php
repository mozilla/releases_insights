<?php

declare(strict_types=1);

use ReleaseInsights\{Bugzilla, Data, Nightly, Release, Version};

$requested_version = Version::get();

$data = new Data();

if ($data->isTodayReleaseDay()) {
    // On release day we want to force fetching product-details on all page views
    $data->cache_duration = 1;
}

$all_releases = (new Data())->getMajorReleases();

// Avoid a warning when we have a race condition in product-details
$release_date = $all_releases[(string) $requested_version] ?? '';

// Future release date object
$release = new DateTime($release_date);

// Previous release object
$previous_release = new DateTime($all_releases[Version::decrement($requested_version, 1)]);

// Release n-2 Needed for nightly cycle length calculation
$nightly_start = new DateTime($all_releases[Version::decrement($requested_version, 2)]);

// Calculate the number of weeks between the 2 releases
$beta_cycle_length = $release->diff($previous_release)->days / 7;
$nightly_cycle_length = $previous_release->diff($nightly_start)->days / 7;

// Get the schedule for the release requested
$cycle_dates = (new Release($requested_version))->getSchedule();

$nightly_fixes = 0;
/* Only for the current Beta view */
if ((int) $requested_version === BETA) {
    // Number of bugs fixed in nightly
    $nightly_fixes = Bugzilla::getBugsFromHgWeb(
        'https://hg.mozilla.org/mozilla-central/json-pushes'
        . '?fromchange=FIREFOX_NIGHTLY_' . ((int) $requested_version - 1) . '_END'
        . '&tochange=FIREFOX_NIGHTLY_' . (int) $requested_version .'_END'
        . '&full&version=2',
        true,
        -1 // Immutable external data, store forever
    );
}

$nightly_updates = true;
$nightly_emergency = '';
/* Only for the current Nightly view, this makes an HTTP request */
if ((int) $requested_version == NIGHTLY) {
    // Are nightly updates activated?
    $nightly_state     = new Nightly();
    $nightly_updates   = $nightly_state->auto_updates;
    $nightly_emergency = Bugzilla::linkify($nightly_state->emergency_message);
}


// This is to display a banner with the remaining working days before release
$beta    = new DateTime($cycle_dates['merge_day']);
$release = new DateTime($cycle_dates['rc_gtb']);
$days_before_beta = (new DateTime('today'))->diff($beta)->days;
$days_before_release = (new DateTime('today'))->diff($release)->days;

return [
    $release_date,
    $beta_cycle_length,
    $nightly_cycle_length,
    $nightly_fixes,
    $nightly_updates,
    $nightly_emergency,
    $cycle_dates,
    $days_before_beta,
    $days_before_release,
];
