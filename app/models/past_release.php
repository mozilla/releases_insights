<?php

declare(strict_types=1);

use ReleaseInsights\{Bugzilla as Bz, Release, Utils, Version};

// Historical data from Product Details
$firefox_releases = Utils::getJson('https://product-details.mozilla.org/1.0/firefox.json')['releases'];
$devedition_releases = Utils::getJson('https://product-details.mozilla.org/1.0/devedition.json')['releases'];
$requested_version = Version::get();

if ($requested_version == 14) {
    // We never had a 14.0 release, so this is hardcoded
    $last_release_date = '2012-07-17';
} else {
    $last_release_date = $firefox_releases['firefox-' . $requested_version]['date'] ?? '';
}
// Needed for beta cycle length calculation
if ($requested_version == 15) {
    // We never had a 14.0 release, so this is hardcoded
    $previous_release_date = '2012-07-17';
} else {
    $previous_release_date = $firefox_releases['firefox-' . number_format((int) $requested_version - 1.0, 1)]['date'];
}
// Needed for nightly cycle length calculation
if ($requested_version == 16) {
    // We never had a 14.0 release, so this is hardcoded
    $nightly_start_date = '2012-06-04';
} else {
    $nightly_start_date = $firefox_releases['firefox-' . number_format((int) $requested_version - 2.0, 1)]['date'];
}

// Calculate the number of weeks between the 2 releases
$date1 = new DateTime($last_release_date);
$date2 = new DateTime($previous_release_date);
$date3 = new DateTime($nightly_start_date);
$beta_cycle_length = $date1->diff($date2)->days / 7;
$nightly_cycle_length = $date2->diff($date3)->days / 7;

/*
    Get Beta uplifts
*/

// Before 4 week schedule, uplifts started with beta 3
$uplift_start = (int) $requested_version > 72 ? '_0b1_RELEASE' : '_0b3_RELEASE';

$beta_changelog = 'https://hg.mozilla.org/releases/mozilla-beta/json-pushes'
    . '?fromchange=FIREFOX_' . (int) $requested_version . $uplift_start
    . '&tochange=FIREFOX_BETA_' . (int) $requested_version .'_END'
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

if ($requested_version !== 53 && $requested_version > 46) {
    $beta_uplifts      = Bz::getBugsFromHgWeb($beta_changelog, true, -1);
    $beta_changelog    = str_replace('json-pushes', 'pushloghtml', $beta_changelog);
    $beta_uplifts_url  = Bz::getBugListLink($beta_uplifts['total']);
    $beta_backouts_url = Bz::getBugListLink($beta_uplifts['backouts']);
    if ($beta_uplifts['no_data']) {
        $beta_uplifts = false;
    }
} else {
    $beta_uplifts = $beta_changelog = $beta_uplifts_url = $beta_backouts_url = false;
}

// Get RC uplifts
$rc_changelog = 'https://hg.mozilla.org/releases/mozilla-release/json-pushes'
    . '?fromchange=FIREFOX_RELEASE_' . ((int) $requested_version) . '_BASE'
    . '&tochange=FIREFOX_' . ((int) $requested_version) . '_0_RELEASE'
    . '&full&version=2';

$rc_uplifts = Bz::getBugsFromHgWeb($rc_changelog, true, -1);
$rc_changelog = str_replace('json-pushes', 'pushloghtml', $rc_changelog);

$rc_uplifts_url  = Bz::getBugListLink($rc_uplifts['total']);
$rc_backouts_url = Bz::getBugListLink($rc_uplifts['backouts']);

// Number of Beta builds
$beta_count = count((array) array_filter(
    $firefox_releases,
    fn ($key) => str_starts_with((string) $key, 'firefox-' . $requested_version . 'b'),
    ARRAY_FILTER_USE_KEY
));

// Number of RC builds, we skip Firefox 14.0 because we never shipped it
if ($requested_version == '14.0') {
    $rc_count = 0;
} else {
    $rc_count = $firefox_releases['firefox-' . $requested_version]['build_number'];
}
// Number of dot releases
$dot_release_count = count((array) array_filter(
    $firefox_releases,
    fn ($key) => str_starts_with((string) $key, 'firefox-' . $requested_version . '.') && ! str_ends_with((string) $key, 'esr'),
    ARRAY_FILTER_USE_KEY
));

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
$nightly_fixes = Bz::getBugsFromHgWeb(
    'https://hg.mozilla.org/mozilla-central/json-pushes'
    . '?fromchange=FIREFOX_NIGHTLY_' . ((int) $requested_version - 1) . '_END'
    . '&tochange=FIREFOX_NIGHTLY_' . (int) $requested_version .'_END'
    . '&full&version=2',
    true,
    -1
);

$no_planned_dot_releases = (new Release($requested_version))->no_planned_dot_releases;

// Check current rollout for the release channel
if ((int) $requested_version === RELEASE) {
    $rollout = Utils::getJson('https://aus-api.mozilla.org/api/v1/rules/firefox-release')['backgroundRate'];
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
    $rc_count,
    $beta_count,
    $dot_release_count,
    $nightly_start_date,
    $beta_start_date,
    $firefox_releases,
    $no_planned_dot_releases,
    $rollout ?? -1,
];
