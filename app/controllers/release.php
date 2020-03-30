<?php

require_once MODELS . 'release.php';

echo $twig->render(
    'release.html.twig',
    [
        'css_files'             => $css_files,
        'css_page_id'           => $css_page_id,
        'page_title'            => $page_title,
        'release'               => (int) $requested_version,
        'release_date'          => $last_release_date,
        'previous_release_date' => $previous_release_date,
        'beta_cycle_length'     => $beta_cycle_length,
        'nightly_cycle_length'  => $nightly_cycle_length,
        'nightly_fixes'         => $nightly_fixes,
        'beta_uplifts'          => $beta_uplifts,
        'rc_uplifts'            => $rc_uplifts,
        'rc_uplifts_url'        => $rc_uplifts_url,
        'rc_backouts_url'       => $rc_backouts_url,
        'beta_uplifts_url'      => $beta_uplifts_url,
        'beta_backouts_url'     => $beta_backouts_url,
        'rc_count'              => $rc_count,
        'beta_count'            => $beta_count,
        'dot_release_count'     => $dot_release_count,
    ]
);

