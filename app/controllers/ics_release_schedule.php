<?php

declare(strict_types=1);

use ReleaseInsights\{Model, Release, Version};

$requested_version = new Version(Version::get());
$release_schedule  = new Release($requested_version->normalized)->getSchedule();

if ($requested_version->int < BETA) {
    $error = 'We don\'t provide predictive schedules for <i>past</i> releases';
}

if (array_key_exists('error', $release_schedule)) {
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
