<?php

declare(strict_types=1);

use ReleaseInsights\{Model, Template};

/* Put the page down until we have synced tags between git and hg */
http_response_code(503);
new Template(
    'maintenance.html.twig',
    [
        'page_title'   => 'Betas this cycle',
        'page_content' => 'This page is currently not available (hg to git migration in progress).',
    ]
)->render();

[$beta, $bug_details, $uplift_counter, $stats,
$known_top_crashes, $crash_bugs, $bugzilla_links] = new Model('beta')->get();

new Template(
    'beta.html.twig',
    [
        'page_title'        => 'Betas this cycle',
        'css_page_id'       => 'beta',
        'beta'              => $beta,
        'bug_list'          => $bug_details,
        'uplift_count'      => $uplift_counter,
        'stats'             => $stats,
        'known_top_crashes' => $known_top_crashes,
        'crash_bugs'        => $crash_bugs,
        'bugs_link'         => $bugzilla_links,
    ]
)->render();

