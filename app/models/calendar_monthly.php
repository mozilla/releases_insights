<?php

declare(strict_types=1);

use benhall14\phpCalendar\Calendar;
use ReleaseInsights\{Data, Release, Version};

$releases = new Data();
$calendar = new Calendar();
$events = [];

// Check if we have a planned dot release coming for the current cycle
$current_release = key($releases->getLatestMajorRelease());

$current_release_schedule = (new Release($current_release))->getSchedule();

if (isset($current_release_schedule['planned_dot_release'])) {
    $date = (new DateTime($current_release_schedule['planned_dot_release']))->format('Y-m-d');
    $events[] = [
        'start'   => $date,
        'end'     => $date,
        'summary' => Release::getNiceLabel((new Version($current_release))->normalized, 'planned_dot_release') . "<br>\n",
        'mask'    => true,
    ];
}

// loop through future releases
foreach (array_keys($releases->getFutureReleases()) as $version) {
    if ((int) $version < RELEASE) {
        continue;
    }

    foreach ((new Release($version))->getSchedule() as $event => $date) {
        if ($event === 'version') {
            continue;
        }

        $date = (new DateTime($date))->format('Y-m-d');

        $events[] = [
            'start'   => $date,
            'end'     => $date,
            'summary' => Release::getNiceLabel($version, $event) . "<br>\n",
            'mask'    => true,
        ];
    }
}

return $calendar->addEvents($events);
