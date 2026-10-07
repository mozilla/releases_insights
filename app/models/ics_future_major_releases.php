<?php

declare(strict_types=1);

use ReleaseInsights\{Data, ReleaseCalendar};

$data = new Data();

// Keep the major releases shipped in the last 12 months, so that they don't
// disappear from subscribed calendars on release day. Their event UID is the
// same as when they were upcoming releases.
$one_year_ago = date('Y-m-d', strtotime('-1 year'));
$shipped_releases = array_filter(
    $data->getMajorPastReleases(),
    fn(string $date, string $version) => $date >= $one_year_ago && preg_match('/^\d+\.0$/', $version),
    ARRAY_FILTER_USE_BOTH
);

$releases = [...$shipped_releases, ...$data->getFutureReleases()];

$filename = 'Firefox_major_releases_schedule.ics';
$ics_calendar = ReleaseCalendar::getICS(
    $releases,
    $release_schedule_labels = [],
    'Firefox major releases',
    'firefox-major-release'
);

return [$filename, $ics_calendar];
