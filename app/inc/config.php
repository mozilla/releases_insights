<?php

declare(strict_types=1);

// We always work with UTF8 encoding
mb_internal_encoding('UTF-8');

// Make sure we have a timezone set
date_default_timezone_set('UTC');

// Autoloading of classes (both /vendor/ and /app/classes)
define('INSTALL_ROOT', realpath(__DIR__ . '/../../') . '/');
require_once INSTALL_ROOT . 'vendor/autoload.php';

// Application globals paths
const CONTROLLERS = INSTALL_ROOT . 'app/controllers/';
const DATA        = INSTALL_ROOT . 'app/data/';
const MODELS      = INSTALL_ROOT . 'app/models/';
const VIEWS       = INSTALL_ROOT . 'app/views/';
const TEST_FILES  = INSTALL_ROOT . 'tests/Files/';
const CACHE_PATH  = INSTALL_ROOT . 'cache/';

// Prepare caching
define('CACHE_ENABLED', ! isset($_GET['nocache']));
define('CACHE_TIME',    3600 * 72); // 3 days

// Cache Product Details versions, 15mn cache
$firefox_versions = (new ReleaseInsights\Data())->getFirefoxVersions();

// Exact version numbers (strings) from product-details
define('ESR',             $firefox_versions['FIREFOX_ESR']);
define('ESR_NEXT',        $firefox_versions['FIREFOX_ESR_NEXT']);
define('FIREFOX_NIGHTLY', $firefox_versions['FIREFOX_NIGHTLY']);
define('DEV_EDITION',     $firefox_versions['FIREFOX_DEVEDITION']);
define('FIREFOX_BETA',    $firefox_versions['LATEST_FIREFOX_RELEASED_DEVEL_VERSION']);
define('FIREFOX_RELEASE', $firefox_versions['LATEST_FIREFOX_VERSION']);

// Major version numbers (integers), used across the app
define('NIGHTLY',  (int) FIREFOX_NIGHTLY);
define('BETA',     (int) FIREFOX_BETA);
define('RELEASE',  (int) FIREFOX_RELEASE);
define('MAIN_ESR', (int) (ESR_NEXT != '' ? ESR_NEXT : ESR));

// Clean up temp variables from global space
unset($firefox_versions);