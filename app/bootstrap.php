<?php

require_once 'config.php';
require_once 'utils.php';
require_once 'uplift.php';


$url = parse_url($_SERVER['REQUEST_URI']);

if (isset($url['query'])) {
    parse_str($url['query'], $params);
    $params = secureText($params);
}

if (isset($params['version'])) {
    $params['version'] = (int) $params['version'];
} else {
    $params['version'] = 67;
}

// boolean to redirect to bug list on bugzilla
$redirect = isset($params['redirect']);

// boolean to output json instead of html
$json = isset($params['json']);

if (! in_array($params['version'], $supported_releases)) {
    exit("release not supported");
}

switch ($params['version']) {
    case '60':
        $last_beta = '16';
        break;
    case '61':
        $last_beta = '14';
        break;
    case '62':
        $last_beta = '20';
        break;
    case '63':
        $last_beta = '14';
        break;
    case '64':
        $last_beta = '14';
        break;
    case '65':
        $last_beta = '12';
        break;
    case '66':
        $last_beta = '14';
        break;
    case '67':
        $last_beta = '19';
        break;
    case '68':
        $last_beta = '14';
        break;
    default:
        break;
}

$query = 'https://hg.mozilla.org/releases/mozilla-beta/json-pushes?fromchange=FIREFOX_'
    . $params['version']
    . '_0b3_RELEASE&tochange=FIREFOX_'
    . $params['version']
    . '_0b'
    . $last_beta
    . '_RELEASE&full&version=2';

