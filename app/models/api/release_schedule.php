<?php

declare(strict_types=1);

use ReleaseInsights\Version;
use ReleaseInsights\Utils;

// We may call this file with a specific version number defined in the controller
if (! isset($requested_version)) {
    $requested_version = Version::get();
}

if ((int) $requested_version < BETA) {
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

$x = match ($requested_version) {
    '97.0' => 4,
    default => 3,
};

$schedule = [
    'nightly_start'    => $requested_version === '100.0' ? $nightly->modify('+1 day') : $nightly,
    'soft_code_freeze' => $nightly->modify('+' . $x .' weeks')->modify('Thursday'),
    'string_freeze'    => $nightly->modify('Friday'),
    'merge_day'        => $nightly->modify('Monday'),
    'beta_1'           => $nightly->modify('Monday'),
    'beta_2'           => $nightly->modify('Tuesday'),
    'beta_3'           => $nightly->modify('Thursday'),
    'beta_4'           => $nightly->modify('Sunday'),
    'beta_5'           => $nightly->modify('Tuesday'),
    'beta_6'           => $nightly->modify('Thursday'),
    'beta_7'           => $nightly->modify('Sunday'),
    'beta_8'           => $nightly->modify('Tuesday'),
    'beta_9'           => $nightly->modify('Thursday'),
    'rc_gtb'           => $nightly->modify('Monday'),
    'rc'               => $nightly->modify('Tuesday'),
    'release'          => $release,
];

if ($requested_version === '99.0') {
    $nightly = new DateTime($all_releases[Version::decrement($requested_version, 2)]);
    $nightly->modify('-1 day');

    $schedule = [
        'nightly_start'    => $nightly,
        'soft_code_freeze' => $nightly->modify('+' . $x .' weeks')->modify('Thursday'),
        'string_freeze'    => $nightly->modify('Friday'),
        'merge_day'        => $nightly->modify('Tuesday'),
        'beta_1'           => $nightly->modify('Tuesday'),
        'beta_2'           => $nightly->modify('Thursday'),
        'beta_3'           => $nightly->modify('Sunday'),
        'beta_4'           => $nightly->modify('Tuesday'),
        'beta_5'           => $nightly->modify('Thursday'),
        'beta_6'           => $nightly->modify('Sunday'),
        'beta_7'           => $nightly->modify('Tuesday'),
        'beta_8'           => $nightly->modify('Thursday'),
        'rc_gtb'           => $nightly->modify('Monday'),
        'rc'               => $nightly->modify('Tuesday'),
        'release'          => $release,
    ];
}

// Transform all the DateTime objects in the $schedule array into formated date strings
foreach ($schedule as $k => $v) {
    $schedule[$k] = $v->format('Y-m-d H:i');
}

// Sort the schedule by date, needed for schedules with a fixup
asort($schedule);

// The schedule contains the release version
return ['version' => $requested_version] + $schedule;
