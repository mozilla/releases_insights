<?php

use ReleaseInsights\Utils;

if ($requested_version < 75) {
    return ['error' => 'API only works with 4 week cycle releases.'];
}

if ($requested_version < FIREFOX_RELEASE) {
    return ['error' => 'API only works with future release.'];
}

// Utility function to decrement a version number provided as a string
$decrementVersion = function (string $version, int $decrement) {
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

$time_format = 'Y-m-d H:i';

$timetable = [
    'version'          => $requested_version,
    'nightly_start'    => $nightly->format($time_format),
    'soft_code_freeze' => $nightly->modify('+3 weeks')->modify('next Thursday')->format($time_format),
    'string_freeze'    => $nightly->modify('next Friday')->format($time_format),
    'merge_day'        => $nightly->modify('next Monday')->format($time_format),
    'beta_1'           => $nightly->modify('next Tuesday')->format($time_format),
    'beta_2'           => $nightly->modify('next Wednesday')->format($time_format),
    'beta_3'           => $nightly->modify('next Friday')->format($time_format),
    'beta_4'           => $nightly->modify('next Monday')->format($time_format),
    'beta_5'           => $nightly->modify('next Wednesday')->format($time_format),
    'beta_6'           => $nightly->modify('next Friday')->format($time_format),
    'beta_7'           => $nightly->modify('next Monday')->format($time_format),
    'beta_8'           => $nightly->modify('next Wednesday')->format($time_format),
    'beta_9'           => $nightly->modify('next Friday')->format($time_format),
    'rc_gtb'           => $nightly->modify('next Monday 05:00')->format($time_format),
    'rc'               => $nightly->modify('next Tuesday')->format($time_format),
    'release'          => $release->format($time_format),
];

// Sometimes there are problems in a release and we need to adjust the beta schedule manually
if ($requested_version === '80.0') {
    // We had infra problems that prevented shipping beta 1, beta 2 became beta 1
    $fix = new DateTime($timetable['beta_1']);
    $timetable['beta_1'] = $fix->modify('+1 day')->format($time_format);
    $timetable['beta_2'] = $fix->modify('next Friday')->format($time_format);
    $timetable['beta_3'] = $fix->modify('next Monday')->format($time_format);
    $timetable['beta_4'] = $fix->modify('next Wednesday')->format($time_format);
    $timetable['beta_5'] = $fix->modify('next Friday')->format($time_format);
    $timetable['beta_6'] = $fix->modify('next Monday')->format($time_format);
    $timetable['beta_7'] = $fix->modify('next Wednesday')->format($time_format);
    $timetable['beta_8'] = $fix->modify('next Friday')->format($time_format);
    unset($timetable['beta_9']);
}

return $timetable;
