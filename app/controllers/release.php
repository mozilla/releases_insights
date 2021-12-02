<?php

declare(strict_types=1);

use ReleaseInsights\Utils;
use ReleaseInsights\ESR;

$requested_version = Utils::requestedVersion();

// Planned releases
$upcoming_releases = include DATA .'upcoming_releases.php';
$release_owners    = include DATA .'release_owners.php';
$release_owner     = $release_owners[$requested_version] ?? 'TBD';
$page_title        = 'Milestones and key data for Firefox ' . (int) $requested_version;

// If this is a release we already shipped, display stats for the release
if ((int) $requested_version <= (int) FIREFOX_RELEASE) {
    require_once MODELS . 'past_release.php';
    $template_file = 'past_release.html.twig';
    $template_data = [
        'css_files'             => $css_files,
        'css_page_id'           => $controller,
        'page_title'            => $page_title,
        'release'               => (int) $requested_version,
        'release_date'          => $last_release_date,
        'previous_release_date' => $previous_release_date,
        'beta_cycle_length'     => $beta_cycle_length,
        'nightly_cycle_length'  => $nightly_cycle_length,
        'nightly_fixes'         => $nightly_fixes,
        'beta_changelog'        => $beta_changelog,
        'beta_uplifts'          => $beta_uplifts,
        'rc_uplifts'            => $rc_uplifts,
        'rc_changelog'          => $rc_changelog,
        'rc_uplifts_url'        => $rc_uplifts_url,
        'rc_backouts_url'       => $rc_backouts_url,
        'beta_uplifts_url'      => $beta_uplifts_url,
        'beta_backouts_url'     => $beta_backouts_url,
        'rc_count'              => $rc_count,
        'beta_count'            => $beta_count,
        'dot_release_count'     => $dot_release_count,
        'release_owner'         => $release_owner,
        'fallback_content'      => '',
        'ESR'                   => ESR::getVersion((int) $requested_version),
        'PREVIOUS_ESR'          => ESR::getOlderSupportedVersion((int) $requested_version),
    ];
} elseif ((int) $requested_version > (int) FIREFOX_RELEASE
    && array_key_exists($requested_version, $upcoming_releases)) {
    require_once MODELS . 'future_release.php';
    $template_file = 'future_release.html.twig';
    $template_data = [
        'css_files'             => $css_files,
        'css_page_id'           => $controller,
        'page_title'            => $page_title,
        'release'               => (int) $requested_version,
        'release_date'          => $release_date,
        'beta_cycle_length'     => $beta_cycle_length,
        'nightly_cycle_length'  => $nightly_cycle_length,
        'nightly_fixes'         => $nightly_fixes,
        'cycle_dates'           => $cycle_dates,
        'release_owner'         => $release_owner,
        'fallback_content'      => '',
        'ESR'                   => ESR::getVersion((int) $requested_version),
        'PREVIOUS_ESR'          => ESR::getOlderSupportedVersion((int) $requested_version),
    ];
} else {
    $template_file = 'future_release.html.twig';
    $template_data = [
        'css_files'    => $css_files,
        'css_page_id'  => $controller,
        'page_title'   => 'No information yet for this release',
        'release'      => (int) $requested_version,
        'fallback_content' => '<p class="alert alert-warning text-center w-50 mx-auto">The release date for this version is not yet available.</p>',
    ];
}

print $twig->render($template_file, $template_data);
