<?php

declare(strict_types=1);

use Cache\Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils as Promise;
use ReleaseInsights\{Bugzilla, Data, IOS, Json, Nightly, Release, URL, Version};

// Get requested version first — uses pre-loaded constants only, no HTTP
$requested_version = Version::get();

// Define parsed cache keys here so they are available for both the pre-fetch
// cache checks below and the actual getBugsFromHgWeb calls further down.
$beta_parsed_key    = 'parsed_beta_uplifts_'  . (int) $requested_version;
$nightly_parsed_key = 'parsed_nightly_fixes_' . (int) $requested_version;

// Build the Mercurial and external URLs up-front so we can identify cache misses
// and fetch them all in parallel before the sequential processing begins.
// URL::Mercurial->value is used here; in production target() == value so cache keys match.
$_beta_url = URL::Mercurial->value
    . 'releases/mozilla-beta/json-pushes'
    . '?fromchange=FIREFOX_BETA_' . (int) $requested_version . '_BASE'
    . '&tochange=FIREFOX_BETA_' . (int) $requested_version . '_END'
    . '&full&version=2';
if ((int) $requested_version === 119) {
    $_beta_url = str_replace('FIREFOX_BETA_119_END', 'f2a69b23cb0aaf2b36bac4f9f197bf4282f542c4', $_beta_url);
}

$_rc_url = URL::Mercurial->value
    . 'releases/mozilla-release/json-pushes'
    . '?fromchange=FIREFOX_RELEASE_' . (int) $requested_version . '_BASE'
    . '&tochange=FIREFOX_' . (int) $requested_version . '_0_RELEASE'
    . '&full&version=2';
if ((int) $requested_version === 125) {
    $_rc_url = str_replace('FIREFOX_125_0_RELEASE', 'FIREFOX_125_0_1_RELEASE', $_rc_url);
}

$_nightly_url = ((int) $requested_version === 126)
    ? URL::Mercurial->value . 'mozilla-central/json-pushes?fromchange=d14f32147b8133ced41921f303d0c9f22e2d4d8a&tochange=FIREFOX_NIGHTLY_' . (int) $requested_version . '_END&full&version=2'
    : URL::Mercurial->value . 'mozilla-central/json-pushes?fromchange=FIREFOX_NIGHTLY_' . ((int) $requested_version - 1) . '_END&tochange=FIREFOX_NIGHTLY_' . (int) $requested_version . '_END&full&version=2';

// Collect URLs not yet in any cache layer, then fetch them all in one parallel batch.
// Skipped in TESTING_CONTEXT where Mercurial URLs resolve to local file paths.
if (! defined('TESTING_CONTEXT')) {
    $_to_prefetch = [];

    // ProductDetails
    if (Cache::getKey(URL::ProductDetails->value . 'firefox.json') === false) {
        $_to_prefetch[URL::ProductDetails->value . 'firefox.json'] = CACHE_TIME;
    }
    if (Cache::getKey(URL::ProductDetails->value . 'devedition.json') === false) {
        $_to_prefetch[URL::ProductDetails->value . 'devedition.json'] = CACHE_TIME;
    }

    // Beta uplifts raw JSON — only needed when the parsed result is also absent
    if ($requested_version != 53 && $requested_version > 46
        && Cache::getKey($beta_parsed_key, 86400 * 365) === false
        && Cache::getKey($_beta_url, -1) === false) {
        $_to_prefetch[$_beta_url] = -1;
    }

    // RC uplifts
    if (Cache::getKey($_rc_url, -1) === false) {
        $_to_prefetch[$_rc_url] = -1;
    }

    // Nightly fixes raw JSON — only needed when the parsed result is also absent
    if (Cache::getKey($nightly_parsed_key, 86400 * 365) === false
        && Cache::getKey($_nightly_url, -1) === false) {
        $_to_prefetch[$_nightly_url] = -1;
    }

    // Balrog (current release only)
    if ((int) $requested_version === RELEASE
        && Cache::getKey(URL::Balrog->value . 'rules/firefox-release') === false) {
        $_to_prefetch[URL::Balrog->value . 'rules/firefox-release'] = CACHE_TIME;
    }

    if (! empty($_to_prefetch)) {
        $_client = new Client([
            'headers' => ['User-Agent' => 'WhatTrainIsItNow/1.0', 'Referer' => 'https://whattrainisitnow.com'],
        ]);
        $_promises = [];
        foreach ($_to_prefetch as $_url => $_) {
            $_promises[$_url] = $_client->getAsync($_url, ['http_errors' => false]);
        }
        foreach (Promise::settle($_promises)->wait() as $_url => $_result) {
            if ($_result['state'] === 'fulfilled' && $_result['value']->getStatusCode() === 200) {
                $_data = $_result['value']->getBody()->getContents();
                if (! empty($_data) && json_validate($_data)) {
                    Cache::setKey($_url, $_data, $_to_prefetch[$_url]);
                }
            }
        }
    }
    unset($_beta_url, $_rc_url, $_nightly_url, $_to_prefetch, $_client, $_promises, $_url, $_result, $_data);
}

// Historical data from Product Details (now served from cache if just pre-fetched)
$firefox_releases = Json::load(URL::ProductDetails->value . 'firefox.json')['releases'];
$devedition_releases = Json::load(URL::ProductDetails->value . 'devedition.json')['releases'];

if ($requested_version == '14.0') {
    // We never had a 14.0 release, so this is hardcoded
    $last_release_date = '2012-07-17';
} elseif ($requested_version == '125.0') {
    $last_release_date = '2024-04-16';
} else {
    $last_release_date = $firefox_releases['firefox-' . $requested_version]['date'] ?? '';
}
// Needed for beta cycle length calculation
if ($requested_version == 15) {
    // We never had a 14.0 release, so this is hardcoded
    $previous_release_date = '2012-07-17';
} elseif ($requested_version == 126) {
    $previous_release_date = $firefox_releases['firefox-125.0.1']['date'];
} else {
    $previous_release_date = $firefox_releases['firefox-' . number_format((int) $requested_version - 1.0, 1)]['date'];
}

// Needed for nightly cycle length calculation
$nightly_start_date = Nightly::cycleStart((int) $requested_version);

// Calculate the number of weeks between the 2 releases
$date1 = new DateTime($last_release_date);
$date2 = new DateTime($previous_release_date);
$date3 = new DateTime($nightly_start_date);
$beta_cycle_length = $date1->diff($date2)->days / 7;
$nightly_cycle_length = $date2->diff($date3)->days / 7;

/*
    Get Beta uplifts
*/

$beta_changelog = URL::Mercurial->target()
    . 'releases/mozilla-beta/json-pushes'
    . '?fromchange=FIREFOX_BETA_' . (int) $requested_version . '_BASE'
    . '&tochange=FIREFOX_BETA_' . (int) $requested_version . '_END'
    . '&full&version=2';

/*
    The 119 changelog was broken by an unwanted central to beta merge
    We don't want to include the bad changesets or we get all the 120
    bugs listed as uplifts. We need to stop before the error.
    See https://bugzilla.mozilla.org/1859380 for reference
*/
if ((int) $requested_version === 119) {
    $beta_changelog = str_replace(
        search: 'FIREFOX_BETA_119_END',
        replace: 'f2a69b23cb0aaf2b36bac4f9f197bf4282f542c4',
        subject: $beta_changelog
    );
}

if ($requested_version != 53 && $requested_version > 46) {
    $beta_uplifts = Cache::getKey($beta_parsed_key, 86400 * 365);
    if ($beta_uplifts === false) {
        $beta_uplifts = Bugzilla::getBugsFromHgWeb($beta_changelog, true, -1);
        Cache::setKey($beta_parsed_key, $beta_uplifts, 86400 * 365);
    }
    $beta_changelog    = str_replace('json-pushes', 'pushloghtml', $beta_changelog);
    $beta_uplifts_url  = Bugzilla::getBugListLink($beta_uplifts['total']);
    $beta_backouts_url = Bugzilla::getBugListLink($beta_uplifts['backouts']);
    if ($beta_uplifts['no_data']) {
        $beta_uplifts = false;
    }
} else {
    $beta_uplifts = $beta_changelog = $beta_uplifts_url = $beta_backouts_url = false;
}

// Get RC uplifts
$rc_changelog = URL::Mercurial->value
    . 'releases/mozilla-release/json-pushes'
    . '?fromchange=FIREFOX_RELEASE_' . ((int) $requested_version) . '_BASE'
    . '&tochange=FIREFOX_' . ((int) $requested_version) . '_0_RELEASE'
    . '&full&version=2';

// We didn't ship a 125.0 release, it was replaced by 125.0.1
if ((int) $requested_version == 125) {
    $rc_changelog = str_replace(
        search: 'FIREFOX_125_0_RELEASE',
        replace: 'FIREFOX_125_0_1_RELEASE',
        subject: $rc_changelog
    );
}

$rc_uplifts = Bugzilla::getBugsFromHgWeb($rc_changelog, true, -1);
$rc_changelog = str_replace('json-pushes', 'pushloghtml', $rc_changelog);

$rc_uplifts_url  = Bugzilla::getBugListLink($rc_uplifts['total']);
$rc_backouts_url = Bugzilla::getBugListLink($rc_uplifts['backouts']);

$dot_releases = array_filter(
    new Data()->getDotReleases(),
    fn ($key) => str_starts_with($key, $requested_version . '.'),
    ARRAY_FILTER_USE_KEY
);

// Number of dot releases
$dot_release_count = count($dot_releases);

// No dot release yet scenario
$dot_uplifts = [
    'bug_fixes' => [],
    'backouts'  => [],
    'total'     => [],
    'no_data'   => true,
];

$dot_changelog = $dot_uplifts_url = $dot_backouts_url = '';

// Get dot release uplifts
if ($dot_release_count > 0) {
    $dot_changelog = URL::Mercurial->value
        . 'releases/mozilla-release/json-pushes'
        . '?fromchange=FIREFOX_' . ((int) $requested_version) . '_0_RELEASE'
        . '&tochange=FIREFOX_' . ((int) $requested_version) . '_0_' . (string) $dot_release_count .  '_RELEASE'
        . '&full&version=2';
    $dot_uplifts      = Bugzilla::getBugsFromHgWeb($dot_changelog, true, 3600 * 24 * 6);
    $dot_uplifts_url  = Bugzilla::getBugListLink($dot_uplifts['total']);
    $dot_backouts_url = Bugzilla::getBugListLink($dot_uplifts['backouts']);
}

// Number of Beta builds
$beta_count = count((array) array_filter(
    $firefox_releases,
    fn ($key) => str_starts_with((string) $key, 'firefox-' . $requested_version . 'b'),
    ARRAY_FILTER_USE_KEY
));

// Number of RC builds, we skip Firefox 14.0 because we never shipped it
if ($requested_version == '14.0') {
    $rc_count = 0;
} elseif ($requested_version == '125.0') {
    $rc_count = 1;
} else {
    $rc_count = $firefox_releases['firefox-' . $requested_version]['build_number'] ?? 'N/A';
}



// Check uptake rate only for the current release and the previous one
$release_uptake = 0;
if ((int) $requested_version >= RELEASE - 1) {
    $release_uptake = Data::getDesktopAdoptionRate($requested_version);
    foreach ($dot_releases as $k => $v) {
        $dot_releases[$k]['adoption'] = Data::getDesktopAdoptionRate($k);
    }
}

// In early days, we occasionnally skipped beta 1 for quality reasons, let's assume b3 is a safe bet
// We skip Firefox 14.0 because we never shipped it
if ($requested_version == '14.0') {
    $beta_start_date = '2012-06-05';
} else {
    $beta_start_date = $firefox_releases['firefox-' . $requested_version . 'b1']['date']
        ?? $devedition_releases['devedition-' . $requested_version . 'b1']['date']
        ?? $firefox_releases['firefox-' . $requested_version . 'b2']['date']
        ?? $devedition_releases['devedition-' . $requested_version . 'b2']['date']
        ?? $firefox_releases['firefox-' . $requested_version . 'b3']['date']
        ?? $devedition_releases['devedition-' . $requested_version . 'b3']['date'];
}

// Number of bugs fixed in nightly
$nightly_fixes = Cache::getKey($nightly_parsed_key, 86400 * 365);
if ($nightly_fixes === false) {
    if ($requested_version == '126.0') {
        /*
            126 was the big merge to mercurial for Firefox Android.
            We start from the commit after this merge
        */
        $nightly_fixes = Bugzilla::getBugsFromHgWeb(
            URL::Mercurial->value
            . 'mozilla-central/json-pushes'
            . '?fromchange=d14f32147b8133ced41921f303d0c9f22e2d4d8a'
            . '&tochange=FIREFOX_NIGHTLY_' . (int) $requested_version . '_END'
            . '&full&version=2',
            true,
            -1
        );
    } else {
        $nightly_fixes = Bugzilla::getBugsFromHgWeb(
            URL::Mercurial->value
            . 'mozilla-central/json-pushes'
            . '?fromchange=FIREFOX_NIGHTLY_' . ((int) $requested_version - 1) . '_END'
            . '&tochange=FIREFOX_NIGHTLY_' . (int) $requested_version . '_END'
            . '&full&version=2',
            true,
            -1
        );
    }
    Cache::setKey($nightly_parsed_key, $nightly_fixes, 86400 * 365);
}

$no_planned_dot_releases = new Release($requested_version)->no_planned_dot_releases;
$planned_dot_release     = new Release($requested_version)->getSchedule()['planned_dot_release'] ?? null;
$planned_dot_release_2   = new Release($requested_version)->getSchedule()['planned_dot_release_2'] ?? null;

// Check current rollout for the release channel
if ((int) $requested_version === RELEASE) {
    $rollout = Json::load(URL::Balrog->value . 'rules/firefox-release')['backgroundRate'];
}

return [
    $last_release_date,
    $previous_release_date,
    $beta_cycle_length,
    $nightly_cycle_length,
    $nightly_fixes,
    $beta_changelog,
    $beta_uplifts,
    $rc_uplifts,
    $rc_changelog,
    $rc_uplifts_url,
    $rc_backouts_url,
    $beta_uplifts_url,
    $beta_backouts_url,
    $dot_uplifts,
    $dot_uplifts_url,
    $dot_backouts_url,
    $dot_changelog,
    $rc_count,
    $beta_count,
    $dot_release_count,
    $dot_releases,
    $nightly_start_date,
    $beta_start_date,
    $firefox_releases,
    $no_planned_dot_releases,
    $rollout ?? -1,
    $release_uptake,
    new Data()->chemspills,
    new IOS($requested_version)->getSchedule(),
    $planned_dot_release,
    $planned_dot_release_2,
];
