<?php

use ReleaseInsights\Utils;

// Utility function to decrement a version number provided as a string
$decrementVersion = function (string $version, int $decrement) {
    return (string) number_format((int) $version - $decrement, 1);
};

// Historical data from Product Details, cache a week
$shipped_releases = Utils::getJson('https://product-details.mozilla.org/1.0/firefox_history_major_releases.json', 604800);

// Merge with future dates stored locally
$all_releases = array_merge($shipped_releases, $upcoming_releases);

$release_date = $all_releases[(string) $requested_version];

// Future release date object
$release = new DateTime($release_date);

// Previous release object
$previous_release = new DateTime($all_releases[$decrementVersion($requested_version, 1)]);

// Release n-2 Needed for nightly cycle length calculation
$nightly_start = new DateTime($all_releases[$decrementVersion($requested_version, 2)]);

// Calculate the number of weeks between the 2 releases
$beta_cycle_length = $release->diff($previous_release)->days / 7;
$nightly_cycle_length = $previous_release->diff($nightly_start)->days / 7;

// Get the schedule for the release requested
$cycle_dates = include MODELS . 'api_releaseschedule.php';
