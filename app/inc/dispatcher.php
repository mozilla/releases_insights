<?php

declare(strict_types=1);

// Here we decide what page we are asking for, falls back to a 404
$controller = match ($url['path']) {
    '/'                         => 'homepage',
    'about'                     => 'about',
    'nightly'                   => 'nightly',
    'release'                   => 'release',
    'api/nightly'               => 'api/nightly',
    'api/release/schedule'      => 'api/release_schedule',
    'api/release/owners'        => 'api/release_owners',
    'api/nightly/crashes'       => 'api/nightly_crashes',
    'calendar/release/schedule' => 'ics_release_schedule',
    default => '404',
};

include CONTROLLERS . $controller . '.php';
