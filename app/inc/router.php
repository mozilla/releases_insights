<?php

declare(strict_types=1);

use ReleaseInsights\Request;

// We import the Request class manually as we haven't autoloaded classes yet
define('INSTALL_ROOT', realpath(__DIR__ . '/../../') . '/');
include INSTALL_ROOT . 'app/classes/ReleaseInsights/Request.php';

/*
    We can have queries with a colon and a number that lead
    to URLs that parse_url() can't parse probably because it thinks that it is a
    port definition.

    That's why we escape the colon to %3A before parsing it and then revert
    that change in the query variable created.
*/
$url = parse_url(str_replace(':', '%3A', $_SERVER['REQUEST_URI']));

if (isset($url['query'])) {
    $url['query'] = str_replace('%3A', ':', $url['query']);
}

$file = pathinfo($url['path']);

// Real files and folders don't get pre-processed
if (file_exists($_SERVER['DOCUMENT_ROOT'] . $url['path'])
    && $url['path'] !== '/') {
    return false;
}

// Don't process non-PHP files, even if they don't exist on the server
if (isset($file['extension']) && $file['extension'] !== 'php') {
    return false;
}

if ($url['path'] !== '/') {
    $url['path'] = Request::cleanPath($url['path']);
}

// Always redirect to an url ending with slashes
$temp_url = parse_url(str_replace(':', '%3A', $_SERVER['REQUEST_URI']));
if (! str_ends_with($temp_url['path'], '/')) {
    unset($temp_url);
    header('Location:/' . $url['path'] . '/');
    exit;
}

// We can now initialize the application, load all dependencies and dispatch urls
require_once __DIR__ . '/init.php';
