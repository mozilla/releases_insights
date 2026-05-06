<?php

declare(strict_types=1);

use ReleaseInsights\{Model, Template};

[$release_uplifts, $bug_details, $uplift_counter, $stats,
$known_top_crashes, $crash_bugs, $bugzilla_links, $chemspill_releases] = new Model('release_uplifts')->get();

new Template(
    'release_uplifts.html.twig',
    [
        'page_title'        => $release_uplifts->release === RELEASE ? 'Release uplifts this cycle' : 'Firefox ' . $release_uplifts->release . ' release uplifts',
        'css_page_id'       => 'release_uplifts',
        'release_uplifts'   => $release_uplifts,
        'bug_list'          => $bug_details,
        'uplift_count'      => $uplift_counter,
        'stats'             => $stats,
        'known_top_crashes' => $known_top_crashes,
        'crash_bugs'        => $crash_bugs,
        'bugs_link'          => $bugzilla_links,
        'chemspill_releases' => $chemspill_releases,
    ]
)->render();
