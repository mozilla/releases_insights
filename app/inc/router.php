<?php

declare(strict_types=1);

use ReleaseInsights\Request;

// Let's redirect all our traffic to whattrainisitnow.com
if ($_SERVER['SERVER_NAME'] === 'fx-trains.herokuapp.com') {
    header('Location: https://whattrainisitnow.com' . $_SERVER['REQUEST_URI'], true, 301);
    exit;
}

// We import the Request class manually as we haven't autoloaded classes yet
include realpath(__DIR__ . '/../../')  . '/app/classes/ReleaseInsights/Request.php';

$url  = new Request($_SERVER['REQUEST_URI']);
$file = pathinfo($url->path);

// Real files and folders don't get pre-processed
if (file_exists($_SERVER['DOCUMENT_ROOT'] . $url->path) && $url->path !== '/') {
    return false;
}

// Don't process non-PHP files, even if they don't exist on the server
if (isset($file['extension'])) {
    http_response_code(404);
    return false;
}

// Always redirect to an url ending with a single slash
if ($url->invalid_slashes) {
    header('Location:' . $url->path);
    exit;
}

// Block suspicious accesses
require_once __DIR__ . '/IPblock.php';

// Clean up temp variables from global space
unset($url, $file);

// We can now initialize the application, load all dependencies and dispatch urls
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/init.php';
