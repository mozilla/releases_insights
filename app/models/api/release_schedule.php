<?php

declare(strict_types=1);

use ReleaseInsights\Data;
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
$upcoming_releases = (new Data())->getFutureReleases();

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

// Transform all the DateTime objects in the $schedule array into formated date strings
$date = function(string|object $day) use ($nightly): string {
    return is_object($day) ? $day->format('Y-m-d H:i') : $nightly->modify($day)->format('Y-m-d H:i');
};

$schedule = [
    'nightly_start'    => $requested_version === '100.0' ? $date('+1 day') : $date($nightly),
    'soft_code_freeze' => $date($nightly->modify('+' . $x .' weeks')->modify('Thursday')),
    'string_freeze'    => $date('Friday'),
    'merge_day'        => $date('Monday'),
    'beta_1'           => $date('Monday'),
    'beta_2'           => $date('Tuesday'),
    'beta_3'           => $date('Thursday'),
    'beta_4'           => $date('Sunday'),
    'beta_5'           => $date('Tuesday'),
    'beta_6'           => $date('Thursday'),
    'beta_7'           => $date('Sunday'),
    'beta_8'           => $date('Tuesday'),
    'beta_9'           => $date('Thursday'),
    'rc_gtb'           => $date('Monday'),
    'rc'               => $date('Tuesday'),
    'release'          => $date($release),
];

unset($date);

// Sort the schedule by date, needed for schedules with a fixup
asort($schedule);

// The schedule starts with the release version number
return ['version' => $requested_version] + $schedule;
