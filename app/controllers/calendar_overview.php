<?php

declare(strict_types=1);

$calendar = require_once MODELS . 'calendar_overview.php';

(new ReleaseInsights\Template(
    'calendar_overview.html.twig',
    [
        'page_title'        => 'General calendar of upcoming Firefox release milestones',
        'css_page_id'       => 'calendar_overview',
        'upcoming_releases' => (new ReleaseInsights\Data)->getFutureReleases(),
    ]
))->render();
