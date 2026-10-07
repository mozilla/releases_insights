<?php

declare(strict_types=1);

use ReleaseInsights\{Data, Model, Release, Version};

$requested_version = new Version(Version::get());
$release_schedule  = new Release($requested_version->normalized)->getFutureSchedule();

/*
    Shipped releases keep their planned schedule for a year, so that calendar
    subscriptions keep working after release day. Our schedule logic doesn't
    match the release cycles of older releases.
 */
$release_date = new Data()->getMajorReleases()[$requested_version->normalized] ?? null;
if ($requested_version->int < BETA && $release_date !== null && $release_date < date('Y-m-d', strtotime('-1 year'))) {
    $error = 'We don\'t provide schedules for releases shipped more than a year ago';
}

if (! isset($error) && array_key_exists('error', $release_schedule)) {
    $error = 'Release is not scheduled yet';
}

if (isset($error)) {
    include CONTROLLERS . 'user_error.php';
    exit;
}

// All good, we can generate an ICS
[$filename, $ics_calendar] = new Model('ics')->get();

header('Content-Type: text/calendar; charset=utf-8');
// header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo $ics_calendar;
