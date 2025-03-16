<?php

declare(strict_types=1);

use ReleaseInsights\{Data, ESR, Release, Version};

$future = [];

foreach (new Data()->getFutureReleases() as $version => $date) {
    $version_data = new Release($version)->getSchedule();

    $owner = new Data()->release_owners[$version] ?? 'TBD';
    // Display the first name only, we don't need family names for active release managers
    $owner = explode(' ', $owner)[0];

    $ESR = ESR::getOlderSupportedVersion((int) $version) == null
        ? ESR::getMainDotVersion(ESR::getVersion((int) $version))
        : ESR::getMainDotVersion(ESR::getOlderSupportedVersion((int) $version))
             . ' + '
             . ESR::getMainDotVersion(ESR::getVersion((int) $version));

    $future += [
        $version => [
            'version'       => new Version($version)->int,
            'release_date'  => $date,
            'nightly_start' => $version_data['nightly_start'],
            'soft_freeze'   => $version_data['soft_code_freeze'],
            'beta_start'    => $version_data['merge_day'],
            'esr'           => $ESR,
            'quarter'       => date('Y', strtotime($date)) . '-Q' . (string) ceil(date('n', strtotime($date)) / 3),
            'owner'         => $owner,
        ],
    ];
}

return $future;