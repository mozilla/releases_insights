<?php

use ReleaseInsights\Utils;

if ($requested_version < 75) {
    return ['error' => 'API only works with 4 week cycle releases.'];
}

if ($requested_version < FIREFOX_RELEASE) {
    return ['error' => 'API only works with future release.'];
}

// Utility function to decrement a version number provided as a string
$decrementVersion = function (string $version, int $decrement): string {
    return (string) number_format((int) $version - $decrement, 1);
};

// Planned releases
$upcoming_releases = include DATA .'upcoming_releases.php';

// Historical data from Product Details, cache a week
$shipped_releases = Utils::getJson('https://product-details.mozilla.org/1.0/firefox_history_major_releases.json', 604800);

// Merge with future dates stored locally
$all_releases = array_merge($shipped_releases, $upcoming_releases);

if (! array_key_exists($requested_version, $all_releases)) {
    return ['error' => 'Not enough data for this version number.'];
}

// Future release date object
$release = new DateTime($all_releases[(string) $requested_version]);

// Previous release object
$previous_release = new DateTime($all_releases[$decrementVersion($requested_version, 1)]);

// Calculate 1st day of the nightly cycle
$nightly = new DateTime($all_releases[$decrementVersion($requested_version, 2)]);
$nightly->modify('-1 day');

$date_format = 'Y-m-d H:i';

$schedule = [
    'nightly_start'    => $nightly->format($date_format),
    'soft_code_freeze' => $nightly->modify('+3 weeks')->modify('Thursday')->format($date_format),
    'string_freeze'    => $nightly->modify('Friday')->format($date_format),
    'merge_day'        => $nightly->modify('Monday')->format($date_format),
    'beta_1'           => $nightly->modify('Monday')->format($date_format),
    'beta_2'           => $nightly->modify('Tuesday')->format($date_format),
    'beta_3'           => $nightly->modify('Thursday')->format($date_format),
    'beta_4'           => $nightly->modify('Sunday')->format($date_format),
    'beta_5'           => $nightly->modify('Tuesday')->format($date_format),
    'beta_6'           => $nightly->modify('Thursday')->format($date_format),
    'beta_7'           => $nightly->modify('Sunday')->format($date_format),
    'beta_8'           => $nightly->modify('Tuesday')->format($date_format),
    'beta_9'           => $nightly->modify('Thursday')->format($date_format),
    'rc_gtb'           => $nightly->modify('Monday')->format($date_format),
    'rc'               => $nightly->modify('Tuesday')->format($date_format),
    'release'          => $release->format($date_format),
];

// Sort the schedule by date, needed for schedules with a fixup
asort($schedule);

// The schedule contains the release version
return ['version' => $requested_version] + $schedule;
