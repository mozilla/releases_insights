<?php

declare(strict_types=1);

// Here we decide what page we are asking for, falls back to a 404
switch ($url['path']) {
    case '/':
        $controller = 'homepage';
        break;
    case 'about':
        $controller = 'about';
        break;
    case 'nightly':
        $controller = 'nightly';
        break;
    case 'release':
        $controller = 'release';
        break;
    case 'api/nightly':
        $controller = 'api/nightly';
        break;
    case 'api/release/schedule':
        $controller = 'api/release_schedule';
        break;
    case 'api/release/owners':
        $controller = 'api/release_owners';
        break;
    case 'api/nightly/crashes':
        $controller = 'api/nightly_crashes';
        break;
    case 'calendar/release/schedule':
        $controller = 'ics_release_schedule';
        break;
    default:
        $controller = '404';
        break;
}

include CONTROLLERS . $controller . '.php';
