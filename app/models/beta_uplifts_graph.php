<?php

declare(strict_types=1);

use Cache\Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils as Promise;
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
$to_fetch = [];

// Collect versions not yet in cache; build two endpoints per version (beta cycle + RC)
for ($version = $first_version; $version <= BETA; $version++) {
    $is_past = ($version < BETA);

    if ($is_past && ($cached = Cache::getKey('uplift_total_v' . $version, 86400 * 365)) !== false) {
        $graph_data[$version] = (int) $cached;
        continue;
    }

    $beta_obj = new Beta($version);

    $beta_end = $beta_obj->beta_cycle_ended
        ? 'FIREFOX_BETA_' . $version . '_END'
        : 'tip';

    $beta_url = 'releases/mozilla-beta/json-pushes?fromchange=FIREFOX_BETA_'
        . $version . '_BASE&tochange=' . $beta_end . '&full&version=2';

    [$have_rc, $number_rc_builds] = ($version === BETA)
        ? $beta_obj->RCStatus()
        : $beta_obj->historicalRCStatus();

    $rc_url = null;
    if ($have_rc) {
        $rc_url = 'releases/mozilla-release/json-pushes?fromchange=FIREFOX_RELEASE_'
            . $version . '_BASE&tochange=FIREFOX_' . $version . '_0_BUILD' . $number_rc_builds
            . '&full&version=2';
    }

    $to_fetch[$version] = ['beta' => $beta_url, 'rc' => $rc_url, 'is_past' => $is_past];
}

// Fetch all pending versions in one parallel Guzzle batch
if (! empty($to_fetch)) {
    $client = new Client(['base_uri' => URL::Mercurial->value]);
    $promises = [];

    foreach ($to_fetch as $version => ['beta' => $beta_url, 'rc' => $rc_url]) {
        $promises[$version . '_beta'] = $client->getAsync($beta_url, ['http_errors' => false]);
        if ($rc_url !== null) {
            $promises[$version . '_rc'] = $client->getAsync($rc_url, ['http_errors' => false]);
        }
    }

    $results = Promise::settle($promises)->wait();

    foreach ($to_fetch as $version => ['is_past' => $is_past]) {
        $total = 0;

        foreach (['beta', 'rc'] as $part) {
            $key = $version . '_' . $part;
            if (! isset($results[$key])) {
                continue;
            }
            $result = $results[$key];
            if ($result['state'] === 'fulfilled' && $result['value']->getStatusCode() === 200) {
                $parsed = Bugzilla::getBugsFromHgWeb(
                    query: $result['value']->getBody()->getContents(),
                    detect_backouts: true
                );
                $total += count($parsed['total'] ?? []);
            }
        }

        if ($is_past) {
            Cache::setKey('uplift_total_v' . $version, (string) $total, 86400 * 365);
        }

        $graph_data[$version] = $total;
    }
}

ksort($graph_data);

file_put_contents($lock_file, '');

if ($waiting_page) {
    Request::waitingPage('leave');
}

return [$graph_data];
