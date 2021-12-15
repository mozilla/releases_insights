<?php

declare(strict_types=1);

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
    // Normalize path before comparing the string to list of valid paths
    $url['path'] = explode('/', $url['path']);
    $url['path'] = array_filter($url['path']); // Remove empty items
    $url['path'] = array_values($url['path']); // Reorder keys
    $url['path'] = implode('/', $url['path']);
}

// Always redirect to an url ending with slashes
$temp_url = parse_url(str_replace(':', '%3A', $_SERVER['REQUEST_URI']));
if (substr($temp_url['path'], -1) !== '/') {
    unset($temp_url);
    header('Location:/' . $url['path'] . '/');
    exit;
}

// We can now initialize the application, load all dependencies and dispatch urls
define('INSTALL_ROOT', realpath(__DIR__ . '/../../') . '/');
require_once __DIR__ . '/init.php';
