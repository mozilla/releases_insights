<?php

declare(strict_types=1);

use ReleaseInsights\{Release, ReleaseCalendar, Version};

if (! isset($requested_version)) {
    $requested_version = Version::get();
}

// Get the schedule for the release requested
$release = new Release($requested_version);
$sched = $release->getSchedule();
$short_version = (string) Version::getMajor($sched['version']);

$release_schedule_labels = Release:: getLabels($requested_version, short:false);

// Add end of early betas to the schedule
$sched['early_beta_end'] = $sched['beta_5'];
$release_schedule_labels['early_beta_end'] = 'End of EARLY_BETA_OR_EARLIER (post beta 5)';

// We don't want html tags in out labels as this is an ICS export
$release_schedule_labels = array_map('strip_tags', $release_schedule_labels);

$ics_calendar = ReleaseCalendar::getICS(
    $sched,
    $release_schedule_labels,
    'Firefox ' . $short_version
);

$filename = 'Firefox_' . $short_version . '_schedule.ics';

return [$filename, $ics_calendar];
