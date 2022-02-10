<?php

declare(strict_types=1);

use ReleaseInsights\Bugzilla;
use ReleaseInsights\Nightly;
use ReleaseInsights\Utils;
use ReleaseInsights\Version;

$requested_version = Version::get();

// Historical data from Product Details, cache a week
$shipped_releases = Utils::getJson(
    'https://product-details.mozilla.org/1.0/firefox_history_major_releases.json',
    604800
);

// Merge with future dates stored locally
$upcoming_releases = include DATA .'upcoming_releases.php';
$all_releases = array_merge($shipped_releases, $upcoming_releases);

$release_date = $all_releases[(string) $requested_version];

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
$cycle_dates = include MODELS . 'api/release_schedule.php';

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
        3600 * 24 * 365
    );
}

$nightly_updates = true;
/* Only for the current Nightly view */
if ((int) $requested_version == Version::getMajor(FIREFOX_NIGHTLY)) {
    // Are nightly updates activated?
    $nightly_updates = (new Nightly())->auto_updates;
}

return [
    $release_date,
    $beta_cycle_length,
    $nightly_cycle_length,
    $nightly_fixes,
    $nightly_updates,
    $cycle_dates,
];
