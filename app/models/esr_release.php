<?php

declare(strict_types=1);

use ReleaseInsights\{Data, ESR, Version};

$esr_releases = new Data()->getESRReleases();

$upcoming_releases = new Data()->getFutureReleases();

$esr_calendar = [];

foreach ($upcoming_releases as $k => $v) {
    $esr_calendar[] = [
        'release' => new Version($k)->int,
        'esr'     => ESR::getMainDotVersion(ESR::getVersion((int) $k)),
        'old_esr' => is_null(ESR::getOlderSupportedVersion((int) $k))
            ? ''
            : ESR::getMainDotVersion(ESR::getOlderSupportedVersion((int) $k)),
        'esr_115' => ESR::getMainDotVersion(ESR::getWin7SupportedVersion((int) $k)),
        'date'    => $v,
    ];
}

return [
    /* @phpstan-ignore-next-line */
    $next_ESR     = ESR_NEXT !== '' ? str_replace('esr', '', (string) ESR_NEXT) : null,
    $current_ESR  = str_replace('esr', '', (string) ESR),
    /* @phpstan-ignore-next-line */
    $release_date = ESR_NEXT !== '' ? $esr_releases[$next_ESR] : $esr_releases[$current_ESR],
    $esr_calendar,
];
