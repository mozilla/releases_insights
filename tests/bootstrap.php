<?php

/*
    We don't run any test here, but we can set up globals for all the tests
*/

require_once __DIR__ . '/../vendor/autoload.php';

const FIREFOX_RELEASE = '93';
const FIREFOX_BETA    = '94';
const FIREFOX_NIGHTLY = '95';
const ESR             = '78';
const UNIT_TESTING    = true;
const INSTALL_ROOT    = __DIR__ . '/../';
const DATA            = INSTALL_ROOT . 'app/data/';
const CACHE_PATH      = INSTALL_ROOT . 'cache/';
const TEST_FILES      = INSTALL_ROOT . 'tests/Files/';
const CACHE_ENABLED   = false;

// Major version numbers (integers), used across the app
define('NIGHTLY', (int) FIREFOX_NIGHTLY);
define('BETA',    (int) FIREFOX_BETA);
define('RELEASE', (int) FIREFOX_RELEASE);

date_default_timezone_set('UTC');