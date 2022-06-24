<?php

include 'init.php';

$paths = [
    ['404/', 404, '404: Page Not Found', 'id="notfound"'],
    ['yolo', 302, '', ''], // Test that we redirect yolo to yolo/
    ['', 200, 'Firefox Trains', 'id="homepage"'],
    ['about/', 200, 'All APIs are under the', 'id="about"'],
    ['nightly/', 200, '', 'id="nightly"'],
    ['release/', 200, 'Release Owner', 'id="release"'],
    ['release/owners/', 200, 'Major releases per owner since Version 27', 'id="release_owners"'],
    ['calendar/release/schedule/?version=beta', 200, 'BEGIN:VCALENDAR', 'END:VCALENDAR'],
];

$obj = new \pchevrel\Verif('Check public pages HTTP responses and content');
$obj
    ->setHost('localhost:8083')
    ->setPathPrefix('');

$check = function ($object, $paths) {
    foreach ($paths as $values) {
        list($path, $http_code, $content, $content2) = $values;
        $object
            ->setPath($path)
            ->fetchContent()
            ->hasResponseCode($http_code)
            ->contains($content)
            ->contains($content2);
    }
};

$check($obj, $paths);

$obj->report();

// Kill PHP dev server by killing all children processes of the bash process we opened in the background
killTestServer($processID);

// Report the status of the execution, needed for CI
die($obj->returnStatus());
