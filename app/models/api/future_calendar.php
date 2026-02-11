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

    // Do we still have an esr 115 release for this version?
    if (ESR::getWin7SupportedVersion((int)$version) !== null ) {
        $ESR .= ' + ' . ESR::getWin7SupportedVersion((int) $version);
    }

    $future += [
        $version => [
            'version'       => new Version($version)->int,
            'nightly_start' => $version_data['nightly_start'],
            'beta_start'    => $version_data['merge_day'],
            'release_date'  => $date,
            'dot_release'   => $version_data['planned_dot_release'],
            'esr'           => $ESR,
            'quarter'       => date('Y', strtotime($date)) . '-Q' . (string) ceil(date('n', strtotime($date)) / 3),
            'owner'         => $owner,
        ],
    ];
}

return $future;