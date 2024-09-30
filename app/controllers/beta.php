<?php

declare(strict_types=1);

use ReleaseInsights\{Model, Template};

[$data, $bug_details, $uplift_counter, $stats, $crashes, $known_top_crashes] = (new Model('beta'))->get();

(new Template(
    'beta.html.twig',
    [
        'page_title'        => 'Betas this cycle',
        'css_page_id'       => 'beta',
        'betas_data'        => $data,
        'bug_list'          => $bug_details,
        'uplift_count'      => $uplift_counter,
        'stats'             => $stats,
        'crashes'           => $crashes,
        'known_top_crashes' => $known_top_crashes,
    ]
))->render();
