<?php

/*
    We don't run any test here, but we can set up globals for all the tests
*/

require __DIR__ . '/../vendor/autoload.php';

const FIREFOX_RELEASE  = '152.0';
const FIREFOX_BETA     = '153.0b8';
const FIREFOX_NIGHTLY  = '154.0a1';
const ESR              = '140.12.0esr';
const ESR_NEXT         = '';
const ESR115           = '115.37.0esr';
const INSTALL_ROOT     = __DIR__ . '/../';
const CONTROLLERS      = INSTALL_ROOT . 'app/controllers/';
const MODELS           = INSTALL_ROOT . 'app/models/';
const DATA             = INSTALL_ROOT . 'app/data/';
const CACHE_PATH       = INSTALL_ROOT . 'cache/';
const TEST_FILES       = INSTALL_ROOT . 'tests/Files/';
const CACHE_ENABLED    = false;
const TESTING_CONTEXT  = true;
const IS_DEV_MODE      = true;

// Major version numbers (integers), used across the app
define('NIGHTLY',     (int) FIREFOX_NIGHTLY);
define('BETA',        (int) FIREFOX_BETA);
define('RELEASE',     (int) FIREFOX_RELEASE);
define('NEXT_ESR',    (int) (ESR_NEXT ?: null));
define('CURRENT_ESR', (int) ESR);
define('WIN7_ESR',    ESR115 ? (int) ESR115 : null);

// Are we on one of our staging sites
define('LOCALHOST',  isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'localhost');
define('STAGING',    isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== 'whattrainisitnow.com' && $_SERVER['SERVER_NAME'] !== 'localhost');
define('PRODUCTION', isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'whattrainisitnow.com');

// Define a Nonce for inline scripts
define('NONCE', bin2hex(random_bytes(20)));

date_default_timezone_set('UTC');
