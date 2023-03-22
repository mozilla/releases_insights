<?php

declare(strict_types=1);

use ReleaseInsights\Data;
use ReleaseInsights\ESR;
use ReleaseInsights\Release;
use ReleaseInsights\Version;
use ReleaseInsights\Utils;

$future = [];

foreach ((new Data)->getFutureReleases() as $version => $date) {
    $version_data = (new Release($version))->getSchedule();

    $owner = (new Data)->getOwners()[$version] ?? 'TBD';
    // Display the first name only, we don't need family names for active release managers
    $owner = explode(' ', $owner)[0];

    $ESR = ESR::getOlderSupportedVersion((int) $version) == null
        ? ESR::getMainDotVersion(ESR::getVersion((int) $version))
        : ESR::getMainDotVersion(ESR::getOlderSupportedVersion((int) $version))
             . ' + '
             . ESR::getMainDotVersion(ESR::getVersion((int) $version));

    $future += [
        $version => [
            'version'       => Version::getMajor($version),
            'release_date'  => $date,
            'nightly_start' => $version_data['nightly_start'],
            'soft_freeze'   => $version_data['soft_code_freeze'],
            'beta_start'    => $version_data['merge_day'],
            'esr'           => $ESR,
            'quarter'       => 'Q' . (string) ceil(date('n', strtotime($date)) / 3),
            'owner'         => $owner,
        ]
    ];
}

$past = [];

foreach ((new Data)->getPastReleases(dot_releases: false) as $version => $date) {
    $version_data = (new Release($version))->getSchedule();

    $ESR = ESR::getOlderSupportedVersion((int) $version) == null
        ? ESR::getMainDotVersion(ESR::getVersion((int) $version))
        : ESR::getMainDotVersion(ESR::getOlderSupportedVersion((int) $version))
             . ' + '
             . ESR::getMainDotVersion(ESR::getVersion((int) $version));

    $past += [
        $version => [
            'version'       => $version == '14.0.1' ? '14.0' : $version,
            'release_date'  => $date,
            'nightly_start' => array_key_exists('error', $version_data) ? 'a' : $version_data['nightly_start'],
            'soft_freeze'   => array_key_exists('error', $version_data) ? '' : $version_data['soft_code_freeze'],
            'beta_start'    => array_key_exists('error', $version_data) ? '' : $version_data['merge_day'],
            'esr'           => $ESR,
            'owner'         => (new Data)->getOwners()[$version] ?? 'TBD',
        ]
    ];

}

arsort($past);

return ['future' => $future, 'past' => $past];
