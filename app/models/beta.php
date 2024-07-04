<?php

declare(strict_types=1);

use ReleaseInsights\{Beta, Bugzilla, Json, URL};

$beta = new Beta();

$uplift_counter = 0;
$bug_list_details = [];

foreach ($beta->report() as $version => $details) {
    // We count all uplifts, including backouts
    $uplifts = count($details['total']);
    $uplift_counter += $uplifts;

    // We Query Bugzilla for bug details
    $bz_fields = ['id', 'summary', 'priority', 'severity', 'product', 'component', 'type'];
    $bug_list_details[$version] = Json::load(
        URL::Bugzilla->value
        . 'rest/bug?include_fields='
        . implode(',', $bz_fields)
        . '&bug_id='
        . implode('%2C', $details['total']),
        3600*24
    )['bugs'] ?? [];

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

return [
    $beta->report(),
    $bug_list_details,
    $uplift_counter,
    $stats,
];