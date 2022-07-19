<?php

declare(strict_types=1);

use ReleaseInsights\Data;
use ReleaseInsights\Utils;
use ReleaseInsights\ESR;

$esr_releases = (new Data())->getESRReleases();

$upcoming_releases = (new Data())->getFutureReleases();

$esr_calendar = [];

foreach($upcoming_releases as $k => $v) {
    $esr_calendar [] = [
        'release' => $k,
        'esr'     => ESR::getVersion((int) $k),
        'old_esr' => ESR::getOlderSupportedVersion((int) $k),
        'date'    => $v,
    ];
}

return [
    $next_ESR     = str_replace('esr', '', ESR_NEXT),
    $current_ESR  = str_replace('esr', '', ESR),
    $release_date = $esr_releases[$next_ESR],
    $esr_calendar
];
