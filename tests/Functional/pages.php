<?php

include 'init.php';

$paths = [
    ['404/', 404, 'The page cannot be found', 'id="notfound"'],
    ['somerandomfile.png', 404, '', ''], // missing files, not pages, don't get a 404 page, just a 404 http response code
    ['yolo', 302, '', ''], // Test that we redirect yolo to yolo/
    ['//', 302, '', ''], // Test that we redirect pages starting with multiple slases to the homepage
    ['//yolo', 302, '', ''], // Test that we redirect pages starting with multiple slashes to the homepage
    ['', 200, 'Firefox Trains', 'id="homepage"'],
    ['', 200, 'Firefox Trains', '<meta http-equiv="refresh" content="3600">'], // Auto-refresh the Home page every hour
    ['about/', 200, 'Other resources', 'id="about"'],
    ['api/', 302, '', 'id="about"'],
    ['beta/', 200, '', ''],
    ['nightly/', 200, '', 'id="nightly"'],
    ['calendar/', 200, '', 'id="calendar_main"'],
    ['calendar/monthly/', 200, '', 'id="calendar_monthly"'],
    ['release/', 200, 'Release Owner', 'id="release_'], // could be release_beta or release_current after beta 1
    ['release/?version=release', 200, 'id="release_current"', '<meta http-equiv="refresh" content="3600">'], // Auto-refresh release page every hour
    ['release/?version=beta', 200, '', '<meta http-equiv="refresh" content="7200">'], // Auto-refresh the Future Release page every 2 hours
    ['release/?version=94', 200, '<td title="Tuesday, September 7, 2021">September 7</td>', ''], // Test Nightly start date is correct
    ['release/?version=131', 200, '<b>Chemspill</b><br>Urgent security release', ''], // Test Nightly start date is correct
    ['release/owners/', 200, 'Major releases per Release Owner', 'version=3.6', '3 · 3.5 · 3.6 · 4'],
    ['calendar/future/releases/', 200, 'BEGIN:VCALENDAR', 'END:VCALENDAR', 'PRODID:Firefox_major_releases_schedule.ics'],
    ['calendar/release/schedule/?version=beta', 200, 'BEGIN:VCALENDAR', 'END:VCALENDAR'],
    ['calendar/release/schedule/?version=1', 400, 'provide predictive schedules for <i>past</i> releases', ''],
    ['calendar/release/schedule/?version=5000', 400, 'Release is not scheduled yet', ''],
    ['sitemap/', 301, '', ''],
    ['sitemap.txt', 200, '', ''],
    ['release-notes/', 302, '', ''],
    ['rss/', 200, '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">', '</item>'],
    ['api/firefox/calendar/future/?format=text', 200, 'Version,Nightly Start,Beta,Release Date,Release Owner', ''],
    ['api/firefox/calendar/future/?format=csv', 200, 'Version,Nightly Start,Beta,Release Date,Release Owner', ''],
];

// Pre-fetch all responses in parallel using curl_multi.
// Redirects are followed so content checks work on the final page (matching the original
// file_get_contents() behavior), but we capture the *first* status code from the raw
// headers to match the original get_headers() behavior.
$obj = new \pchevrel\Verif('Check public pages HTTP responses and content');
$obj->setHost('localhost:8083')->setPathPrefix('');

$multi = curl_multi_init();
$handle_to_index = [];
foreach ($paths as $i => [$path]) {
    $ch = curl_init('http://localhost:8083/' . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER         => true,
    ]);
    $handle_to_index[(int) $ch] = $i;
    curl_multi_add_handle($multi, $ch);
}

echo "\nTesting page path:\n";
do {
    curl_multi_exec($multi, $still_running);
    while ($info = curl_multi_info_read($multi)) {
        if ($info['msg'] !== CURLMSG_DONE) {
            continue;
        }
        $ch = $info['handle'];
        $i  = $handle_to_index[(int) $ch];
        [$path, $http_code, $content, $content2] = $paths[$i];

        $raw = curl_multi_getcontent($ch);
        preg_match('/HTTP\/\d[\d.]* (\d+)/', $raw, $matches);
        $first_code = isset($matches[1]) ? (int) $matches[1] : 0;
        $body = substr($raw, curl_getinfo($ch, CURLINFO_HEADER_SIZE));

        echo "\e[1;32m✓\e[0m " . ($path ?: '(empty string)') . "\n";
        $obj->setPath($path);
        if ($first_code !== $http_code) {
            $obj->setError("HTTP code error for {$path}: expected {$http_code}, got {$first_code}");
        }
        $obj->test_count++;
        $obj->content = $body;
        $obj->contains($content)->contains($content2);

        curl_multi_remove_handle($multi, $ch);
        curl_close($ch);
    }
    if ($still_running) {
        curl_multi_select($multi);
    }
} while ($still_running);
curl_multi_close($multi);

$obj->report();

// Kill PHP dev server by killing all children processes of the bash process we opened in the background
killTestServer($processID);

// Report the status of the execution, needed for CI
die($obj->returnStatus());
