<?php
use Cache\Cache;
use ReleaseInsights\Utils as Utils;

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

// Common constants
require_once __DIR__ . '/constants.php';

// Autoloading of classes (both /vendor and /classes)
require_once INSTALL_ROOT . 'vendor/autoload.php';

// Caching defaults
const CACHE_TIME = 3600*6;

// Cache Product Details versions, we use Firefox version numbers in most views
$firefox_versions = Utils::getJson('https://product-details.mozilla.org/1.0/firefox_versions.json');

define('ESR', $firefox_versions["FIREFOX_ESR"]);
define('ESR_NEXT', $firefox_versions["FIREFOX_ESR_NEXT"]);
define('FIREFOX_NIGHTLY', $firefox_versions["FIREFOX_NIGHTLY"]);
define('DEV_EDITION', $firefox_versions["FIREFOX_DEVEDITION"]);
define('FIREFOX_BETA', $firefox_versions["LATEST_FIREFOX_RELEASED_DEVEL_VERSION"]);
define('FIREFOX_RELEASE', $firefox_versions["LATEST_FIREFOX_VERSION"]);

$main_nightly = (int) FIREFOX_NIGHTLY;
$main_beta    = (int) FIREFOX_BETA;
$main_release = (int) FIREFOX_RELEASE;
$main_esr     = (int) (ESR_NEXT != "" ? ESR_NEXT : ESR);
$last_beta    = (int) str_replace($main_beta .'.0b', '', FIREFOX_BETA);

// Initialize our Templating system
$twig_loader = new Twig_Loader_Filesystem(INSTALL_ROOT . 'app/views/templates');
$twig = new Twig_Environment($twig_loader, ['cache' => false]);

// Dispatch urls, we do that only in a Web server context (dev or prod)
if (php_sapi_name() != 'cli') {
    require_once INC . 'dispatcher.php';
}
