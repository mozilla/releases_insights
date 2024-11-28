<?php

declare(strict_types=1);

use ReleaseInsights\{Bugzilla, Data, Duration, Json, Nightly, Release, URL, Version};

$requested_version = Version::get();

$data = new Data();

if ($data->isTodayReleaseDay()) {
    // On release day we want to force fetching product-details on all page views
    $data->cache_duration = 1;
}

$all_releases = $data->getMajorReleases();

// Avoid a warning when we have a race condition in product-details
$release_date = $all_releases[(string) $requested_version] ?? '';

// Future release date object
$release = new DateTime($release_date);

// Previous release object
$previous_release = new DateTime($all_releases[Version::decrement($requested_version, 1)]);

// Release n-2 Needed for nightly cycle length calculation
$nightly_start = new DateTime($all_releases[Version::decrement($requested_version, 2)]);

// Calculate the number of weeks between the 2 releases
$beta_cycle_length = $release->diff($previous_release)->days / 7;
$nightly_cycle_length = $previous_release->diff($nightly_start)->days / 7;

// Get the schedule for the release requested
$cycle_dates = new Release($requested_version)->getSchedule();

$nightly_fixes = 0;
/* Only for the current Beta view */
$nightly_fixes = 0;
if ((int) $requested_version === BETA) {
    // Number of bugs fixed in nightly
    $nightly_fixes = Bugzilla::getBugsFromHgWeb(
        URL::Mercurial->value
        . 'mozilla-central/json-pushes'
        . '?fromchange=FIREFOX_NIGHTLY_' . ((int) $requested_version - 1) . '_END'
        . '&tochange=FIREFOX_NIGHTLY_' . (int) $requested_version .'_END'
        . '&full&version=2',
        true,
        -1 // Immutable external data, store forever
    );
}

$nightly_updates = true;
$nightly_emergency = '';
/* Only for the current Nightly view, this makes an HTTP request */
if ((int) $requested_version == NIGHTLY) {
    // Are nightly updates activated?
    $nightly_state     = new Nightly();
    $nightly_updates   = $nightly_state->auto_updates;
    $nightly_emergency = Bugzilla::linkify($nightly_state->emergency_message);
}

$deadlines = [];
foreach ($cycle_dates as $k => $date) {
    // Not a real milestone
    if ($k === 'version') {
        continue;
    }

    // let's ignore hours and only consider full days for simplicity
    $reference_date = new DateTime($date)->setTime(0, 0);
    $time = new Duration(new DateTime(), $reference_date);
    $deadlines[$k] = $time->report();

    if ($reference_date>= new DateTime()) {
        // Future
        if ($time->report()['weeks'] >= 2) {
            $deadlines[$k]['message'] = 'In ' . $time->report()['weeks'] . ' weeks';
        } else {
            $deadlines[$k]['message'] = match($time->report()['workdays']) {
                0  => 'No working day before milestone',
                1  => '1 working day',
                default => 'In ' . $time->report()['workdays'] . ' working days',
            };
        }
    } else {
        // Past
        if ($time->report()['weeks'] >= 2) {
            $deadlines[$k]['message'] = $time->report()['weeks'] . ' weeks ago';
        } else {
            $deadlines[$k]['message'] = match($time->report()['days']) {
                -1 => 'yesterday',
                 0 => 'Today',
                 1 => 'Yesterday',
                default => $time->report()['days'] . ' days ago',
            };
        }
    }
}

// Check current rollout for the beta channel
if ((int) $requested_version === BETA) {
    $rollout = Json::load(URL::Balrog->value . 'rules/firefox-beta')['backgroundRate'];
}

return [
    $release_date,
    $beta_cycle_length,
    $nightly_cycle_length,
    $nightly_fixes,
    $nightly_updates,
    $nightly_emergency,
    $cycle_dates,
    $deadlines,
    $rollout ?? -1,
    $wellness_days = new Data()->wellness_days,
    Nightly::getLatestBuildID(),
];
