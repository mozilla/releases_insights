<?php

// Analyse version requested

// If there is no version requested show the latest release
if (!isset($_GET['version'])) {
    $_GET['version'] = FIREFOX_RELEASE;
}
// Normalize version number to XX.y
$requested_version = abs((int) $_GET['version']);
$requested_version = number_format($requested_version, 1);

// Planned releases
$upcoming_releases = include DATA .'upcoming_releases.php';

// If this is a release we already shipped, display stats for the release
if ($requested_version <= FIREFOX_RELEASE) {
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
        'fallback_content'      => ''
    ];
} elseif ($requested_version > FIREFOX_RELEASE
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
        'cycle_dates'           => $cycle_dates,
        'fallback_content'      => ''
    ];

} else {
    $template_file = 'future_release.html.twig';
    $template_data = [
        'css_files'    => $css_files,
        'css_page_id'  => $controller,
        'page_title'   => $page_title,
        'release'      => (int) $requested_version,
        'fallback_content' => '<p class="alert alert-warning text-center w-25 mx-auto">The release date for this version is not yet available.</p>'
    ];

}

echo $twig->render($template_file, $template_data);
