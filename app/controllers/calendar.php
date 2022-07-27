<?php

declare(strict_types=1);

$calendar = require_once MODELS . 'calendar.php';

(new ReleaseInsights\Template(
    'calendar.html.twig',
    [
        'page_title'        => 'General calendar of upcoming Firefox release milestones',
        'css_page_id'       => 'calendar',
        'upcoming_releases' => (new ReleaseInsights\Data)->getFutureReleases(),
        'calendar'          => $calendar,
    ]
))->render();
