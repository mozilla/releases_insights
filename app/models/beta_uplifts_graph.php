<?php

declare(strict_types=1);

use ReleaseInsights\UpliftCounts;

/*
    Bugs uplifted to beta and to the release candidates of each Firefox version,
    from Firefox 75 (the first 4-week cycle) to the beta in progress.
    Raw logs of shipped releases are committed .immutable files shared with
    the past release pages, see UpliftCounts.
 */
$first_version = 75;

$logs = [];
foreach (range($first_version, BETA) as $version) {
    $logs['beta_' . $version] = [
        'urls'        => UpliftCounts::getBetaLogUrls($version),
        'in_progress' => $version >= BETA,
    ];
}

$counts = UpliftCounts::countAll($logs);

$beta_uplifts = [];
foreach (range($first_version, BETA) as $version) {
    $beta_uplifts[$version] = $counts['beta_' . $version];
}

return [$beta_uplifts];
