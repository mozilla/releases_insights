<?php

require_once MODELS . 'nightly.php';

print $twig->render(
    'nightly.html.twig',
    [
        'css_files'         => $css_files,
        'css_page_id'       => $controller,
        'page_title'        => $page_title,
        'display_date'      => $display_date,
        'nightly_pairs'     => $nightly_pairs,
        'build_crashes'     => $build_crashes,
        'top_sigs'          => $top_sigs,
        'bug_list'          => $bug_list,
        'previous_date'     => $previous_date,
        'requested_date'    => $requested_date,
        'next_date'         => $next_date,
        'today'             => $today,
        'known_top_crashes' => $known_top_crashes,
    ]
);
