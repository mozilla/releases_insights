<?php

// We always work with UTF8 encoding
mb_internal_encoding('UTF-8');

// Make sure we have a timezone set
date_default_timezone_set('Europe/Paris');

// Load all constants for the application, hardcoded.
// TODO:create a config.ini-dist file
if (php_sapi_name() == 'cli-server' || php_sapi_name() == 'cli') {
    $install = '/home/pascalc/repos/github/releases_insights';
} else {
    $install = '/home/pascalc/releases_insights';
}

require_once __DIR__.'/constants.php';

// Autoloading of classes (both /vendor and /classes)
require_once INSTALL_ROOT.'vendor/autoload.php';

// Dispatch urls, we do that only in a Web server context (dev or prod)
if (php_sapi_name() != 'cli') {
    require_once INC.'dispatcher.php';
}
