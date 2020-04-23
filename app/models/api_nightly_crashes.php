<?php
use Cache\Cache;
Use ReleaseInsights\Utils as Utils;

$buildid = Utils::getBuildID($_GET['buildid'] ?? '');

$cache_id = 'https://crash-stats.mozilla.com/api/SuperSearch/?build_id=' . $buildid . '&_facets=signature&product=Firefox';

// If we can't retrieve cached data, we create and cache it.
// We cache because we want to avoid http request latency
if (!$data = Cache::getKey($cache_id, 1)) {
    $data = file_get_contents($cache_id);

    // Extract into an array the values we want from the data source
    $data = json_decode($data, true);

    // No data returned, don't cache.
    if (empty($data)) {
        return [];
    }

    $total_crashes = $data['total'];
    $signatures = $data['facets']['signature'];

    $data = [
        'buildid'    => $buildid,
        'total'      => $total_crashes,
        'signatures' => $signatures,
    ];

    // We don't cache today because we may miss the second nightly build
    if (date('Ymd', $buildid) != date('Ymd')) {
        Cache::setKey($cache_id, $data);
    }
}

return $data;
