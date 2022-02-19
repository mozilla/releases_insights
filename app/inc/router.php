<?php

declare(strict_types=1);

// We import the Request class manually as we haven't autoloaded classes yet
include realpath(__DIR__ . '/../../')  . '/app/classes/ReleaseInsights/Request.php';

$url = new ReleaseInsights\Request();
$file = pathinfo($url->path);

// Real files and folders don't get pre-processed
if (file_exists($_SERVER['DOCUMENT_ROOT'] . $url->path) && $url->path !== '/') {
    return false;
}

// Don't process non-PHP files, even if they don't exist on the server
if (isset($file['extension']) && $file['extension'] !== 'php') {
    return false;
}

// Clean up temp variables from global space
unset($url, $file);

// We can now initialize the application, load all dependencies and dispatch urls
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/init.php';
