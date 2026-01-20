<?php

declare(strict_types=1);

use BzKarma\Scoring;
use ReleaseInsights\{Bugzilla, Json, URL, Request, Utils};

/*
    We need previous and next days for navigation and changelog
    The requester date is already in the $date variable
*/
$today          = date('Ymd');
$requested_date = Utils::getDate();
$previous_date  = date('Ymd', strtotime($requested_date . ' -1 day'));
$next_date      = date('Ymd', strtotime($requested_date . ' +1 day'));

// We may have to display a warning message because an external resource is down
$warning = '';

// Get nightlies for the GET Request (or today's nightly)
$nightlies = include MODELS . 'api/nightly.php';

if (! empty($nightlies)) {
    Request::waitingPage('load');
}

// Store a value for the View title
$display_date = strtotime($requested_date);
$fallback_nightly = false;

// This is a fallback mechanism for Buildhub which sometimes takes hours to have the latest nightly
if (empty($nightlies)) {
    // Get the latest nightly build ID, used as a tooltip on the nightly version number
    $latest_nightly = Json::load(
        URL::Archive->value .  'pub/firefox/nightly/latest-mozilla-central/firefox-' . FIREFOX_NIGHTLY . '.en-US.win64.json',
        900
    );

    // We want to make sure that the latest nightly from archive.mozilla.org is not yesterday's nightly
    if (isset($latest_nightly['buildid']) && $today === date('Ymd', strtotime((string) $latest_nightly['buildid']))) {
        $nightlies = [
            $latest_nightly['buildid'] => [
                'revision' => $latest_nightly['moz_source_stamp'],
                'version'  => FIREFOX_NIGHTLY,
            ],
        ];

        $fallback_nightly = true;
    }
    unset($latest_nightly);
}

// We now fetch the previous day nightlies because we need them for changelogs
$_GET['date'] = $previous_date;
$nightlies_day_before = include MODELS . 'api/nightly.php';

/*
    If we didn't ship any nightly the day before, check previous days.
    We don't go further than 7 days back.
 */
if (empty($nightlies_day_before)) {
    foreach(range(1,7) as $i) {
        $_GET['date'] = date('Ymd', strtotime($requested_date . " -{$i} days"));
        $nightlies_day_before = include MODELS . 'api/nightly.php';
        if (! empty($nightlies_day_before)) {
            break;
        }
    }
}

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

// We fetch crashes from Socorro for the last 10 days only
$days_elapsed = date_diff(date_create(date($today)), date_create($requested_date))->days;
if ($days_elapsed < 10) {
    foreach ($nightly_pairs as $dataset) {
        $build_crashes[$dataset['buildid']] = Utils::getCrashesForBuildID($dataset['buildid'])['total'] ?? null;
        if (is_null($build_crashes[$dataset['buildid']])) {
            $warning = "Socorro is not providing data";
            continue;
        }
    }

    foreach ($nightly_pairs as $dataset) {
        $sig = Utils::getCrashesForBuildID($dataset['buildid'])['facets']['signature'] ?? null;
        if (is_null($sig)) {
            $warning = "Socorro is not providing data";
            continue;
        }
        $top_sigs[$dataset['buildid']] = array_splice($sig, 0, 20);
    }
}

$files = [];
$bug_list = [];
$bug_list_karma = [];
$bug_list_karma_details = [];
foreach ($nightly_pairs as $dataset) {
    $bugs = Bugzilla::getBugsFromHgWeb(
        URL::Mercurial->value
        . 'mozilla-central/json-pushes?fromchange='
        . $dataset['prev_changeset']
        . '&tochange='
        . $dataset['changeset']
        . '&full&version=2'
    );

    $files = $bugs['files'] + $files;
    $bugs = $bugs['total'];

    $bug_list_karma = array_unique([...$bugs,...$bug_list_karma]);

    // There were no bugs in the build, it is the same as the previous one
    if (empty($bugs)) {
        $bug_list[$dataset['buildid']] = [
            'bugs'  => null,
            'url'   => '',
            'count' => 0,
        ];
        continue;
    }

    $url = Bugzilla::getBugListLink($bugs);

    // Bugzilla REST API https://wiki.mozilla.org/Bugzilla:REST_API
    $bug_list_details = Json::load(URL::Bugzilla->value . 'rest/bug?include_fields=id,summary,priority,severity,keywords,product,component,type,duplicates,regressions,cf_webcompat_priority,cf_performance_impact,cf_tracking_firefox' . NIGHTLY . ',cf_tracking_firefox' . BETA . ',cf_tracking_firefox' . RELEASE . ',cf_status_firefox' . NIGHTLY . ',cf_status_firefox' . BETA . ',cf_status_firefox' . RELEASE . ',cc,see_also&bug_id=' . implode('%2C', $bugs));

    $bug_list[$dataset['buildid']] = [
        'bugs'  => $bug_list_details['bugs'],
        'url'   => $url,
        'count' => is_countable($bugs) ? count($bugs) : 0,
    ];

    $bug_list_karma_details = [...$bug_list_details['bugs'], ...$bug_list_karma_details];
}

// Create the real bug list Karma
sort($bug_list_karma);
$bug_list_karma = array_map('intval', $bug_list_karma);
$bug_list_karma = array_values($bug_list_karma);
$bug_list_karma = array_flip($bug_list_karma);

$scores = new Scoring($bug_list_karma_details, RELEASE);

// The $bug_list_karma array has bug numbers as keys and score (ints) as values
foreach ($bug_list_karma as $key => $value) {
    $bug_list_karma[$key] = [
        'score'   => $scores->getBugScore($key),
        'details' => $scores->getBugScoreDetails($key),
    ];
}

$known_top_crashes = [
    'IPCError-browser | ShutDownKill | mozilla::ipc::MessagePump::Run',
    'IPCError-browser | ShutDownKill | NtYieldExecution',
    'IPCError-browser | ShutDownKill | EMPTY: no crashing thread identified; ERROR_NO_MINIDUMP_HEADER',
    'IPCError-browser | ShutDownKill',
    'OOM | small',
];

$top_sigs_worth_a_bug = [];
foreach ($top_sigs as $values) {
    foreach ($values as $target) {
        if (in_array($target['term'], $known_top_crashes)) {
            continue;
        }
        if (isset($top_sigs_worth_a_bug[$target['term']])){
            $top_sigs_worth_a_bug[$target['term']] += $target['count'];
        } else {
            $top_sigs_worth_a_bug[$target['term']] = $target['count'];
        }
    }
}
// We take 10 crashes for a day as a treshold
$top_sigs_worth_a_bug = array_filter($top_sigs_worth_a_bug, fn($n) => $n > 10);

// We escape weird crash signature characters for url use
$top_sigs_worth_a_bug = array_keys($top_sigs_worth_a_bug);
$top_sigs_worth_a_bug = array_map('urlencode', $top_sigs_worth_a_bug);

// Query bugs for signatures
$crash_bugs = [];
if (! empty($top_sigs_worth_a_bug)) {
    foreach ($top_sigs_worth_a_bug as $sig) {
        $bugs_for_top_sigs = Utils::getBugsforCrashSignature($sig, 30)['hits']; // short 30s cache intended
        $tmp = array_column($bugs_for_top_sigs, 'id');
        if (!empty($tmp)) {
            $crash_bugs[urldecode($sig)] = max(
                array_unique(
                    array_column($bugs_for_top_sigs, 'id')
                )
            );
        }
    }
}

// In this section, we extract outstanding bugs
$outstanding_bugs = [];
$bug_changed_pref = [];
foreach ($bug_list as $key => $values) {

    if (empty($values['bugs'])) {
        // We may have a build with no patch from Bugzilla
        continue;
    }

    foreach ($values['bugs'] as $bug_details) {
        // Touches a feature flag file
        $file_match = false;
        $key_files = [
            'modules/libpref/init/StaticPrefList.yaml',
            'toolkit/components/pdfjs/PdfJsOverridePrefs.js',
            'browser/app/profile/firefox.js',
            'modules/libpref/init/all.js',
        ];
        foreach ($files[$bug_details['id']] as $file) {
            if (in_array($file, $key_files)) {
                $file_match = true;
                break;
            }
        }

        if ($file_match) {
            $outstanding_bugs[$key]['bugs'][] = $bug_details;
            // We keep track of files that modify a pref to expose them in the Twig template
            $bug_changed_pref[] = $bug_details['id'];
            continue;
        }


        // Old bugs fixed are often interesting
        if ($bug_details['id'] < 1_500_000) {
            $outstanding_bugs[$key]['bugs'][] = $bug_details;
            continue;
        }

        // Enhancements are potential release notes additions
        if ($bug_details['type'] == 'enhancement') {
            $outstanding_bugs[$key]['bugs'][] = $bug_details;
            continue;
        }

        // High karma
        if ($bug_list_karma[$bug_details['id']]['score'] > 15) {
            $outstanding_bugs[$key]['bugs'][] = $bug_details;
            continue;
        }
    }
}

$bug_changed_pref = array_unique($bug_changed_pref);

if (! empty($nightlies)) {
    Request::waitingPage('hide');
}

return [
    $display_date,
    $nightly_pairs,
    $build_crashes,
    $top_sigs,
    $crash_bugs,
    $bug_list,
    $bug_list_karma,
    $outstanding_bugs,
    $previous_date,
    $requested_date,
    $next_date,
    $today,
    $known_top_crashes,
    $fallback_nightly,
    $warning,
    $bug_changed_pref,
];
