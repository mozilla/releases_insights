<?php

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
        $view = '404';
        $page_title = '404: Page Not Found';
        $page_descr = '';
        break;
}

if ($template) {
    ob_start();

    if (isset($view)) {
        include VIEWS . $view . '.php';
    } else {
        include CONTROLLERS . $controller . '.php';
    }

    $content = ob_get_contents();
    ob_end_clean();

    ob_start();

    // display the page
    require_once VIEWS . 'templates/base.php';
    $content = ob_get_contents();
    ob_end_clean();
} else {
    ob_start();
    if (isset($view)) {
        include VIEWS . $view . '.php';
    } else {
        include CONTROLLERS . $controller . '.php';
    }
    $content = ob_get_contents();
    ob_end_clean();
}

print $content;

die;
