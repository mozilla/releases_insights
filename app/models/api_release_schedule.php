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
    'soft_code_freeze' => $nightly->modify('+3 weeks')->modify('next Thursday')->format($date_format),
    'string_freeze'    => $nightly->modify('next Friday')->format($date_format),
    'merge_day'        => $nightly->modify('next Monday')->format($date_format),
    'beta_1'           => $nightly->modify('next Tuesday')->format($date_format),
    'beta_2'           => $nightly->modify('next Wednesday')->format($date_format),
    'beta_3'           => $nightly->modify('next Friday')->format($date_format),
    'beta_4'           => $nightly->modify('next Monday')->format($date_format),
    'beta_5'           => $nightly->modify('next Wednesday')->format($date_format),
    'beta_6'           => $nightly->modify('next Friday')->format($date_format),
    'beta_7'           => $nightly->modify('next Monday')->format($date_format),
    'beta_8'           => $nightly->modify('next Wednesday')->format($date_format),
    'beta_9'           => $nightly->modify('next Friday')->format($date_format),
    'rc_gtb'           => $nightly->modify('next Monday')->format($date_format),
    'rc'               => $nightly->modify('next Tuesday')->format($date_format),
    'release'          => $release->format($date_format),
];

if ($requested_version === '85.0') {
    // We will ship 85 on a longer 6 weeks cycle because of EOY holidays
    $fix = new DateTime($schedule['beta_4']);
    $schedule['beta_5'] = $fix->modify('+ 2 weeks')->modify('next Wednesday')->format($date_format);
    $schedule['beta_6'] = $fix->modify('next Friday')->format($date_format);
    $schedule['beta_7'] = $fix->modify('next Monday')->format($date_format);
    $schedule['beta_8'] = $fix->modify('next Wednesday')->format($date_format);
    $schedule['beta_9'] = $fix->modify('next Friday')->format($date_format);
    $schedule['rc_gtb'] = $fix->modify('next Monday')->format($date_format);
    $schedule['rc']     = $fix->modify('next Tuesday')->format($date_format);
}

if ($requested_version === '86.0') {
    $fix = new DateTime($schedule['nightly_start']);
    $schedule['soft_code_freeze'] = $fix->modify('+5 weeks')->modify('next Thursday')->format($date_format);
    $schedule['string_freeze']    = $fix->modify('next Friday')->format($date_format);
    $schedule['merge_day']        = $fix->modify('next Monday')->format($date_format);
    $schedule['beta_1']           = $fix->modify('next Tuesday')->format($date_format);
    $schedule['beta_2']           = $fix->modify('next Wednesday')->format($date_format);
    $schedule['beta_3']           = $fix->modify('next Friday')->format($date_format);
    $schedule['beta_4']           = $fix->modify('next Monday')->format($date_format);
    $schedule['beta_5']           = $fix->modify('next Wednesday')->format($date_format);
    $schedule['beta_6']           = $fix->modify('next Friday')->format($date_format);
    $schedule['beta_7']           = $fix->modify('next Monday')->format($date_format);
    $schedule['beta_8']           = $fix->modify('next Wednesday')->format($date_format);
    $schedule['beta_9']           = $fix->modify('next Friday')->format($date_format);
    $schedule['rc_gtb']           = $fix->modify('next Monday')->format($date_format);
    $schedule['rc']               = $fix->modify('next Tuesday')->format($date_format);
}

// Sort the schedule by date, needed for schedules with a fixup
asort($schedule);

// The schedule contains the release version
return ['version' => $requested_version] + $schedule;
