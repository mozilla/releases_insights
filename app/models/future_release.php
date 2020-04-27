<?php
use Cache\Cache;
use ReleaseInsights\Utils as Utils;
use ReleaseInsights\Bugzilla as Bz;

// Future release date
$release_date = $upcoming_releases[(int) $requested_version];

// Historical data from Product Details
$shipped_releases = Utils::getJson('https://product-details.mozilla.org/1.0/firefox.json')['releases'];

// Previous release
$pd_key = 'firefox-' . number_format(($requested_version - 1.0), 1);
if (array_key_exists($pd_key, $shipped_releases)) {
    $previous_release_date = $shipped_releases[$pd_key]['date'];
} else {
    $previous_release_date = $upcoming_releases[$requested_version - 1];
}

// Release n-2 Needed for nightly cycle length calculation
$pd_key = 'firefox-' . number_format(($requested_version - 2.0), 1);
if (array_key_exists($pd_key, $shipped_releases)) {
    $nightly_start_date = $shipped_releases[$pd_key]['date'];
} else {
    $nightly_start_date = $upcoming_releases[$requested_version - 2];
}

// Calculate the number of weeks between the 2 releases
$date1 = new DateTime($release_date);
$date2 = new DateTime($previous_release_date);
$date3 = new DateTime($nightly_start_date);
$beta_cycle_length = $date1->diff($date2)->days / 7;
$nightly_cycle_length = $date2->diff($date3)->days / 7;
