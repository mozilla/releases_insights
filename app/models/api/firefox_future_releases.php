<?php

declare(strict_types=1);

use ReleaseInsights\{Model, Release, Version};

$data = [];
$format = 'Y-m-d';


// 1. Check if we have a planned dot releases for the current cycle
$current_dot_release = new Release(Version::get(FIREFOX_RELEASE))->getSchedule()['planned_dot_release'] ?? null;
if ($current_dot_release) {
    $date = new DateTime($current_dot_release)->format($format);

    // 2. Check that it is not shipped yet
    if ($date < new DateTime('now')) {
        $data[Version::getMajor(FIREFOX_RELEASE) . '.0.x'] = $date;
    }
}

// Check that we don't have a second planned dot release coming (150 case)
$extra_dot_release = new Release(Version::get(FIREFOX_RELEASE))->getSchedule()['planned_dot_release_2'] ?? null;
if ($extra_dot_release) {
    $date = new DateTime($extra_dot_release)->format($format);

    // 2. Check that it is not shipped yet
    if ($date < new DateTime('now')) {
        $data[Version::getMajor(FIREFOX_RELEASE) . '.0.y'] = $date;
    }
}

foreach (new Model('api_future_calendar')->get() as $key => $values) {
    $data[$key] = $values['release_date'];
    if (isset($values['dot_release'])) {
        $data[$key . '.x'] = new DateTime($values['dot_release'])->format($format);
    }
    if ($values['version'] === 150) {
        $data[$key . '.y'] = new DateTime(
            new Release('150.0')->getSchedule()['planned_dot_release_2']
        )->format($format);
    }
}

return $data;