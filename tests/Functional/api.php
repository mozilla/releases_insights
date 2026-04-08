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
    ['firefox/releases/esr/future/', 200, 'Verif:skip'],
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
    ['I_am_a_404/', 404, '{
    "error": "Not Found"
}'],
];

$obj = new \pchevrel\Verif('Check API HTTP responses');
$obj->setHost('localhost:8083')->setPathPrefix('api/');

$multi = curl_multi_init();
$handle_to_index = [];
foreach ($paths as $i => [$path]) {
    $ch = curl_init('http://localhost:8083/api/' . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true]);
    $handle_to_index[(int) $ch] = $i;
    curl_multi_add_handle($multi, $ch);
}

echo "\nTesting API path:\n";
do {
    curl_multi_exec($multi, $still_running);
    while ($info = curl_multi_info_read($multi)) {
        if ($info['msg'] !== CURLMSG_DONE) {
            continue;
        }
        $ch = $info['handle'];
        $i  = $handle_to_index[(int) $ch];
        [$path, $http_code, $content] = $paths[$i];

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $body = curl_multi_getcontent($ch);

        echo "- $path\n";
        $obj->setPath($path);
        if ($code !== $http_code) {
            $obj->setError("HTTP code error for {$path}: expected {$http_code}, got {$code}");
        }
        $obj->test_count++;
        $obj->content = $body;
        $obj->isJson()->isEqualTo($content);

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
