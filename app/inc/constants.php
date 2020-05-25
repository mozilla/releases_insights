<?php

// Constants for the project
define('INSTALL_ROOT', realpath($install) . '/');
define('WEB_ROOT', INSTALL_ROOT . 'public/');
define('APP_ROOT', INSTALL_ROOT . 'app/');
define('DATA', APP_ROOT . 'data/');
define('INC', APP_ROOT . 'inc/');
define('VIEWS', APP_ROOT . 'views/');
define('MODELS', APP_ROOT . 'models/');
define('CONTROLLERS', APP_ROOT . 'controllers/');
define('CACHE_ENABLED', isset($_GET['nocache']) ? false : true);
define('CACHE_PATH', INSTALL_ROOT . 'cache/');
define('APP_SCHEME', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https://' : 'http://');

if (PHP_SAPI != 'cli-server') {
    define('API_ROOT', APP_SCHEME . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_QUOTES, 'UTF-8') . '/api/');
}
