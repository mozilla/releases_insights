<?php

declare(strict_types=1);

use ReleaseInsights\{Data, ESR, Model, Template, Version, IosSchedule};
use DateTimeImmutable;
use DateTimeInterface;

$requested_version = Version::get();
$requested_version_int = (int) Version::get();

if ($requested_version == '0.0') {
    header('Location: /404/');
    return;
}

// Planned releases
$upcoming_releases = new Data()->getFutureReleases();
$owners = new Data()->release_owners;

// iOS milestones view: /release/?iosversion=NNN
if (isset($_GET['iosversion'])) {
    $ios_param = (string) $_GET['iosversion'];
    $ios_major = (int) preg_replace('/\D+/', '', $ios_param);

    if ($ios_major <= 0) {
        header('Location: /404/');
        return;
    }

    // We’ll reuse the existing models. They key off Version::get(), so temporarily
    // set ?version to the requested iOS major, call the model, then render & return.
    $original_version = $_GET['version'] ?? null;
    $_GET['version']  = (string) $ios_major;

    // Determine whether we should use past or future model based on RELEASE constant
    if ($ios_major <= RELEASE) {
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
            $dot_uplifts,
            $dot_uplifts_url,
            $dot_backouts_url,
            $dot_changelog,
            $rc_count,
            $beta_count,
            $dot_release_count,
            $dot_releases,
            $nightly_start_date,
            $beta_start_date,
            $firefox_releases,
            $no_planned_dot_releases,
            $release_rollout,
            $uptake,
            $chemspills,
        ] = new Model('past_release')->get();

        // For already-shipped versions, anchor to Desktop ship day
        $desktop_next_release = $last_release_date;
    } else {
        [
            $release_date,
            $beta_cycle_length,
            $nightly_cycle_length,
            $nightly_fixes,
            $nightly_updates,
            $nightly_emergency,
            $cycle_dates,
            $deadlines,
            $rollout,
            $wellness_days,
            $latest_nightly,
        ] = new Model('future_release')->get();

        // For future versions, anchor to Desktop NEXT_RELEASE_DATE
        $desktop_next_release = $cycle_dates['release'] ?? $release_date;
    }

        // Normalize the anchor to DateTimeImmutable for the scheduler
    if ($desktop_next_release instanceof DateTimeInterface) {
        $desktopNextRelease = ($desktop_next_release instanceof DateTimeImmutable)
            ? $desktop_next_release
            : DateTimeImmutable::createFromInterface($desktop_next_release);
    } else {
        $desktopNextRelease = new DateTimeImmutable((string) $desktop_next_release);
    }

    // Build the iOS weekly schedule from the Desktop anchor
    $scheduler    = new IosSchedule();
    $ios_schedule = $scheduler->buildFromDesktopNextReleaseDate(
        major: $ios_major,
        desktopNextReleaseDate: $desktopNextRelease,
        weeks: 4
    );

    // Owner lookup follows the same pattern as the main page (keys like "146.0")
    $requested_ios_key = $ios_major . '.0';
    $ios_owner = $owners[$requested_ios_key] ?? 'TBD';

    // Render the iOS Twig view and stop normal /release rendering
    (new Template('ios_release.html.twig', [
        'css_page_id'      => 'release_ios',
        'page_title'       => 'Firefox iOS milestones for ' . $ios_major,
        'release'          => $ios_major,
        'release_owner'    => $ios_owner,
        'desktop_release'  => $desktopNextRelease,
        'ios_schedule'     => $ios_schedule,
        'fallback_content' => '',
    ]))->render();

    // Restore original ?version just in case and exit this controller
    if ($original_version === null) {
        unset($_GET['version']);
    } else {
        $_GET['version'] = $original_version;
    }
    return;
}

$css_page_id = match (true) {
    $requested_version_int === NIGHTLY => 'release_nightly',
    $requested_version_int === BETA    => 'release_beta',
    $requested_version_int === RELEASE => 'release_current',
    $requested_version_int < RELEASE   => 'release_past',
    default                            => 'release_future',
};

$template_data = [
    'css_page_id'      => $css_page_id,
    'page_title'       => 'Milestones and key data for Firefox ' . $requested_version_int,
    'release'          => $requested_version_int,
    'release_owner'    => $owners[$requested_version] ?? 'TBD',
    'fallback_content' => '',
];

// Releases before version 4 were handled completely differently
if ($requested_version_int < 4) {
    [$dot_release_count, $release_date] = new Model('pre_firefox4_release')->get();
    $template_data += ['dot_release_count' => $dot_release_count];
    $template_data += ['release_date' => $release_date];
    new Template('pre4_release.html.twig', $template_data)->render();
    return;
}

$template_data = array_merge(
    $template_data,
    [
        'ESR'       => ESR::getVersion($requested_version_int),
        'OLDER_ESR' => ESR::getOlderSupportedVersion($requested_version_int),
        'ESR_115'   => ESR::getWin7SupportedVersion($requested_version_int),
    ]
);

if (isset($_GET['version']) && $_GET['version'] === 'esr') {
    [
        $next_ESR,
        $current_ESR,
        $release_date,
        $esr_calendar,
    ] = new Model('esr_release')->get();

    $template_data = array_merge($template_data, [
        'page_title'   => 'Firefox ESR schedule',
        'css_page_id'  => 'release_esr',
        'next_ESR'     => $next_ESR,
        'current_ESR'  => $current_ESR,
        'release_date' => $release_date,
        'esr_calendar' => $esr_calendar,
        'esr_majors'   => ESR::$esr_releases,
        'esr_115_eol'  => ESR::$esr115_EOL,
    ]);

    new Template('esr_release.html.twig', $template_data)->render();

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
        $dot_uplifts,
        $dot_uplifts_url,
        $dot_backouts_url,
        $dot_changelog,
        $rc_count,
        $beta_count,
        $dot_release_count,
        $dot_releases,
        $nightly_start_date,
        $beta_start_date,
        $firefox_releases,
        $no_planned_dot_releases,
        $release_rollout,
        $uptake,
        $chemspills,
    ] = new Model('past_release')->get();

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
        'dot_uplifts'             => $dot_uplifts,
        'dot_uplifts_url'         => $dot_uplifts_url,
        'dot_backouts_url'        => $dot_backouts_url,
        'dot_changelog'           => $dot_changelog,
        'rc_count'                => $rc_count,
        'beta_count'              => $beta_count,
        'dot_release_count'       => $dot_release_count,
        'dot_releases'            => $dot_releases,
        'nightly_start_date'      => $nightly_start_date,
        'beta_start_date'         => $beta_start_date,
        'firefox_releases'        => $firefox_releases,
        'no_planned_dot_releases' => $no_planned_dot_releases,
        'release_rollout'         => $release_rollout,
        'uptake'                  => $uptake,
        'chemspills'              => $chemspills,
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
        $deadlines,
        $rollout,
        $wellness_days,
        $latest_nightly,
    ] = new Model('future_release')->get();
    $template_file = 'future_release.html.twig';
    $template_data = array_merge($template_data, [
        'release_date'         => $release_date,
        'beta_cycle_length'    => $beta_cycle_length,
        'nightly_cycle_length' => $nightly_cycle_length,
        'nightly_fixes'        => $nightly_fixes,
        'nightly_updates'      => $nightly_updates,
        'nightly_emergency'    => $nightly_emergency,
        'cycle_dates'          => $cycle_dates,
        'deadlines'            => $deadlines,
        'beta_rollout'         => $rollout,
        'wellness_days'        => $wellness_days,
        'latest_nightly'       => $latest_nightly,
    ]);
} else {
    $template_file = 'future_release.html.twig';
    $template_data = array_merge($template_data, [
        'page_title'   => 'No information yet for this release',
        'fallback_content' => '<p class="alert alert-warning text-center w-50 mx-auto">The release date for this version is not yet available.</p>',
    ]);
}

new Template($template_file, $template_data)->render();
