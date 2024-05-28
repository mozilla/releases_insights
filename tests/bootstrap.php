<?php

/*
    We don't run any test here, but we can set up globals for all the tests
*/

require __DIR__ . '/../vendor/autoload.php';

const FIREFOX_RELEASE  = '93.0';
const FIREFOX_BETA     = '94.0b8';
const FIREFOX_NIGHTLY  = '95.0a1';
const ESR              = '78.10.0esr';
const INSTALL_ROOT     = __DIR__ . '/../';
const MODELS           = INSTALL_ROOT . 'app/models/';
const DATA             = INSTALL_ROOT . 'tests/data/';
const CACHE_PATH       = INSTALL_ROOT . 'cache/';
const TEST_FILES       = INSTALL_ROOT . 'tests/Files/';
const CACHE_ENABLED    = false;
const TESTING_CONTEXT  = true;

// Major version numbers (integers), used across the app
define('NIGHTLY', (int) FIREFOX_NIGHTLY);
define('BETA',    (int) FIREFOX_BETA);
define('RELEASE', (int) FIREFOX_RELEASE);

// Are we on one of our staging sites
define('LOCALHOST',  isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'localhost');
define('STAGING',    isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== 'whattrainisitnow.com' && $_SERVER['SERVER_NAME'] !== 'localhost');
define('PRODUCTION', isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'whattrainisitnow.com');

date_default_timezone_set('UTC');
