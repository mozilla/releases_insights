<?php

// We always work with UTF8 encoding
mb_internal_encoding('UTF-8');

// Make sure we have a timezone set
date_default_timezone_set('UTC');

require __DIR__ . '/../../vendor/autoload.php';

// Launch PHP dev server in the background
chdir(realpath(__DIR__ . '/../../'));
echo getcwd();
// We pass an env variable to the php process because we want to disable Ignition in testing mode
exec('TESTING_CONTEXT=true php -S localhost:8083 -t public/ > /dev/null 2>&1 & echo $!', $output);

// We will need the pid to kill it, beware, this is the pid of the bash process started with start.sh
$processID = $output[0];

// Pause to let time for the dev server to launch in the background in CI, locally it's almost instant
for ($i = 0; $i < 300; $i++) {
    $socket = @fsockopen('localhost', 8083);

    if ($socket !== false) {
        break;
    }

    // 10ms per try
    usleep(10_000);
}

// Create a file in the cache folder to indicate that we are in dev mode for port 8083 as well
touch(realpath(__DIR__ . '/../../cache/')  . '/devmachine.cache');


// This is the function to call to stop the test server in sub-scripts
function killTestServer(string $processID): void {
    unlink(realpath(__DIR__ . '/../../cache/')  . '/devmachine.cache');
    exec('kill -9 ' . $processID);
}
