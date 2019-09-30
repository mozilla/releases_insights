<?php
namespace ReleaseInsights;

if ($api_url) {
    $page = 'api';
} else {
    $page = isset($urls[$url['path']]) ? $urls[$url['path']] : 'notfound';
}

$template = true;
$extra = null;
$experimental = false;
$show_title = true;
$css_files = ['transvision.css'];
$js_files = ['/js/base.js'];

switch ($url['path']) {
    case '/':
        $controller = 'homepage';
        $show_title = false;
        break;
    case Strings::StartsWith($url['path'], 'api'):
        $controller = 'api';
        $page_title = 'API response';
        $page_descr = '';
        $template = false;
        break;
    case 'credits':
        $view = 'credits';
        $page_title = 'Credits';
        $page_descr = '';
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

ob_start();
ob_end_clean();

print $perf_header . $content;

die;
