<?php

declare(strict_types=1);

use ReleaseInsights\{Model, Release, Version};

$data = [];
$format = 'Y-m-d';


// Check if we have planned dot releases for the current cycle
$planned_dot_release = function(string $release, string $dot_release) use (&$data, $format) {
    $schedule = new Release(Version::get($release))->getSchedule();
    if (isset($schedule[$dot_release])) {
        $date = new DateTime($schedule[$dot_release]);

        if ($date >= new DateTime()) {
            // Get the last character of the string
            $number = substr($dot_release, -1);

            // Map the number to the desired suffix
            $suffix = match ($number) {
                '1' => 'x',
                '2' => 'y',
                '3' => 'z',
                default => null, // Handle cases where the number doesn't match 1, 2, or 3
            };

            $data[Version::getMajor($release) . '.0.' . $suffix] = $date->format($format);
        }
    }
};

$planned_dot_release(FIREFOX_RELEASE, 'dot_release_1');
$planned_dot_release(FIREFOX_RELEASE, 'dot_release_2');
$planned_dot_release(FIREFOX_RELEASE, 'dot_release_3');

foreach (new Model('api_future_calendar')->get() as $key => $values) {
    $data[$key] = $values['release_date'];
    $planned_dot_release($key, 'dot_release_1');
    $planned_dot_release($key, 'dot_release_2');
    $planned_dot_release($key, 'dot_release_3');
}

return $data;