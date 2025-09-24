<?php

declare(strict_types=1);

use benhall14\phpCalendar\Calendar;
use ReleaseInsights\{Data, Release, Version};

$releases = new Data();
$calendar = new Calendar();
$events = [];

// Check if we have planned dot releases coming for the current cycle
$current_release = key($releases->getLatestMajorRelease());
$current_release_schedule = new Release($current_release)->getSchedule();
$extra_milestone = function (string $milestone) use ($current_release_schedule, $current_release) {
    if (isset($current_release_schedule[$milestone])) {
        $date = new DateTime($current_release_schedule[$milestone])->format('Y-m-d');
        return [
            'start'   => $date,
            'end'     => $date,
            'summary' => Release::getNiceLabel(new Version($current_release)->normalized, $milestone) . "<br>\n",
            'mask'    => true,
        ];
    }
};

$events[] = $extra_milestone('mobile_dot_release');
$events[] = $extra_milestone('planned_dot_release');

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

return $calendar->addEvents($events);
