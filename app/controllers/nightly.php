<?php

declare(strict_types=1);

[
    $display_date,
    $nightly_pairs,
    $build_crashes,
    $top_sigs,
    $bug_list,
    $bug_list_karma,
    $previous_date,
    $requested_date,
    $next_date,
    $today,
    $known_top_crashes,
    $fallback_nightly,
] = require_once MODELS . 'nightly.php';

(new ReleaseInsights\Template(
    'nightly.html.twig',
    [
        'page_title'        => 'Nightly crashes for a day',
        'css_page_id'       => 'nightly',
        'display_date'      => $display_date,
        'nightly_pairs'     => $nightly_pairs,
        'build_crashes'     => $build_crashes,
        'top_sigs'          => $top_sigs,
        'bug_list'          => $bug_list,
        'bug_list_karma'    => $bug_list_karma,
        'previous_date'     => $previous_date,
        'requested_date'    => $requested_date,
        'next_date'         => $next_date,
        'today'             => $today,
        'known_top_crashes' => $known_top_crashes,
        'fallback_nightly'  => $fallback_nightly,
    ]
))->render();

