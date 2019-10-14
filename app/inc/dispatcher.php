<?php

Use \ReleaseInsights\Utils as Utils;

/* Default values for pages, can be overriden in the switch. */

// Do we insert the page in our default template base.php?
$template = true;

// We can insert an id to the body of an html page to apply separate styles
$css_page_id = '';

// List of css files we include, by default only base.css
$css_files = ['base.css'];

// Here we decide what page we are asking for, falls back to a 404
switch ($url['path']) {
    case '/':
        $controller = 'homepage';
        $page_title = 'Firefox Release Insights tools';
        break;
    case 'nightly':
        $controller = 'nightly';
        $page_title = 'Nightly crashes for a day';
        break;
    case 'api/nightly':
        $controller = 'api_nightly';
        $template = false;
        break;
    default:
        $controller = '404';
        $page_title = '404: Page Not Found';
        break;
}

// Pages can be output directly and not go into a template, especially json raw files
if (!$template) {
    echo Utils::includeBuffering(CONTROLLERS.$controller.'.php');
} else {
    $content = Utils::includeBuffering(CONTROLLERS.$controller.'.php');
    include VIEWS.'templates/base.php';
}
