<?php

// Analyse version requested

// If there is no version requested show the latest release
if (!isset($_GET['version'])) {
    $_GET['version'] = FIREFOX_RELEASE;
}

// Normalize version number to XX.y
$requested_version = abs((int) $_GET['version']);
$requested_version = number_format($requested_version, 1);

// If this is a release we already shipped, display stats for the release
if ($requested_version <= FIREFOX_RELEASE)  {
    require_once MODELS . 'past_release.php';
    $template_file = 'past_release.html.twig';
    $template_data = [
        'css_files'             => $css_files,
        'css_page_id'           => $css_page_id,
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
    ];
}

if ($requested_version > FIREFOX_RELEASE)  {
    require_once MODELS . 'future_release.php';
    $template_file = 'future_release.html.twig';
    $template_data = [
        'css_files'    => $css_files,
        'css_page_id'  => $css_page_id,
        'page_title'   => $page_title,
        'release'      => (int) $requested_version,
        'page_content' => 'Version not released yet.'
    ];

}

echo $twig->render($template_file, $template_data);
