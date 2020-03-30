<?php
use Cache\Cache;
use ReleaseInsights\Utils as Utils;
use ReleaseInsights\Bugzilla as Bz;


// Analyse version requested
if (!isset($_GET['version'])) {
    $_GET['version'] = FIREFOX_RELEASE;
}

$requested_version = abs((int) $_GET['version']);
$requested_version = number_format($requested_version, 1);

// Historical data from Product Details
$firefox_releases = Utils::getJson('https://product-details.mozilla.org/1.0/firefox.json')['releases'];
$last_release_date = $firefox_releases['firefox-' . $requested_version]['date'];
$previous_release_date = $firefox_releases['firefox-' . number_format(($requested_version - 1.0), 1)]['date'];

// Calculate the number of weeks between the 2 releases
$date1 = new DateTime($last_release_date);
$date2 = new DateTime($previous_release_date);
$cycle_length = $date1->diff($date2)->days / 7;

// Get Beta uplifts

// Before 4 week schedule, uplifts started with beta 3
$uplift_start = (int) $requested_version > 72 ? '_0b1_RELEASE' :'_0b3_RELEASE';

$beta_uplifts = Bz::getBugsFromHgWeb(
    'https://hg.mozilla.org/releases/mozilla-beta/json-pushes'
    . '?fromchange=FIREFOX_' . (int) $requested_version . $uplift_start
    . '&tochange=FIREFOX_BETA_' . (int) $requested_version .'_END'
    . '&full&version=2'
    , true
);

$beta_uplifts_url  = Bz::getBugListLink($beta_uplifts['total']);
$beta_backouts_url = Bz::getBugListLink($beta_uplifts['backouts']);

$typo_fix_74 = (int) $requested_version == '74' ? '.' : '';

// Get RC uplifts
$rc_uplifts = Bz::getBugsFromHgWeb(
    'https://hg.mozilla.org/releases/mozilla-release/json-pushes'
    . '?fromchange=FIREFOX_RELEASE_' . ((int) $requested_version) . '_BASE'
    . '&tochange=FIREFOX_RELEASE_' . ((int) $requested_version) . '_END'. $typo_fix_74
    . '&full&version=2'
    , true
);

$rc_uplifts_url  = Bz::getBugListLink($rc_uplifts['total']);
$rc_backouts_url = Bz::getBugListLink($rc_uplifts['backouts']);

// Number of Beta builds
// $beta_count= count($firefox_releases['firefox-' . FIREFOX_RELEASE] . 'b');

$beta_count = count(array_filter(
    $firefox_releases,
    function($k) use ($requested_version) {
        return Utils::startsWith($k, 'firefox-' . $requested_version . 'b');
    },
    ARRAY_FILTER_USE_KEY
));

// Number of RC builds
$rc_count = $firefox_releases['firefox-' . $requested_version]['build_number'];
