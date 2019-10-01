<?php

Use \ReleaseInsights\Utils as Utils;

// Default values for pages, can be overriden in the switch
$show_title = true;
$template = true;
$page = '';
$css_files = ['base.css'];
$js_files = [];

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
        $page_title = 'API response';
        $page_descr = '';
        $template = false;
        break;
    default:
        $page_title = '404: Page Not Found';
        $page_descr = '';
        break;
}

if (!$template) {
    echo Utils::includeBuffering(CONTROLLERS.$controller.'.php');
    exit;
}

$content = Utils::includeBuffering(CONTROLLERS.$controller.'.php');
include VIEWS.'templates/base.php';
exit;
