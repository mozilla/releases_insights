<?php

declare(strict_types=1);

use ReleaseInsights\{Data, UpliftCounts};

/*
    Bugs uplifted to each ESR dot release, per Firefox release cycle and ESR branch.

    The ESR dot releases of cycle N ship with Firefox N. The cycle in progress is
    the next release, its ESR branches are read up to tip. A new ESR branch
    starts at x.0.0 which has no uplifts, it isn't counted.
 */
$in_progress_version = RELEASE + 1;
$esr_releases = UpliftCounts::getESRReleases(
    $in_progress_version,
    array_keys(new Data()->getESRReleases())
);

$logs = [];
foreach ($esr_releases as $fx_version => $esr_versions) {
    $in_progress = $fx_version === $in_progress_version;
    foreach ($esr_versions as $esr_version) {
        $url = UpliftCounts::getESRLogUrl($esr_version, $in_progress);
        if ($url !== null) {
            $logs['esr_' . $esr_version] = ['urls' => [$url], 'in_progress' => $in_progress];
        }
    }
}

$counts = UpliftCounts::countAll($logs);

$esr_uplifts = [];
$log_links = [];
foreach ($esr_releases as $fx_version => $esr_versions) {
    foreach ($esr_versions as $branch => $esr_version) {
        $id = 'esr_' . $esr_version;
        if (! isset($logs[$id])) {
            continue;
        }
        $esr_uplifts[$fx_version][$branch] = $counts[$id];
        $log_links[$fx_version][$branch] = str_replace('json-pushes', 'pushloghtml', $logs[$id]['urls'][0]);
    }
}

return [$esr_releases, $esr_uplifts, $log_links, $in_progress_version];
