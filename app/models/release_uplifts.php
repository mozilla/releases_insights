<?php

declare(strict_types=1);

use ReleaseInsights\{Bugzilla, Data, Json, Request, ReleaseUplifts, Utils, URL};

$requested_version = isset($_GET['version']) ? (int) $_GET['version'] : RELEASE;
$requested_version = max(1, min(RELEASE, $requested_version));
$is_current_release = ($requested_version === RELEASE);

$waiting_page = false;
$lock_file = CACHE_PATH . 'release_uplifts_lock_' . $requested_version . '.cache';
$lock_ttl = $is_current_release ? 900 : PHP_INT_MAX;
if (! file_exists($lock_file) OR time()-filemtime($lock_file) > $lock_ttl) {
    $waiting_page = true;
    Request::waitingPage('load');
}

$release_uplifts = new ReleaseUplifts($requested_version);

$uplift_counter = 0;
$bug_list_details = [];
$bz_fields = ['id', 'summary', 'priority', 'severity', 'product', 'component', 'type'];

$uplifts = $release_uplifts->uplifts();

foreach ($uplifts as $version => $details) {
    $uplift_counter += count($details['total']);
    $bug_list_details[$version] = [];
}

// Fetch all bug details in a single batched Bugzilla request
$all_bug_ids = array_unique(array_merge(...array_map(fn($d) => $d['total'], array_values($uplifts))));

$bz_ttl = $is_current_release ? 3600 * 24 : 3600 * 24 * 30;
$all_bugs_indexed = [];
foreach (array_chunk($all_bug_ids, 100) as $chunk) {
    $bz_response = Json::load(
        URL::Bugzilla->value
        . 'rest/bug?include_fields='
        . implode(',', $bz_fields)
        . '&bug_id='
        . implode('%2C', $chunk),
        $bz_ttl
    )['bugs'] ?? [];
    $all_bugs_indexed += array_column($bz_response, null, 'id');
}

$bug_template = function () use ($bz_fields) {
    $fields = array_flip($bz_fields);
    foreach ($fields as $key => $value) {
        if ($key == 'product' || $key == 'component') {
            $fields[$key] = 'Other';
            $fields['summary'] = '…';
            continue;
        }
        $fields[$key] = 'N/A';
    }
    return $fields;
};

foreach ($uplifts as $version => $details) {
    $bug_list_details[$version] = array_values(
        array_intersect_key($all_bugs_indexed, array_flip($details['total']))
    );

    $hidden_bugs = array_values(
        array_diff(
            $details['total'],
            array_column($bug_list_details[$version], 'id')
        )
    );

    foreach ($hidden_bugs as $key => $value) {
        $hidden_bugs[$key] = $bug_template();
        $hidden_bugs[$key]['id'] = $value;
    }

    $bug_list_details[$version] = [...$bug_list_details[$version], ...$hidden_bugs];
}

$stats = [];
foreach ($bug_list_details as $bugs) {
    foreach ($bugs as $fields) {
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
$crash_bugs = [];

if ($is_current_release) {
    foreach ($release_uplifts->crashes() as $k => $values) {
        if ($k == 'summary') {
            continue;
        }
        foreach ($values['signatures'] as $target) {
            if (in_array($target['term'], $known_top_crashes)) {
                continue;
            }
            if (isset($top_sigs_worth_a_bug[$target['term']])) {
                $top_sigs_worth_a_bug[$target['term']] += $target['count'];
            } else {
                $top_sigs_worth_a_bug[$target['term']] = $target['count'];
            }
        }
    }

    $top_sigs_worth_a_bug = array_filter($top_sigs_worth_a_bug, fn($n) => $n > 10);
    $top_sigs_worth_a_bug = array_keys($top_sigs_worth_a_bug);
    $top_sigs_worth_a_bug = array_map('urlencode', $top_sigs_worth_a_bug);

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
}

file_put_contents($lock_file, '');

$bugzilla_links = [];
foreach ($bug_list_details as $version => $details) {
    $bugzilla_links[$version] =
        Bugzilla::getBugListLink(array_column($details, 'id'))
        . '&title=' . urlencode($version . ' : Uplifts and backouts');
}

if ($waiting_page) {
    Request::waitingPage('leave');
}

$chemspill_releases = (new Data())->chemspills;

return [
    $release_uplifts,
    $bug_list_details,
    $uplift_counter,
    $stats,
    $known_top_crashes,
    $crash_bugs,
    $bugzilla_links,
    $chemspill_releases,
];
