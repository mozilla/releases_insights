<?php

declare(strict_types=1);

use ReleaseInsights\{Model, Template};

[$beta, $bug_details, $uplift_counter, $stats,
$known_top_crashes, $crash_bugs, $bugzilla_links] = new Model('beta')->get();

new Template(
    'beta.html.twig',
    [
        'page_title'        => $beta->release === BETA ? 'Betas this cycle' : 'Firefox ' . $beta->release . ' beta',
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

