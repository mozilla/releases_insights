<?php

declare(strict_types=1);

use Cache\Cache;
use ReleaseInsights\{URL, Utils};

$buildid = Utils::getBuildID((int) ($_GET['buildid'] ?? 1));

$cache_id = URL::Socorro->value . 'SuperSearch/?build_id=' . (string) $buildid . '&_facets=signature&product=Firefox';

// If we can't retrieve cached data, we create and cache it.
// We cache because we want to avoid http request latency
if (! $data = Cache::getKey($cache_id, 1)) {
    $data = Utils::arrayFromJson(file_get_contents($cache_id));

    // No data returned, don't cache.
    if (empty($data)) {
        return [];
    }

    $total_crashes = $data['total'];
    $signatures = $data['facets']['signature'];

    $data = [
        'buildid'    => (string) $buildid,
        'total'      => $total_crashes,
        'signatures' => $signatures,
    ];

    // We don't cache today because we may miss the second nightly build
    if (date('Ymd', $buildid) !== date('Ymd')) {
        Cache::setKey($cache_id, $data);
    }
}

return $data;
