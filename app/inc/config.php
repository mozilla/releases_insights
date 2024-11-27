<?php

declare(strict_types=1);

use ReleaseInsights\Data;
use function Sentry\init;

// We always work with UTF8 encoding
mb_internal_encoding('UTF-8');

// Make sure we have a timezone set
date_default_timezone_set('UTC');

// Application globals paths
define('INSTALL_ROOT', dirname(__DIR__, 2) . '/');
const CONTROLLERS = INSTALL_ROOT . 'app/controllers/';
const DATA        = INSTALL_ROOT . 'app/data/';
const MODELS      = INSTALL_ROOT . 'app/models/';
const VIEWS       = INSTALL_ROOT . 'app/views/';
const TEST_FILES  = INSTALL_ROOT . 'tests/Files/';
const CACHE_PATH  = INSTALL_ROOT . 'cache/';

// Prepare caching
define('CACHE_ENABLED', ! isset($_GET['nocache']));
define('CACHE_TIME', 900); // 15 minutes

// Autoloading of classes (both /vendor/ and /app/classes)
require_once INSTALL_ROOT . 'vendor/autoload.php';

// Get Firefox Versions from Product Details library, default cache duration
$firefox_versions = new Data()->getFirefoxVersions();

// Exact version numbers (strings) from product-details
define('ESR',             $firefox_versions['FIREFOX_ESR']);
define('ESR_NEXT',        $firefox_versions['FIREFOX_ESR_NEXT']);
define('ESR115',          $firefox_versions['FIREFOX_ESR115']);
define('FIREFOX_NIGHTLY', $firefox_versions['FIREFOX_NIGHTLY']);
define('DEV_EDITION',     $firefox_versions['FIREFOX_DEVEDITION']);
define('FIREFOX_BETA',    $firefox_versions['LATEST_FIREFOX_RELEASED_DEVEL_VERSION']);
define('FIREFOX_RELEASE', $firefox_versions['LATEST_FIREFOX_VERSION']);

// Major version numbers (integers), used across the app
define('NIGHTLY',  (int) FIREFOX_NIGHTLY);
define('BETA',     (int) FIREFOX_BETA);
define('RELEASE',  (int) FIREFOX_RELEASE);
define('MAIN_ESR', (int) (ESR_NEXT != '' ? ESR_NEXT : ESR));
define('OLD_ESR',  (int) (ESR115 != '' ? ESR115 : (ESR_NEXT != '' ? ESR : ESR_NEXT)));

// Are we on one of our staging sites
$http_host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : null;

define('LOCALHOST',
    ! is_null($http_host)
    && (
        str_starts_with($http_host, 'localhost')
        || str_starts_with($http_host, '127.0.0.1')
    )
);

define('STAGING',
    ! is_null($http_host)
    && $http_host !== 'whattrainisitnow.com'
    && ! LOCALHOST
);

define('PRODUCTION',
    ! is_null($http_host)
    && $http_host === 'whattrainisitnow.com'
);

// Define a Nonce for inline scripts
define('NONCE', bin2hex(random_bytes(20)));

// Set up Sentry endpoint, don't send errors while in dev mode
if (STAGING) {
    init(['dsn' => 'https://e17dcdc892db4ee08a6937603e407f76@o1069899.ingest.sentry.io/4505243444772864']);
}

if (PRODUCTION) {
    init(['dsn' => 'https://20bef71984594e16add1d2c69146ad88@o1069899.ingest.sentry.io/4505243430092800']);
}

// Clean up temp variables from global space
unset($firefox_versions, $http_host);
