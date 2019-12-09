<?php

use Cache\Cache;
Use ReleaseInsights\Utils as Utils;


$cache_id = 'https://product-details.mozilla.org/1.0/firefox_versions.json';

// If we can't retrieve cached data, we create and cache it.
// We cache because we want to avoid http request latency
if (!$data = Cache::getKey($cache_id)) {
    $firefox_versions = file_get_contents($cache_id);

    // Extract into an array the values we want from the data source
    $firefox_versions = json_decode($firefox_versions, true);

    // No data returned, don't cache.
    if (empty($firefox_versions)) {
        return [];
    }
}

define('ESR', $firefox_versions["FIREFOX_ESR"]);
define('ESR_NEXT', $firefox_versions["FIREFOX_ESR_NEXT"]);
define('FIREFOX_NIGHTLY', $firefox_versions["FIREFOX_NIGHTLY"]);
define('DEV_EDITION', $firefox_versions["FIREFOX_DEVEDITION"]);
define('FIREFOX_BETA', $firefox_versions["LATEST_FIREFOX_RELEASED_DEVEL_VERSION"]);
define('FIREFOX_RELEASE', $firefox_versions["LATEST_FIREFOX_VERSION"]);

$main_nightly = (int) FIREFOX_NIGHTLY;
$main_beta    = (int) FIREFOX_BETA;
$main_release = (int) FIREFOX_RELEASE;
$main_esr     = (int) (ESR_NEXT != "" ? ESR_NEXT : ESR);
$last_beta    = (int) str_replace($main_beta .'.0b', '', FIREFOX_BETA);




$link = function($url, $text, $title = true) {
    $title = $title ? '&title=' . rawurlencode($text) : '';
    return '<a href="' . $url . $title . '" target="_blank" rel="noopener">' . $text . '</a>';
};
ob_start();

print'<h5>Patches uplifted for each beta</h5><ul>';

for ($i = 2; $i <= $last_beta + 1; $i++) {
    $beta_previous = ($i - 1) < 3 ? 'DEVEDITION_' : 'FIREFOX_';
    $beta_current_type = $i < 3 ? 'DEVEDITION_' : 'FIREFOX_';

    $beta_previous .= $main_beta . '_0b' . ($i-1) . '_RELEASE';
    $beta_current = ($i-1 == $last_beta)
        ? 'tip'
        : $main_beta . '_0b' . $i . '_RELEASE';

    if ($beta_current != 'tip') {
        $beta_current = $beta_current_type . $beta_current;
    }

    $hg_link =
        'https://hg.mozilla.org/releases/mozilla-beta/pushloghtml?fromchange='
        . $beta_previous
        . '&amp;tochange='
        . $beta_current;
    print '            <li>' . $link($hg_link,'Beta' . $i, $title = false ) . "</li>\n";
}
print'</ul>';
$patches_beta = ob_get_contents();
ob_end_clean();
