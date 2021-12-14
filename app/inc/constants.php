<?php

declare(strict_types=1);

// Constants for the project
define('INSTALL_ROOT', realpath($install) . '/');
define('APP_ROOT', INSTALL_ROOT . 'app/');
define('DATA', APP_ROOT . 'data/');
define('INC', APP_ROOT . 'inc/');
define('VIEWS', APP_ROOT . 'views/');
define('MODELS', APP_ROOT . 'models/');
define('CONTROLLERS', APP_ROOT . 'controllers/');
define('CACHE_ENABLED', ! isset($_GET['nocache']));
define('CACHE_PATH', INSTALL_ROOT . 'cache/');
