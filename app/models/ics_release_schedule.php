<?php

use Eluceo\iCal\Component\{Calendar, Event};
use ReleaseInsights\Utils;


$short_version = (string) (int) $releases['version'];

$release_schedule_labels = [
    'nightly_start'     => 'Nightly ' . $short_version . ' starts',
    'soft_code_freeze'  => 'Firefox ' . $short_version . ' soft Code Freeze',
    'string_freeze'     => 'String Freeze starts',
    'merge_day'         => 'Merge day',
    'beta_1'            => 'Firefox ' . $releases['version'] . 'b1',
    'beta_2'            => 'Firefox ' . $releases['version'] . 'b2 (GTB: 21:00 UTC)',
    'beta_3'            => 'Firefox ' . $releases['version'] . 'b3 (GTB: 21:00 UTC)',
    'beta_4'            => 'Firefox ' . $releases['version'] . 'b4 (GTB: 21:00 UTC)',
    'beta_5'            => 'Firefox ' . $releases['version'] . 'b5 (GTB: 21:00 UTC)',
    'beta_6'            => 'Firefox ' . $releases['version'] . 'b6 (GTB: 21:00 UTC)',
    'beta_7'            => 'Firefox ' . $releases['version'] . 'b7 (GTB: 21:00 UTC)',
    'beta_8'            => 'Firefox ' . $releases['version'] . 'b8 (GTB: 21:00 UTC)',
    'beta_9'            => 'Firefox ' . $releases['version'] . 'b9 (GTB: 21:00 UTC)',
    'beta_10'           => 'Firefox ' . $releases['version'] . 'b10 (GTB: 21:00 UTC)',
    'beta_11'           => 'Firefox ' . $releases['version'] . 'b11 (GTB: 21:00 UTC)',
    'beta_12'           => 'Firefox ' . $releases['version'] . 'b12 (GTB: 21:00 UTC)',
    'beta_13'           => 'Firefox ' . $releases['version'] . 'b13 (GTB: 21:00 UTC)',
    'beta_14'           => 'Firefox ' . $releases['version'] . 'b14 (GTB: 21:00 UTC)',
    'beta_15'           => 'Firefox ' . $releases['version'] . 'b15 (GTB: 21:00 UTC)',
    'rc_gtb'            => 'Firefox ' . $short_version . ' go to Build',
    'rc'                => 'RC',
    'release'           => 'Firefox ' . $short_version . ' go-live @ 6am PT'
];

// Add end of early betas to the schedule
$early_beta_end = new DateTime($releases['beta_6']);
if ($short_version == '89') {
    $early_beta_end = new DateTime($releases['beta_12']);
}
$releases['early_beta_end'] = $early_beta_end->modify('+1 day')->format('Y-m-d H:i');
$release_schedule_labels['early_beta_end'] = 'End of EARLY_BETA_OR_EARLIER';

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
