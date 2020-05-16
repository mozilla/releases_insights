<?php
use Cache\Cache;
use ReleaseInsights\Bugzilla as Bz;
use ReleaseInsights\Utils;

// Historical data from Product Details
$firefox_releases = Utils::getJson('https://product-details.mozilla.org/1.0/firefox.json')['releases'];
$last_release_date = $firefox_releases['firefox-' . $requested_version]['date'];

// Needed for beta cycle length calculation
$previous_release_date = $firefox_releases['firefox-' . number_format($requested_version - 1.0, 1)]['date'];

// Needed for nightly cycle length calculation
$nightly_start_date = $firefox_releases['firefox-' . number_format($requested_version - 2.0, 1)]['date'];

// Calculate the number of weeks between the 2 releases
$date1 = new DateTime($last_release_date);
$date2 = new DateTime($previous_release_date);
$date3 = new DateTime($nightly_start_date);
$beta_cycle_length = $date1->diff($date2)->days / 7;
$nightly_cycle_length = $date2->diff($date3)->days / 7;

// Get Beta uplifts

// Before 4 week schedule, uplifts started with beta 3
$uplift_start = (int) $requested_version > 72 ? '_0b1_RELEASE' : '_0b3_RELEASE';

$beta_changelog = 'https://hg.mozilla.org/releases/mozilla-beta/json-pushes'
    . '?fromchange=FIREFOX_' . (int) $requested_version . $uplift_start
    . '&tochange=FIREFOX_BETA_' . (int) $requested_version .'_END'
    . '&full&version=2';

if ($requested_version != 53 && $requested_version > 46 ) {
    $beta_uplifts = Bz::getBugsFromHgWeb($beta_changelog, true, 3600 * 24 * 365);
    $beta_changelog = str_replace('json-pushes', 'pushloghtml', $beta_changelog);
    $beta_uplifts_url  = Bz::getBugListLink($beta_uplifts['total']);
    $beta_backouts_url = Bz::getBugListLink($beta_uplifts['backouts']);
} else {
        $beta_uplifts = $beta_changelog = $beta_uplifts_url = $beta_backouts_url = false;
}

// Get RC uplifts
$rc_changelog = 'https://hg.mozilla.org/releases/mozilla-release/json-pushes'
    . '?fromchange=FIREFOX_RELEASE_' . ((int) $requested_version) . '_BASE'
    . '&tochange=FIREFOX_' . ((int) $requested_version) . '_0_RELEASE'
    . '&full&version=2';

$rc_uplifts = Bz::getBugsFromHgWeb($rc_changelog, true, 3600 * 24 * 365);
$rc_changelog = str_replace('json-pushes', 'pushloghtml', $rc_changelog);

$rc_uplifts_url  = Bz::getBugListLink($rc_uplifts['total']);
$rc_backouts_url = Bz::getBugListLink($rc_uplifts['backouts']);

// Number of Beta builds
// $beta_count= count($firefox_releases['firefox-' . FIREFOX_RELEASE] . 'b');
$beta_count = count(array_filter(
    $firefox_releases,
    function($key) use ($requested_version) {
        return Utils::startsWith($key, 'firefox-' . $requested_version . 'b');
    },
    ARRAY_FILTER_USE_KEY
));

// Number of RC builds
$rc_count = $firefox_releases['firefox-' . $requested_version]['build_number'];

// Number of dot releases
$dot_release_count = count(array_filter(
    $firefox_releases,
    function($key) use ($requested_version) {
        return Utils::startsWith($key, 'firefox-' . $requested_version . '.');
    },
    ARRAY_FILTER_USE_KEY
));

// Number of bugs fixed in nightly
$nightly_fixes = Bz::getBugsFromHgWeb(
    'https://hg.mozilla.org/mozilla-central/json-pushes'
    . '?fromchange=FIREFOX_NIGHTLY_' . ((int) $requested_version - 1) . '_END'
    . '&tochange=FIREFOX_NIGHTLY_' . (int) $requested_version .'_END'
    . '&full&version=2'
    , true
    , 3600 * 24 * 365
);
