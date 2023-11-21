<?php

declare(strict_types=1);

use ReleaseInsights\Data;

// We always work with UTF8 encoding
mb_internal_encoding('UTF-8');

// Make sure we have a timezone set
date_default_timezone_set('UTC');

// Autoloading of classes (both /vendor/ and /app/classes)
define('INSTALL_ROOT', dirname(__DIR__, 2) . '/');

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
define('CACHE_TIME', 900); // 15 minutes

// Get Firefox Versions from Product Details library, default cache duration
$firefox_versions = (new Data())->getFirefoxVersions();

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
define('OLD_ESR',  (int) (ESR_NEXT != '' ? ESR : ESR_NEXT));

// Are we on one of our staging sites
define('LOCALHOST',  isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'localhost');
define('STAGING',    isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== 'whattrainisitnow.com' && ! LOCALHOST);
define('PRODUCTION', isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'whattrainisitnow.com');

// Define a Nonce for inline scripts
define('NONCE', bin2hex(random_bytes(10)));

// Clean up temp variables from global space
unset($firefox_versions);
