<?php

declare(strict_types=1);

use ReleaseInsights\{Model, Release, Version};

$data = [];
$format = 'Y-m-d';

// 1. Check if we have a planned dot release for the current cycle
$current_dot_release = new Release(Version::get(FIREFOX_RELEASE))->getSchedule()['planned_dot_release'] ?? null;

if ($current_dot_release) {
    $date = new DateTime($current_dot_release)->format($format);

    // 2. Check that it is not shipped yet
    if ($date < new DateTime('now')) {
        $data[Version::getMajor(FIREFOX_RELEASE) . '.0.x'] = $date;
    }
}

foreach (new Model('api_future_calendar')->get() as $key => $values) {
    $data[$key] = $values['release_date'];
    if (isset($values['dot_release'])) {
        $data[$key . '.x'] = new DateTime($values['dot_release'])->format($format);
    }
}

return $data;