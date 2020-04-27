<?php

Use ReleaseInsights\Utils as Utils;

/* Default values for pages, can be overriden in the switch. */

// We can insert an id to the body of an html page to apply separate styles
$css_page_id = '';

// List of css files we include, by default only base.css
$css_files = ['base.css'];

// Here we decide what page we are asking for, falls back to a 404
switch ($url['path']) {
    case '/':
        $controller = 'homepage';
        $css_page_id = 'home';
        $page_title = 'Firefox Desktop Release Insights tools';
        break;
    case 'nightly':
        $controller = 'nightly';
        $css_page_id = 'todayinnightly';
        $page_title = 'Nightly crashes for a day';
        break;
    case 'beta':
        $controller = 'beta';
        $css_page_id = 'beta';
        $page_title = 'General statistics for betas';
        break;
    case 'release':
        $controller = 'release';
        $css_page_id = 'release';
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
