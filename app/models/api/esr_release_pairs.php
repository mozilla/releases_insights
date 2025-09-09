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
    $data[$version] = [
        ESR::getVersion($version),
        ESR::getOlderSupportedVersion($version),
        ESR::getWin7SupportedVersion($version),
    ];
    $data[$version] = array_filter($data[$version]); // Remove NULL values
    $data[$version] = array_unique($data[$version]); // Remove duplicate ESR versions
    sort($data[$version]); // Sort ESR numbers from oldest to newest branch
}

return $data;