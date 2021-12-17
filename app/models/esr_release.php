<?php

declare(strict_types=1);

use ReleaseInsights\Utils;
use ReleaseInsights\ESR;

$esr_releases = include MODELS . 'api/esr_releases.php';

$upcoming_releases = include DATA .'upcoming_releases.php';

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
    $latest_ESR = str_replace('esr', '', ESR),
    $release_date = $esr_releases[$latest_ESR],
    $esr_calendar
];
