<?php

declare(strict_types=1);

use ReleaseInsights\Data;
use function Sentry\init;

// We always work with UTF8 encoding
mb_internal_encoding('UTF-8');

// Make sure we have a timezone set
date_default_timezone_set('UTC');

// Set locale to en-US, avoids setting it in every twig template
locale_set_default('en-US');

// Application globals paths
define('INSTALL_ROOT', dirname(__DIR__, 2) . '/');

const CONTROLLERS = INSTALL_ROOT . 'app/controllers/';
const DATA        = INSTALL_ROOT . 'app/data/';
const MODELS      = INSTALL_ROOT . 'app/models/';
const VIEWS       = INSTALL_ROOT . 'app/views/';
const TEST_FILES  = INSTALL_ROOT . 'tests/Files/';
const CACHE_PATH  = INSTALL_ROOT . 'cache/';
const WEB_ROOT    = INSTALL_ROOT . 'public/';

// Prepare caching. The ?nocache bypass is for local development only, it is
// gated on an env variable (not the Host header, which clients control) so that
// it can't be used to hammer our upstream data sources in production.
define('CACHE_ENABLED', ! (isset($_GET['nocache']) && getenv('DEV_MODE') === 'true'));
define('CACHE_TIME', 900); // 15 minutes

// Autoloading of classes (both /vendor/ and /app/classes)
require_once INSTALL_ROOT . 'vendor/autoload.php';

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

// Are we in a dev mode where debug is activated and twig templates not cached?
define('IS_DEV_MODE', getenv('DEV_MODE') === 'true');

/*
    Set up Sentry as early as possible so that errors during startup (such as
    fetching Product Details below) are reported. Not used on localhost.
    deployed-version.txt is generated at build time on all our deployments.
 */
if (STAGING || PRODUCTION) {
    $deployed_version = is_file(WEB_ROOT . 'deployed-version.txt')
        ? trim((string) file_get_contents(WEB_ROOT . 'deployed-version.txt'))
        : '';

    init([
        'dsn' => PRODUCTION
            ? 'https://20bef71984594e16add1d2c69146ad88@o1069899.ingest.sentry.io/4505243430092800'
            : 'https://e17dcdc892db4ee08a6937603e407f76@o1069899.ingest.sentry.io/4505243444772864',
        'environment' => PRODUCTION ? 'production' : 'staging',
        'release'     => preg_match('/^[0-9a-f]{40}$/', $deployed_version) ? $deployed_version : null,
    ]);
}

// PHP sends no User-Agent at all on stream based requests. Set ours as the
// default so that anything we didn't route through Utils::httpClient() or
// Utils::streamContext() is still identifiable by the sites we query.
ini_set('user_agent', ReleaseInsights\Utils::USER_AGENT);

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
define('NIGHTLY',     (int) FIREFOX_NIGHTLY);
define('BETA',        (int) FIREFOX_BETA);
define('RELEASE',     (int) FIREFOX_RELEASE);
define('NEXT_ESR',    (int) (ESR_NEXT ?: null));
define('CURRENT_ESR', (int) ESR);
define('WIN7_ESR',    ESR115 ? (int) ESR115 : null);

// Define a Nonce for inline scripts
define('NONCE', bin2hex(random_bytes(20)));

// Clean up temp variables from global space
unset($firefox_versions, $http_host, $deployed_version);
