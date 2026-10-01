<?php

declare(strict_types=1);

use ReleaseInsights\{Data, ESR, Release, Version};

$future = [];
$releases = new Data()->getFutureReleases();

// Optional ?from=XX parameter to start the list at a specific version, including past ones
// Pre-Firefox 4 releases didn't follow a train model, we don't have reliable cycle data for them
if (isset($_GET['from']) && (int) $_GET['from'] > 0) {
    $from = max(4, (int) $_GET['from']);
    $releases = [];
    foreach (new Data()->getMajorReleases() as $version => $date) {
        if ((int) $version >= $from) {
            // Some major versions were replaced by a dot release (14.0.1, 125.0.1)
            $releases[Version::get($version)] = $date;
        }
    }
}

foreach ($releases as $version => $date) {
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
            'dot_release_1' => $version_data['dot_release_1'] ?? null,
            'dot_release_2' => $version_data['dot_release_2'] ?? null,
            'dot_release_3' => $version_data['dot_release_3'] ?? null,
            'esr'           => $ESR,
            'quarter'       => date('Y', strtotime($date)) . '-Q' . (string) ceil(date('n', strtotime($date)) / 3),
            'owner'         => $owner,
        ],
    ];
}

return $future;