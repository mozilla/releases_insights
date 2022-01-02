<?php

declare(strict_types=1);

use ReleaseInsights\Version;

if ((int) $requested_version < (int) $main_beta) {
    return ['error' => 'API only works with future release.'];
}

// Planned releases
$upcoming_releases = include DATA .'upcoming_releases.php';

// Historical data from Product Details, cache a week
$shipped_releases = ReleaseInsights\Utils::getJson(
    'https://product-details.mozilla.org/1.0/firefox_history_major_releases.json',
    604800
);

// Merge with future dates stored locally
$all_releases = array_merge($shipped_releases, $upcoming_releases);

if (! array_key_exists($requested_version, $all_releases)) {
    return ['error' => 'Not enough data for this version number.'];
}

// Future release date object
$release = new DateTime($all_releases[(string) $requested_version]);

// Previous release object
$previous_release = new DateTime($all_releases[Version::decrement($requested_version, 1)]);

// Calculate 1st day of the nightly cycle
$nightly = new DateTime($all_releases[Version::decrement($requested_version, 2)]);

$nightly->modify('-1 day');

$date_format = 'Y-m-d H:i';

$x = match ($requested_version) {
    '96.0', '97.0' => 4,
    default => 3,
};

$schedule = [
    'nightly_start'    => $nightly->format($date_format),
    'soft_code_freeze' => $nightly->modify('+' . $x .' weeks')->modify('Thursday')->format($date_format),
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

if ($requested_version === '96.0') {
    $nightly = new DateTime($all_releases[Version::decrement($requested_version, 2)]);
    $nightly->modify('-1 day');

    $schedule = [
        'nightly_start'    => $nightly->format($date_format),
        'soft_code_freeze' => $nightly->modify('+' . $x .' weeks')->modify('Thursday')->format($date_format),
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
        'beta_10'          => $nightly->modify('Tuesday')->format($date_format),
        // We have a 5 weeks beta cycle for 96,but no additional betas
        'rc_gtb'           => $nightly->modify('Monday')->format($date_format),
        'rc'               => $nightly->modify('Tuesday')->format($date_format),
        'release'          => $release->format($date_format),
    ];
}
// Sort the schedule by date, needed for schedules with a fixup
asort($schedule);

// The schedule contains the release version
return ['version' => $requested_version] + $schedule;
