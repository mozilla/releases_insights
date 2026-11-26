<?php

declare(strict_types=1);

use ReleaseInsights\{Data, Model, Request, Template, UpliftCounts};

/*
    Two pages share this controller:
    - /esr/uplifts/graph/ : ESR uplifts per release cycle
    - /uplifts/graph/     : beta and RC uplifts of Firefox N stacked on the ESR
                            uplifts shipping with Firefox N, they cover the same period

    Each page has a chart of uplifts per cycle and a chart of uplifts per week,
    as cycle lengths vary (holiday cycles, 2-week cycles from Firefox 155).
 */
$with_beta = new Request(filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL))->path === '/uplifts/graph/';

$graph_start = 75; // Just after the move to a 4-week release cycle
$two_week_cadence_start = 155;

[, $esr_uplifts, , $in_progress_version] = new Model('esr_uplifts')->get();
$beta_uplifts = $with_beta ? new Model('beta_uplifts_graph')->get()[0] : [];
$cycle_weeks = UpliftCounts::getCycleWeeks(new Data()->getMajorReleases());

$versions = array_values(array_filter(
    array_keys($esr_uplifts),
    fn(int $version) => $version >= $graph_start
));
sort($versions);

$esr = [];
$beta = [];
$missing = false;
foreach ($versions as $version) {
    $esr_counts = $esr_uplifts[$version];
    $esr[$version] = in_array(null, $esr_counts, true) ? null : array_sum($esr_counts);
    if ($with_beta) {
        $beta[$version] = $beta_uplifts[$version] ?? null;
    }
    $missing = $missing || $esr[$version] === null || ($with_beta && $beta[$version] === null);
}

// Cycles without known release dates can't be measured, they are left out of the weekly chart
$weekly_versions = array_values(array_filter($versions, fn(int $version) => isset($cycle_weeks[$version])));
$per_week = fn(array $counts): array => array_map(
    fn(int $version) => $counts[$version] === null ? null : round($counts[$version] / $cycle_weeks[$version], 1),
    $weekly_versions
);

$datasets = fn(array $esr_values, array $beta_values): array => $with_beta
    ? [
        ['label' => 'ESR', 'color' => 'purple', 'data' => $esr_values, 'link' => '/esr/uplifts/#fx'],
        ['label' => 'Beta + RC', 'color' => 'orange', 'data' => $beta_values, 'link' => '/beta/?version='],
    ]
    : [
        ['label' => 'ESR', 'color' => 'cycle', 'data' => $esr_values, 'link' => '/esr/uplifts/#fx'],
    ];

$name = $with_beta ? 'Beta and ESR uplifts' : 'ESR uplifts';

$charts = [
    [
        'id'       => 'chart_totals',
        'title'    => $name . ' per release cycle',
        'subtitle' => $with_beta
            ? 'Uplifts to beta and to release candidates of Firefox N stacked on the ESR uplifts shipping with Firefox N'
            : 'Total of ESR uplifts shipping with each Firefox release, all ESR branches',
        'y_title'  => 'Number of uplifts',
        'labels'   => $versions,
        'datasets' => $datasets(array_values($esr), array_values($beta)),
        'weeks'    => array_map(fn(int $version) => $cycle_weeks[$version] ?? null, $versions),
    ],
    [
        'id'       => 'chart_weekly',
        'title'    => $name . ' per week',
        'subtitle' => 'Uplifts of each release cycle divided by its length in weeks, so cycles of different lengths can be compared',
        'y_title'  => 'Uplifts per week',
        'labels'   => $weekly_versions,
        'datasets' => $datasets($per_week($esr), $with_beta ? $per_week($beta) : []),
        'weeks'    => array_map(fn(int $version) => $cycle_weeks[$version], $weekly_versions),
    ],
];

new Template(
    'uplifts_graph.html.twig',
    [
        'page_title'             => $name . ' per release cycle',
        'css_page_id'            => $with_beta ? 'uplifts_graph' : 'esr_uplifts_graph',
        'page_url'               => $with_beta ? '/uplifts/graph/' : '/esr/uplifts/graph/',
        'charts'                 => $charts,
        'with_beta'              => $with_beta,
        'graph_start'            => $graph_start,
        'in_progress_version'    => $in_progress_version,
        'two_week_cadence_start' => $two_week_cadence_start,
        'missing'                => $missing,
    ]
)->render();
