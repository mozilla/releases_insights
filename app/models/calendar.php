<?php

declare(strict_types=1);

use ReleaseInsights\{Data, ESR, Release, Version};

$past = [];

$obj = new Data();

foreach ($obj->getDesktopPastReleases(dot_releases: false) as $version => $date) {
    $esr = ESR::getOlderSupportedVersion((int) $version) == null
        ? ESR::getMainDotVersion(ESR::getVersion((int) $version))
        : ESR::getMainDotVersion(ESR::getOlderSupportedVersion((int) $version))
             . ' + '
             . ESR::getMainDotVersion(ESR::getVersion((int) $version));

    $betas = $obj->getPastBetas();

    if ($version == '17.0') {
        $nightly_start = $obj->getDesktopPastReleases()['14.0.1'];
    } elseif ($version == '8.0') {
        $nightly_start = '2011-07-05';
    } elseif ($version == '7.0') {
        $nightly_start = '2011-05-24';
    } elseif ($version == '6.0') {
        $nightly_start = '2011-04-12';
    } elseif ((int) $version < 55 && (int) $version > 8) {
        $nightly_start = $obj->getDesktopPastReleases()[Version::decrement($version, 3)];
    } elseif ($version =='127.0') {
        $nightly_start = $obj->getDesktopPastReleases()['125.0.1']; // 125.0 replaced by 125.0.1
    } else {
        $nightly_start = $obj->getDesktopPastReleases()[Version::decrement($version, 2)];
    }
    // We never shipped 14.0
    $version = $version == '14.0.1' ? '14.0' : $version;

    // We never shipped 125.0
    $version = $version == '125.0.1' ? '125.0' : $version;

    // We never shipped 33.0 and we used a weird 33.1 dot release sheme instead of 33.0.1
    $version = $version == '33.1' ? '33.0' : $version;

    // We didn't always have a regular beta schedule
    $beta_date = $betas[$version . 'b1']
        ?? $betas[$version . 'b3'] // We used to start betas with b3 when we had aurora
        ?? $betas[$version . 'b4'] // We never shipped 58.0b3
        ?? $betas[$version . 'b6'] // We never shipped previous 14.0bx
        ?? $betas[$version . 'rc1'] // We had no public betas for 1.0 & 1.5 but had RCs
        ?? '9999-12-12'; // Fake date in the future as a fallback to avoid a plausible date.

    $past += [
        $version => [
            'version'       => $version,
            'release_date'  => $date,
            'nightly_start' => $nightly_start,
            'beta_start'    => $beta_date,
            'esr'           => $esr,
            'owner'         => new Data()->release_owners[$version] ?? 'TBD',
        ],
    ];
}

arsort($past);

// Get our upcoing calendar and major milestones
$future = include MODELS . 'api/future_calendar.php';

return ['future' => $future, 'past' => $past];
