<?php

declare(strict_types=1);

use Cache\Cache;
use ReleaseInsights\{Beta, Bugzilla, Request, URL};

$waiting_page = false;
$lock_file = CACHE_PATH . 'beta_uplifts_graph.cache';
$lock_ttl = 3600;

if (! file_exists($lock_file) OR time() - filemtime($lock_file) > $lock_ttl) {
    $waiting_page = true;
    Request::waitingPage('load');
}

$graph_data = [];
$first_version = 127;

for ($version = $first_version; $version <= BETA; $version++) {
    $is_past = ($version < BETA);
    $cache_ttl = $is_past ? 86400 * 365 : $lock_ttl;

    // Fast path: per-version count already cached
    if (($cached = Cache::getKey('uplift_total_v' . $version, $cache_ttl)) !== false) {
        $graph_data[$version] = (int) $cached;
        continue;
    }

    // Beta URL: start from the nightly-to-beta merge, same as past_release.php
    $beta_end = 'FIREFOX_BETA_' . $version . '_END';

    if ($is_past) {
        // RC URL: use _0_RELEASE (same as past_release.php, shares immutable cache files)
        $rc_url = URL::Mercurial->value
            . 'releases/mozilla-release/json-pushes?fromchange=FIREFOX_RELEASE_' . $version . '_BASE'
            . '&tochange=FIREFOX_' . $version . '_0_RELEASE&full&version=2';
    } else {
        $beta_obj = new Beta($version);
        if (! $beta_obj->beta_cycle_ended) {
            $beta_end = 'tip';
        }
        // RC URL: use _0_BUILD{N} because _0_RELEASE is not yet tagged during RC week
        [$have_rc, $number_rc_builds] = $beta_obj->RCStatus();
        $rc_url = $have_rc
            ? URL::Mercurial->value
                . 'releases/mozilla-release/json-pushes?fromchange=FIREFOX_RELEASE_' . $version . '_BASE'
                . '&tochange=FIREFOX_' . $version . '_0_BUILD' . $number_rc_builds . '&full&version=2'
            : null;
    }

    $beta_url = URL::Mercurial->value
        . 'releases/mozilla-beta/json-pushes?fromchange=FIREFOX_BETA_' . $version . '_BASE'
        . '&tochange=' . $beta_end . '&full&version=2';

    // v119: an unwanted central-to-beta merge corrupts the range up to FIREFOX_BETA_119_END
    // @phpstan-ignore identical.alwaysFalse
    if ($version === 119) {
        $beta_url = str_replace('FIREFOX_BETA_119_END', 'f2a69b23cb0aaf2b36bac4f9f197bf4282f542c4', $beta_url);
    }

    // Use Json::load() so responses are served from cache (immutable for past, lock_ttl for current)
    // This shares the same immutable files used by past_release.php
    $json_ttl = $is_past ? -1 : $lock_ttl;

    $beta_result = Bugzilla::getBugsFromHgWeb($beta_url, true, $json_ttl);
    $total = count($beta_result['total'] ?? []);

    if ($rc_url !== null) {
        $rc_result = Bugzilla::getBugsFromHgWeb($rc_url, true, $json_ttl);
        $total += count($rc_result['total'] ?? []);
    }

    Cache::setKey('uplift_total_v' . $version, (string) $total, $cache_ttl);
    $graph_data[$version] = $total;
}

ksort($graph_data);

file_put_contents($lock_file, '');

if ($waiting_page) {
    Request::waitingPage('leave');
}

return [$graph_data];
