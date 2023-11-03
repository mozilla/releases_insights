<?php

declare(strict_types=1);

use ReleaseInsights\{Data, ESR, Template, Utils, Version};

$requested_version = Version::get();
$requested_version_int = (int) Version::get();

if ($requested_version == '0.0') {
    header('Location: /404/');
    return;
}

// Planned releases
$upcoming_releases = (new Data())->getFutureReleases();
$owners = (new Data())->getOwners();

$template_data = [
    'css_page_id'      => 'release',
    'page_title'       => 'Milestones and key data for Firefox ' . $requested_version_int,
    'release'          => $requested_version_int,
    'release_owner'    => $owners[$requested_version] ?? 'TBD',
    'fallback_content' => '',
];

// Releases before version 4 were handled completely differently
if ($requested_version_int < 4) {
    [$dot_release_count, $release_date] = require MODELS . 'pre4_release.php';
    $template_data += ['dot_release_count' => $dot_release_count];
    $template_data += ['release_date' => $release_date];
    (new Template('pre4_release.html.twig', $template_data))->render();
    return;
}

$template_data = array_merge(
    $template_data,
    [
        'ESR'       => ESR::getVersion($requested_version_int),
        'OLDER_ESR' => ESR::getOlderSupportedVersion($requested_version_int),
    ]
);

if (isset($_GET['version']) && $_GET['version'] === 'esr') {
    [
        $next_ESR,
        $current_ESR,
        $release_date,
        $esr_calendar,
    ] = require_once MODELS . 'esr_release.php';

    $template_data = array_merge($template_data, [
        'page_title'   => 'Firefox ESR schedule',
        'css_page_id'  => 'release_esr',
        'next_ESR'     => $next_ESR,
        'current_ESR'  => $current_ESR,
        'release_date' => $release_date,
        'esr_calendar' => $esr_calendar,
        'esr_majors'   => ESR::$esr_releases,
    ]);

    (new Template('esr_release.html.twig', $template_data))->render();

    return;
}

// If this is a release we already shipped, display stats for the release
if ($requested_version_int <= RELEASE) {
    [
        $last_release_date,
        $previous_release_date,
        $beta_cycle_length,
        $nightly_cycle_length,
        $nightly_fixes,
        $beta_changelog,
        $beta_uplifts,
        $rc_uplifts,
        $rc_changelog,
        $rc_uplifts_url,
        $rc_backouts_url,
        $beta_uplifts_url,
        $beta_backouts_url,
        $rc_count,
        $beta_count,
        $dot_release_count,
        $nightly_start_date,
        $beta_start_date,
        $firefox_releases,
        $no_planned_dot_releases,
    ] = require_once MODELS . 'past_release.php';

    $template_file = 'past_release.html.twig';
    $template_data = array_merge($template_data, [
        'release_date'            => $last_release_date,
        'previous_release_date'   => $previous_release_date,
        'beta_cycle_length'       => $beta_cycle_length,
        'nightly_cycle_length'    => $nightly_cycle_length,
        'nightly_fixes'           => $nightly_fixes,
        'beta_changelog'          => $beta_changelog,
        'beta_uplifts'            => $beta_uplifts,
        'rc_uplifts'              => $rc_uplifts,
        'rc_changelog'            => $rc_changelog,
        'rc_uplifts_url'          => $rc_uplifts_url,
        'rc_backouts_url'         => $rc_backouts_url,
        'beta_uplifts_url'        => $beta_uplifts_url,
        'beta_backouts_url'       => $beta_backouts_url,
        'rc_count'                => $rc_count,
        'beta_count'              => $beta_count,
        'dot_release_count'       => $dot_release_count,
        'nightly_start_date'      => $nightly_start_date,
        'beta_start_date'         => $beta_start_date,
        'firefox_releases'        => $firefox_releases,
        'no_planned_dot_releases' => $no_planned_dot_releases,
    ]);
} elseif ($requested_version_int > RELEASE
    && array_key_exists($requested_version, $upcoming_releases)) {
    [
        $release_date,
        $beta_cycle_length,
        $nightly_cycle_length,
        $nightly_fixes,
        $nightly_updates,
        $nightly_emergency,
        $cycle_dates,
        $days_before_beta,
        $days_before_release,
        $deadlines,
    ] = require_once MODELS . 'future_release.php';
    $template_file = 'future_release.html.twig';
    $template_data = array_merge($template_data, [
        'release_date'         => $release_date,
        'beta_cycle_length'    => $beta_cycle_length,
        'nightly_cycle_length' => $nightly_cycle_length,
        'nightly_fixes'        => $nightly_fixes,
        'nightly_updates'      => $nightly_updates,
        'nightly_emergency'    => $nightly_emergency,
        'cycle_dates'          => $cycle_dates,
        'days_before_beta'     => $days_before_beta,
        'days_before_release'  => $days_before_release,
        'deadlines'            => $deadlines,
    ]);
} else {
    $template_file = 'future_release.html.twig';
    $template_data = array_merge($template_data, [
        'page_title'   => 'No information yet for this release',
        'fallback_content' => '<p class="alert alert-warning text-center w-50 mx-auto">The release date for this version is not yet available.</p>',
    ]);
}

(new Template($template_file, $template_data))->render();
