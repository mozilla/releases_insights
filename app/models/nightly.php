<?php

declare(strict_types=1);

use ReleaseInsights\Bugzilla as Bz;
use ReleaseInsights\Utils;

// We need previous and next days for navigation and changelog
// The requester date is already in the $date variable
$today          = date('Ymd');
$requested_date = Utils::getDate();
$previous_date  = date('Ymd', strtotime($requested_date . ' -1 day'));
$next_date      = date('Ymd', strtotime($requested_date . ' +1 day'));

// Get nightlies for the GET Request (or today's nightly)
$nightlies = include MODELS . 'api/nightly.php';

// Store a value for the View title
$display_date = strtotime($requested_date);
$fallback_nightly = false;

// This is a fallback mechanism for Buildhub which sometimes takes hours to have the latest nightly
if (empty($nightlies)) {
    // Get the latest nightly build ID, used as a tooltip on the nightly version number
    $latest_nightly = Utils::getJson(
        'https://archive.mozilla.org/pub/firefox/nightly/latest-mozilla-central/firefox-' . FIREFOX_NIGHTLY . '.en-US.win64.json',
        3600
    );

    // We want to make sure that the latest nightly from archive.mozilla.org is not yesterday's nightly
    if (isset($latest_nightly['buildid']) && $today === date('Ymd', strtotime($latest_nightly['buildid']))) {
        $nightlies = [
            $latest_nightly['buildid'] => [
                'revision' => $latest_nightly['moz_source_stamp'],
                'version'  => FIREFOX_NIGHTLY
            ]
        ];

        $fallback_nightly = true;
    }
    unset($latest_nightly);
}

// We now fetch the previous day nightlies because we need them for changelogs
$_GET['date'] = $previous_date;
$nightlies_day_before = include MODELS . 'api/nightly.php';

// Associate nightly with nightly-1
$nightly_pairs = [];

$i = true;
$previous_changeset = null;
foreach ($nightlies as $buildid => $changeset) {
    // The first build of the day is to associate with yesterday's last build
    if ($i === true) {
        $nightly_pairs[] = [
            'buildid'        => $buildid,
            'changeset'      => $changeset['revision'],
            'version'        => $changeset['version'],
            'prev_changeset' => end($nightlies_day_before)['revision'],
        ];
        $i = false;
        $previous_changeset = $changeset['revision'];
        continue;
    }

    $nightly_pairs[] = [
        'buildid'        => $buildid,
        'changeset'      => $changeset['revision'],
        'version'        => $changeset['version'],
        'prev_changeset' => $previous_changeset,
    ];
    $previous_changeset = $changeset['revision'];
}

$build_crashes = [];
$top_sigs = [];


// we fetch crashes from Socorro for the last 10 days only
if (($today - $requested_date) < 10) {
    foreach ($nightly_pairs as $dataset) {
        $build_crashes[$dataset['buildid']] = Utils::getCrashesForBuildID($dataset['buildid'])['total'];
    }

    foreach ($nightly_pairs as $dataset) {
        $top_sigs[$dataset['buildid']] = array_splice(
            Utils::getCrashesForBuildID($dataset['buildid'])['facets']['signature'],
            0,
            20
        );
    }
}
$bug_list = [];
foreach ($nightly_pairs as $dataset) {
    $bugs = Bz::getBugsFromHgWeb(
        'https://hg.mozilla.org/mozilla-central/json-pushes?fromchange=' . $dataset['prev_changeset'] . '&tochange=' . $dataset['changeset'] . '&full&version=2'
    )['total'];

    // There were no bugs in the build, it is the same as the previous one
    if (empty($bugs)) {
        $bug_list[$dataset['buildid']] = [
            'bugs'  => null,
            'url'   => '',
            'count' => 0,
        ];
        continue;
    }

    $url = Bz::getBugListLink($bugs);
    // Bugzilla REST API https://wiki.mozilla.org/Bugzilla:REST_API
    $bug_list_details= Utils::getJson('https://bugzilla.mozilla.org/rest/bug?include_fields=id,summary,product,component,type&bug_id=' . implode('%2C', $bugs))['bugs'];

    $bug_list[$dataset['buildid']] = [
        'bugs'  => $bug_list_details,
        'url'   => $url,
        'count' => is_countable($bugs) ? count($bugs) : 0,
    ];
}

$known_top_crashes = [
    'IPCError-browser | ShutDownKill | mozilla::ipc::MessagePump::Run',
    'IPCError-browser | ShutDownKill | NtYieldExecution',
    'IPCError-browser | ShutDownKill | EMPTY: no crashing thread identified; ERROR_NO_MINIDUMP_HEADER',
    'IPCError-browser | ShutDownKill',
    'OOM | small',
];


return [
    $display_date,
    $nightly_pairs,
    $build_crashes,
    $top_sigs,
    $bug_list,
    $previous_date,
    $requested_date,
    $next_date,
    $today,
    $known_top_crashes,
    $fallback_nightly
];
