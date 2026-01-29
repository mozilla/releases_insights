<?php

declare(strict_types=1);

use ReleaseInsights\{Data, ReleaseCalendar};

$releases = new Data()->getFutureReleases();

$filename = 'Firefox_major_releases_schedule.ics';
$ics_calendar = ReleaseCalendar::getICS(
    $releases,
    $release_schedule_labels = [],
    $filename
);

return [$filename, $ics_calendar];
