<?php

declare(strict_types=1);

use ReleaseInsights\ReleaseCalendar;
use ReleaseInsights\Utils as U;

test('ReleaseCalendar::getICS', function () {
    $releases = [
        "version" 			=> "95.0",
        "nightly_start" 	=> "2021-10-04 00:00",
        "soft_code_freeze" 	=> "2021-10-28 00:00",
        "string_freeze" 	=> "2021-10-29 00:00",
        "merge_day" 		=> "2021-11-01 00:00",
        "beta_1" 			=> "2021-11-01 00:00",
        "beta_2" 			=> "2021-11-02 00:00",
        "beta_3" 			=> "2021-11-04 00:00",
        "beta_4" 			=> "2021-11-07 00:00",
        "beta_5" 			=> "2021-11-09 00:00",
        "beta_6" 			=> "2021-11-11 00:00",
        "beta_7" 			=> "2021-11-14 00:00",
        "beta_8" 			=> "2021-11-16 00:00",
        "beta_9" 			=> "2021-11-18 00:00",
        "beta_10" 			=> "2021-11-21 00:00",
        "beta_11" 			=> "2021-11-23 00:00",
        "beta_12" 			=> "2021-11-25 00:00",
        "rc_gtb"  			=> "2021-11-29 00:00",
        "rc" 				=> "2021-11-30 00:00",
        "release" 			=> "2021-12-07 00:00",
    ];

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
        'release'           => 'Firefox ' . $short_version . ' go-live @ 6am PT',
    ];

    $data = ReleaseCalendar::getICS(
        $releases,
        $release_schedule_labels,
        'Firefox ' . $short_version
    );

    expect($data)->toBeString();
    expect($data)->toStartWith('BEGIN:VCALENDAR')->and($data)->toEndWith('END:VCALENDAR');

    // Remove random UIDs and test content
    $func = fn($value) => ! U::startsWith($value, ['UID', 'DTSTAMP']);

    $clean_array = function ($input) {
        $output = explode("\r\n", $input);
        $output = array_filter($output); // remove empty items
        $output = array_filter(
            $output,
            fn($value) => ! U::startsWith($value, ['UID', 'DTSTAMP'])
        );

        return $output;
    };

    $reference = file_get_contents(__DIR__ . '/../../Files/calendar.ics');

    $this->assertEquals($clean_array($data), $clean_array($reference));


    // Future releases ICS testing
    $releases = [
        '98.0' => '2021-12-07 00:00',
    ];

    $data = ReleaseCalendar::getICS(
        $releases,
        [],
        'Firefox_major_releases_schedule.ics'
    );

    expect($data)->toBeString();
    expect($data)
        ->toStartWith('BEGIN:VCALENDAR')
        ->and($data)->toContain('Firefox 98 go-live @ 06:00 AM PT')
        ->and($data)->toContain('DTSTART;VALUE=DATE:20211207')
        ->and($data)->toEndWith('END:VCALENDAR');
});
