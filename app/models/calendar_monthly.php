<?php

declare(strict_types=1);

use ReleaseInsights\{Data, Release, Version};

$releases = new Data();
$events = [];

// Check if we have planned dot releases coming for the current cycle
$current_release = key($releases->getLatestMajorRelease());
$current_release_schedule = new Release($current_release)->getSchedule();
$extra_milestone = function (string $milestone) use ($current_release_schedule, $current_release): ?array {
    if (! isset($current_release_schedule[$milestone])) {
        return null;
    }

    $date = new DateTime($current_release_schedule[$milestone])->format('Y-m-d');
    return [
        'start'   => $date,
        'end'     => $date,
        'summary' => Release::getNiceLabel(new Version($current_release)->normalized, $milestone) . "<br>\n",
        'mask'    => true,
    ];
};

$events[] = $extra_milestone('release');
$events[] = $extra_milestone('dot_release_1');
$events[] = $extra_milestone('dot_release_2');
$events[] = $extra_milestone('dot_release_3');

// Loop through future releases
foreach (array_keys($releases->getFutureReleases()) as $version) {
    if ((int) $version < RELEASE) {
        continue;
    }

    foreach (new Release($version)->getSchedule() as $event => $date) {
        if ($event === 'version') {
            continue;
        }

        $date = new DateTime($date)->format('Y-m-d');

        $events[] = [
            'start'   => $date,
            'end'     => $date,
            'summary' => Release::getNiceLabel($version, $event) . "<br>\n",
            'mask'    => true,
        ];
    }
}

// Group events by month ('Y-m'). The calendar library scans all of its events
// for every day it draws, so we give each month only its own events.
// All our milestones are single-day events. Unplanned dot releases are null.
$events_by_month = [];
foreach (array_filter($events) as $event) {
    $events_by_month[substr($event['start'], 0, 7)][] = $event;
}

return $events_by_month;
