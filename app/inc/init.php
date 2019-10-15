<?php

// We always work with UTF8 encoding
mb_internal_encoding('UTF-8');

// Make sure we have a timezone set
date_default_timezone_set('America/Los_Angeles');

// Load all constants for the application, hardcoded.
// TODO:create a config.ini-dist file
if (php_sapi_name() == 'cli-server' || php_sapi_name() == 'cli') {
    $install = '/home/pascalc/repos/github/releases_insights';
} elseif (in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    $install = '/home/pascalc/repos/github/releases_insights';
} else {
    $install = '/home/pascalc/releases_insights';
}

require_once __DIR__.'/constants.php';

// Autoloading of classes (both /vendor and /classes)
require_once INSTALL_ROOT.'vendor/autoload.php';

// Initialie our Templating system
$twig_loader = new Twig_Loader_Filesystem(INSTALL_ROOT.'app/views/templates');
$twig = new Twig_Environment($twig_loader, ['cache' => false]);

// Dispatch urls, we do that only in a Web server context (dev or prod)
if (php_sapi_name() != 'cli') {
    require_once INC.'dispatcher.php';
}
