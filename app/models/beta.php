<?php

declare(strict_types=1);

use ReleaseInsights\{Beta, Bugzilla, Json, Request, Utils, URL};

$waiting_page = false;
$lock_file = CACHE_PATH . 'beta_lock.cache';
if (! file_exists($lock_file) OR time()-filemtime($lock_file) > 900) {
    $waiting_page = true;
    Request::waitingPage('load');
}

$beta = new Beta();

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
        $fields = array_flip($bz_fields);
        foreach ($fields as $key => $value) {
            if ($key == 'product' || $key == 'component' ) {
                $fields[$key] = 'Other';
                $fields['summary'] = '…';
                continue;
            }
            $fields[$key] = 'N/A';
        }
        return $fields;
    };

    foreach ($hidden_bugs as $key => $value) {
        $hidden_bugs[$key] = $bug_template();
        $hidden_bugs[$key]['id'] = $value;
    }

    $bug_list_details[$version] = [...$bug_list_details[$version], ...$hidden_bugs];
}

$stats = [];
foreach ($bug_list_details as $bugs) {
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
        $bugs_for_top_sigs = Utils::getBugsforCrashSignature($sig)['hits'] ?? [];
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

// Write a lock file for the page to keep track of its age
file_put_contents($lock_file, '');

// Generate Bugzilla links per beta of all the bugs fixed/backedout
$bugzilla_links = [];
foreach ($bug_list_details as $version => $details) {
    $bugzilla_links[$version] =
    Bugzilla::getBugListLink(array_column($details, 'id'))
    . '&title=' . $version . '%20:%20Uplifts%20and%20backouts';
}

if ($waiting_page) {
    Request::waitingPage('leave');
}

return [
    $beta, // this is the whole Beta object
    $bug_list_details,
    $uplift_counter,
    $stats,
    $known_top_crashes,
    $crash_bugs,
    $bugzilla_links,
];