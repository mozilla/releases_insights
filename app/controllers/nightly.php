<?php

declare(strict_types=1);

use ReleaseInsights\{Model, Template};

[
    $display_date,
    $nightly_pairs,
    $build_crashes,
    $top_sigs,
    $crash_bugs,
    $bug_list,
    $bug_list_karma,
    $outstanding_bugs,
    $previous_date,
    $requested_date,
    $next_date,
    $today,
    $known_top_crashes,
    $fallback_nightly,
    $warning,
    $latest_nightly,
] = (new Model('nightly'))->get();

(new Template(
    'nightly.html.twig',
    [
        'page_title'        => 'Nightly builds (crashes and bug fixes)',
        'css_page_id'       => 'nightly',
        'display_date'      => $display_date,
        'nightly_pairs'     => $nightly_pairs,
        'build_crashes'     => $build_crashes,
        'top_sigs'          => $top_sigs,
        'crash_bugs'        => $crash_bugs,
        'outstanding_bugs'  => $outstanding_bugs,
        'bug_list'          => $bug_list,
        'bug_list_karma'    => $bug_list_karma,
        'previous_date'     => $previous_date,
        'requested_date'    => $requested_date,
        'next_date'         => $next_date,
        'today'             => $today,
        'known_top_crashes' => $known_top_crashes,
        'fallback_nightly'  => $fallback_nightly,
        'warning_message'   => $warning,
        'latest_nightly'    => $latest_nightly,
    ]
))->render();
