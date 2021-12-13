<?php

declare(strict_types=1);

// List of css files we include, by default only base.css
$css_files = ['base.css'];

// Here we decide what page we are asking for, falls back to a 404
switch ($url['path']) {
    case '/':
        $controller = 'homepage';
        $page_title = 'Where are we in the current release cycle?';
        break;
    case 'about':
        $controller = 'about';
        $page_title = 'Firefox Desktop Release insights';
        break;
    case 'nightly':
        $controller = 'nightly';
        $page_title = 'Nightly crashes for a day';
        break;
    case 'release':
        $controller = 'release';
        $page_title = 'General statistics for releases';
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
        $page_title = '404: Page Not Found';
        break;
}

include CONTROLLERS . $controller . '.php';
