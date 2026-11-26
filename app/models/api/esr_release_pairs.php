<?php

declare(strict_types=1);

use ReleaseInsights\{Data, ESR, Request};

// Endpoint: api/firefox/releases/esr/
$start = 10; // Our very first ESR release was 10.0.0
$end = RELEASE;

// Endpoint: api/firefox/releases/esr/future/
if (new Request(filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL))->path == '/api/firefox/releases/esr/future/') {
    $future_releases = array_map('intval', array_keys(new Data()->getFutureReleases()));
    $start = $future_releases[0];
    $end = end($future_releases);
}

$data = [];
foreach(range($start, $end) as $version) {
    $data[$version] = ESR::getVersionsForRelease($version);
}

return $data;