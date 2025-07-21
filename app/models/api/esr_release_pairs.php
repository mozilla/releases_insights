<?php

declare(strict_types=1);

use ReleaseInsights\ESR;

$data = [];

/*
    Our very first ESR release was 10.0.0
*/
foreach(range(10, RELEASE) as $version) {
    $data[$version] = [
        ESR::getVersion($version),
        ESR::getOlderSupportedVersion($version),
        ESR::getWin7SupportedVersion($version),
    ];
    $data[$version] = array_filter($data[$version]); // Remove NULL values
    $data[$version] = array_unique($data[$version]); // Remove duplicate ESR versions
    sort($data[$version]); // Sort ESR numbersfrom oldest to newest branch
}

return $data;
