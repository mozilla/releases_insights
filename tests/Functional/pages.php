<?php

include 'init.php';

$paths = [
    ['404/', 404, '404: Page Not Found', 'id="notfound"'],
    ['somerandomfile.png', 404, '', ''], // missing files, not pages, don't get a 404 page, just a 404 http response code
    ['yolo', 302, '', ''], // Test that we redirect yolo to yolo/
    ['//', 302, '', ''], // Test that we redirect pages starting with multiple slases to the homepage
    ['//yolo', 302, '', ''], // Test that we redirect pages starting with multiple slashes to the homepage
    ['', 200, 'Firefox Trains', 'id="homepage"'],
    ['about/', 200, 'Other resources', 'id="about"'],
    ['beta/', 200, 'Current Desktop beta cycle:', 'id="beta"'],
    ['nightly/', 200, '', 'id="nightly"'],
    ['calendar/', 200, '', 'id="calendar_main"'],
    ['calendar/monthly/', 200, '', 'id="calendar_monthly"'],
    ['release/', 200, 'Release Owner', 'id="release"'],
    ['release/?version=beta', 200, '', '<meta http-equiv="refresh" content="7200">'], // Auto-refresh the Future Release page every 2 hours
    ['release/?version=94', 200, '<td title="Tuesday, September 7, 2021">September 7</td>', ''], // Test Nightly start date is correct
    ['', 200, 'Firefox Trains', '<meta http-equiv="refresh" content="21600">'], // Auto-refresh the Home page every 6 hours
    ['release/owners/', 200, 'Major releases per release owner', 'version=3.6'],
    ['calendar/release/schedule/?version=beta', 200, 'BEGIN:VCALENDAR', 'END:VCALENDAR'],
    ['sitemap/', 301, '', ''],
    ['sitemap.txt', 200, '', ''],
    ['rss/', 200, '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">', '</item>'],
];

$obj = new \pchevrel\Verif('Check public pages HTTP responses and content');
$obj
    ->setHost('localhost:8083')
    ->setPathPrefix('');

$check = function ($object, $paths) {
    foreach ($paths as $values) {
        [$path, $http_code, $content, $content2] = $values;
        echo "- $path\n";
        $object
            ->setPath($path)
            ->fetchContent()
            ->hasResponseCode($http_code)
            ->contains($content)
            ->contains($content2);
    }
};

echo "\nTesting page path:\n";
$check($obj, $paths);

$obj->report();

// Kill PHP dev server by killing all children processes of the bash process we opened in the background
killTestServer($processID);

// Report the status of the execution, needed for CI
die($obj->returnStatus());
