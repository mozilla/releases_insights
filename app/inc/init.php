<?php
namespace ReleaseInsights;

// We always work with UTF8 encoding
mb_internal_encoding('UTF-8');

// Make sure we have a timezone set
date_default_timezone_set('Europe/Paris');

// Load all constants for the application
require_once __DIR__ . '/constants.php';

// Autoloading of classes (both /vendor and /classes)
require_once INSTALL_ROOT . 'vendor/autoload.php';

// Dispatch urls, use it only in web context
if (php_sapi_name() != 'cli') {
    require_once INC . 'dispatcher.php';
}
