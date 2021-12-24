<?php

// We always work with UTF8 encoding
mb_internal_encoding('UTF-8');

// Make sure we have a timezone set
date_default_timezone_set('America/Los_Angeles');

require __DIR__ . '/../../vendor/autoload.php';

// Launch PHP dev server in the background
chdir(realpath(__DIR__ . '/../../'));
echo getcwd();
exec('php -S localhost:8083 -t public/ > /dev/null 2>&1 & echo $!', $output);

// We will need the pid to kill it, beware, this is the pid of the bash process started with start.sh
$pid = $output[0];
