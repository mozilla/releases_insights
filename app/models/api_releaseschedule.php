<?php

use ReleaseInsights\Utils as Utils;

if ($requested_version < 75) {
    return ['error' => "API only works with 4 week cycle releases."];
}

// Utility function to decrement a version number provided as a string
$decrementVersion = function(string $version, int $decrement) {
    return (string) number_format((int) $version - $decrement, 1);
};

// Historical data from Product Details, cache a week
$shipped_releases = Utils::getJson('https://product-details.mozilla.org/1.0/firefox_history_major_releases.json', 604800);

// Merge with future dates stored locally
$all_releases = array_merge($shipped_releases, $upcoming_releases);

if (! array_key_exists($requested_version, $all_releases)) {
    return ['error' => "Not enough data for this version number."];
}

// Future release date object
$release = new DateTime($all_releases[(string) $requested_version]);

// Previous release object
$previous_release = new DateTime($all_releases[$decrementVersion($requested_version, 1)]);

// Calculate 1st day of the nightly cycle
$nightly = new DateTime($all_releases[$decrementVersion($requested_version, 2)]);
$nightly->modify('-1 day');

return [
    'version'          => $requested_version,
    'nightly_start'    => $nightly->format('Y-m-d'),
    'soft_code_freeze' => $nightly->modify('+3 weeks')->modify('next Thursday')->format('Y-m-d'),
    'string_freeze'    => $nightly->modify('next Friday')->format('Y-m-d'),
    'merge_day'        => $nightly->modify('next Monday')->format('Y-m-d'),
    'beta_1'           => $nightly->modify('next Tuesday')->format('Y-m-d'),
    'beta_2'           => $nightly->modify('next Wednesday')->format('Y-m-d'),
    'beta_3'           => $nightly->modify('next Friday')->format('Y-m-d'),
    'beta_4'           => $nightly->modify('next Monday')->format('Y-m-d'),
    'beta_5'           => $nightly->modify('next Wednesday')->format('Y-m-d'),
    'beta_6'           => $nightly->modify('next Friday')->format('Y-m-d'),
    'beta_7'           => $nightly->modify('next Monday')->format('Y-m-d'),
    'beta_8'           => $nightly->modify('next Wednesday')->format('Y-m-d'),
    'beta_9'           => $nightly->modify('next Friday')->format('Y-m-d'),
    'rc_gtb'           => $nightly->modify('next Monday')->format('Y-m-d'),
    'rc'               => $nightly->modify('next Tuesday')->format('Y-m-d'),
    'release'          => $release->format('Y-m-d'),
];
