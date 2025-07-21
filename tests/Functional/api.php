<?php

include 'init.php';

// Those tests mostly check that our public API works
$paths = [
    ['external/', 200, 'Verif:skip'],
    ['nightly/?date=20220101&nocache', 200, '{"20220101095322":"521f2f0038436e8f0a83146ea6c32eb419049b57","20220101231829":"1fe0ce6b31654726691145ca9dc5f2f15114316c"}'],
    ['release/schedule/?version=1200', 400, '{
    "error": "Not enough data for this version number."
}'],
    ['esr/releases/', 200, 'Verif:skip'],
    ['firefox/releases/', 200, 'Verif:skip'],
    ['firefox/releases/esr/', 200, 'Verif:skip'],
    ['nightly/crashes/', 200, 'Verif:skip'],
    ['beta/crashes/', 200, 'Verif:skip'],
    ['nightly/crashes/?buildid=20191014213051', 200, '{"buildid":"20191014213051","total":0,"signatures":[]}'],
    ['release/schedule/?version=beta', 200, 'Verif:skip'],
    ['release/schedule/?version=nightly', 200, 'Verif:skip'],
    ['release/owners/', 200, 'Verif:skip'],
    ['wellness/days/', 200, 'Verif:skip'],
    ['firefox/releases/future/', 200, 'Verif:skip'],
    ['firefox/calendar/future/', 200, 'Verif:skip'],
    ['firefox/chemspills/', 200, 'Verif:skip'],
];

$obj = new \pchevrel\Verif('Check API HTTP responses');
$obj
    ->setHost('localhost:8083')
    ->setPathPrefix('api/');

$check = function ($object, $paths) {
    foreach ($paths as $values) {
        [$path, $http_code, $content] = $values;
        echo "- $path\n";
        $object
            ->setPath($path)
            ->fetchContent()
            ->hasResponseCode($http_code)
            ->isJson()
            ->isEqualTo($content);
    }
};

echo "\nTesting API path:\n";
$check($obj, $paths);

$obj->report();

// Kill PHP dev server by killing all children processes of the bash process we opened in the background
killTestServer($processID);

// Report the status of the execution, needed for CI
die($obj->returnStatus());
