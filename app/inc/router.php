<?php

declare(strict_types=1);

use ReleaseInsights\Request;

// We import the Request class manually as we haven't autoloaded classes yet
include realpath(__DIR__ . '/../../')  . '/app/classes/ReleaseInsights/Request.php';

$url = new Request($_SERVER['REQUEST_URI']);

if (isset($url->query)) {
    $url->query = str_replace('%3A', ':', $url->query);
}

// Non parsable path is a 404
if ($url->path === null || ! isset($url->path)) {
    $url->path = '404';
}

$file = pathinfo($url->path);

// Real files and folders don't get pre-processed
if (file_exists($_SERVER['DOCUMENT_ROOT'] . $url->path) && $url->path !== '/') {
    return false;
}

// Don't process non-PHP files, even if they don't exist on the server
if (isset($file['extension']) && $file['extension'] !== 'php') {
    return false;
}

// Always redirect to an url ending with slashes
$temp_url = parse_url(str_replace(':', '%3A', $_SERVER['REQUEST_URI']));
if ($temp_url === false || ! str_ends_with($temp_url['path'], '/')) {
    $location = 'Location:' . $url->path . '/';
    header($location);
    exit;
}

// We can now initialize the application, load all dependencies and dispatch urls
require_once __DIR__ . '/init.php';
