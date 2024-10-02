<?php

declare(strict_types=1);

use ReleaseInsights\{Beta, Bugzilla, Json, Utils, URL};
use ReleaseInsights\Debug as D;

$beta = new Beta();
D::dump($beta);


$uplift_counter = 0;
$bug_list_details = [];

foreach ($beta->uplifts() as $version => $details) {
    // We count all uplifts, including backouts
    $uplifts = count($details['total']);
    $uplift_counter += $uplifts;

    // We Query Bugzilla for bug details
    $bz_fields = ['id', 'summary', 'priority', 'severity', 'product', 'component', 'type'];
    $bug_list_details[$version] = [];
    if ($uplifts > 0) {
        $bug_list_details[$version] = Json::load(
            URL::Bugzilla->value
            . 'rest/bug?include_fields='
            . implode(',', $bz_fields)
            . '&bug_id='
            . implode('%2C', $details['total']),
            3600*24
        )['bugs'] ?? [];
    }

    /*
        The Bugzilla API does not send all bug results without auth
        and we don't want auth on this app. That's why we make a
        diff between the bugs mentionned in the logs and the ones
        that the Bugzilla API answers to.
    */
    $hidden_bugs = array_values(
        array_diff(
            $details['total'],
            array_column($bug_list_details[$version], 'id')
        )
    );

    // Create a blank template for bugs not populated by Bugzilla
    $bug_template = function () use ($bz_fields) {
        $bug = array_flip($bz_fields);
        foreach ($bug as $key => $value) {
            $bug[$key] = 'N/A';
        }
        return $bug;
    };

    foreach ($hidden_bugs as $key => $value) {
        $hidden_bugs[$key] = $bug_template();
        $hidden_bugs[$key]['id'] = $value;
    }

    $bug_list_details[$version] = [...$bug_list_details[$version], ...$hidden_bugs];
}

$stats = [];
foreach ($bug_list_details as $build => $bugs) {
    foreach($bugs as $fields) {
        if (! isset($stats[$fields['product']])) {
            $stats[$fields['product']] = [];
        }
        $stats[$fields['product']]['bugs'][] = $fields['id'];
    }
}

arsort($stats);

foreach ($stats as $product => $bugs) {
    $stats[$product]['bugzilla'] = Bugzilla::getBugListLink($stats[$product]['bugs']);
}

$known_top_crashes = [
    'IPCError-browser | ShutDownKill | mozilla::ipc::MessagePump::Run',
    'IPCError-browser | ShutDownKill | NtYieldExecution',
    'IPCError-browser | ShutDownKill | EMPTY: no crashing thread identified; ERROR_NO_MINIDUMP_HEADER',
    'IPCError-browser | ShutDownKill',
    'OOM | small',
];

$top_sigs_worth_a_bug = [];

foreach ($beta->crashes() as $k => $values) {
    if ($k == 'summary') {
        continue;
    }
    foreach ($values['signatures'] as $target) {
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
        $bugs_for_top_sigs = Utils::getBugsforCrashSignature($sig)['hits'];
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

\ReleaseInsights\Debug::dump($crash_bugs);
return [
    $beta, // this is the whole Beta object
    $bug_list_details,
    $uplift_counter,
    $stats,
    $known_top_crashes,
    $crash_bugs,
];