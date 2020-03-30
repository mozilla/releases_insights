<?php

require_once MODELS . 'release.php';

echo $twig->render(
    'release.html.twig',
    [
        'release'               => (int) $requested_version,
        'page_title'            => $page_title,
        'release_date'          => $last_release_date,
        'previous_release_date' => $previous_release_date,
        'beta_cycle_length'     => $beta_cycle_length,
        'nightly_cycle_length'  => $nightly_cycle_length,
        'beta_uplifts'          => $beta_uplifts,
        'rc_uplifts'            => $rc_uplifts,
        'rc_uplifts_url'        => $rc_uplifts_url,
        'rc_backouts_url'       => $rc_backouts_url,
        'beta_uplifts_url'      => $beta_uplifts_url,
        'beta_backouts_url'     => $beta_backouts_url,
        'rc_count'              => $rc_count,
        'beta_count'            => $beta_count,
    ]
);

