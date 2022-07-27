<?php

declare(strict_types=1);

use benhall14\phpCalendar\Calendar;
use ReleaseInsights\Data;
use ReleaseInsights\Release;

$calendar = new Calendar();
$events = [];

foreach (array_keys((new Data)->getFutureReleases()) as $version) {

    if ((int) $version <= RELEASE) {
        continue;
    }

    foreach ((new Release($version))->getSchedule() as $event => $date )  {
        if ($event === 'version') {
            continue;
        }

        $date = (new DateTime($date))->format('Y-m-d');

        $events[] = array(
            'start'   => $date,
            'end'     => $date,
            'summary' => Release::getNiceLabel($version, $event) . "<br>\n",
            'mask'    => true
        );
    }
}

return $calendar->addEvents($events);
