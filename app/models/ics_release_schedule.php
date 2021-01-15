<?php

use Eluceo\iCal\Component\{Calendar, Event};
use ReleaseInsights\Utils;


$short_version = (string) (int) $releases['version'];

$release_schedule_labels = [
    'nightly_start'     => 'Nightly ' . $short_version . ' starts',
    'soft_code_freeze'  => 'Firefox ' . $short_version . ' soft Code Freeze starts',
    'string_freeze'     => 'String Freeze starts',
    'merge_day'         => 'Merge day',
    'beta_1'            => 'Firefox ' . $releases['version'] . 'b1',
    'beta_2'            => 'Firefox ' . $releases['version'] . 'b2',
    'beta_3'            => 'Firefox ' . $releases['version'] . 'b3',
    'beta_4'            => 'Firefox ' . $releases['version'] . 'b4',
    'beta_5'            => 'Firefox ' . $releases['version'] . 'b5',
    'beta_6'            => 'Firefox ' . $releases['version'] . 'b6',
    'beta_7'            => 'Firefox ' . $releases['version'] . 'b7',
    'beta_8'            => 'Firefox ' . $releases['version'] . 'b8',
    'beta_9'            => 'Firefox ' . $releases['version'] . 'b9',
    'beta_10'           => 'Firefox ' . $releases['version'] . 'b10',
    'rc_gtb'            => 'Firefox ' . $short_version . ' go to Build',
    'rc'                => 'RC',
    'release'           => 'Firefox ' . $short_version . ' go-live @ 6am PT'
];

$calendar = new Calendar('Firefox ' . $short_version);

foreach ($releases as $label => $date) {

    if ($label == 'version' || $label == 'rc') {
        continue;
    }

    $event = new Event();


    if ($label == 'soft_code_freeze') {

        $start = new DateTime($date);
        $end   = new DateTime($date);

        $event
            ->setDtStart($start)
            ->setDtEnd($end->modify('Sunday'))
            ->setNoTime(true)
            ->setSummary($release_schedule_labels[$label])
        ;
    } else {
        $event
            ->setDtStart(new DateTime($date))
            ->setDtEnd(new DateTime($date))
            ->setNoTime(true)
            ->setSummary($release_schedule_labels[$label])
        ;
    }

    $calendar->addComponent($event);
}


$ics_calendar = $calendar->render();
$filename = 'Firefox_' . $short_version . '_schedule.ics';
