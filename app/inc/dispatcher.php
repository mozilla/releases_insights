<?php

Use ReleaseInsights\Utils as Utils;


// List of css files we include, by default only base.css
$css_files = ['base.css'];

// Here we decide what page we are asking for, falls back to a 404
switch ($url['path']) {
    case '/':
        $controller = 'homepage';
        $page_title = 'Firefox Desktop Release Insights tools';
        break;
    case 'overview':
        $controller = 'overview';
        $page_title = 'Where are we in the current release cycle?';
        break;
    case 'nightly':
        $controller = 'nightly';
        $page_title = 'Nightly crashes for a day';
        break;
    case 'beta':
        $controller = 'beta';
        $page_title = 'General statistics for betas';
        break;
    case 'release':
        $controller = 'release';
        $page_title = 'General statistics for releases';
        break;
    case 'api/nightly':
        $controller = 'api_nightly';
        break;
    case 'api/nightly/crashes':
        $controller = 'api_nightly_crashes';
        break;
    default:
        $controller = '404';
        $page_title = '404: Page Not Found';
        break;
}

include CONTROLLERS . $controller . '.php';
