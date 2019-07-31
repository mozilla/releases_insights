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

$betaquery = 'https://hg.mozilla.org/releases/mozilla-beta/json-pushes?fromchange=FIREFOX_'
    . $params['version']
    . '_0b3_RELEASE&tochange=FIREFOX_BETA_'
    . $params['version']
    . '_END&full&version=2';

$rcquery = 'https://hg.mozilla.org/releases/mozilla-release/json-pushes?fromchange=FIREFOX_BETA_'
    . $params['version']
    . '_END&tochange=FIREFOX_'
    . $params['version']
    . '_0_RELEASE&full&version=2';
