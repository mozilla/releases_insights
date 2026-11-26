<?php

declare(strict_types=1);

use ReleaseInsights\{Model, Template};

[$esr_releases, $esr_uplifts, $log_links, $in_progress_version] = new Model('esr_uplifts')->get();

// One column per ESR branch that received uplifts
$branches = [];
foreach ($esr_uplifts as $counts) {
    $branches = [...$branches, ...array_keys($counts)];
}
$branches = array_unique($branches);
sort($branches);

$branch_totals = [];
foreach ($branches as $branch) {
    $branch_totals[$branch] = array_sum(array_column($esr_uplifts, $branch));
}

// A cycle total is not available if one of its logs isn't
$row_totals = array_map(
    fn(array $counts) => in_array(null, $counts, true) ? null : array_sum($counts),
    $esr_uplifts
);

// Most recent cycles first
krsort($esr_uplifts);

new Template(
    'esr_uplifts.html.twig',
    [
        'page_title'          => 'ESR uplifts per release cycle',
        'css_page_id'         => 'esr_uplifts',
        'esr_releases'        => $esr_releases,
        'esr_uplifts'         => $esr_uplifts,
        'log_links'           => $log_links,
        'branches'            => $branches,
        'branch_totals'       => $branch_totals,
        'row_totals'          => $row_totals,
        'in_progress_version' => $in_progress_version,
        'missing'             => in_array(null, $row_totals, true),
    ]
)->render();
