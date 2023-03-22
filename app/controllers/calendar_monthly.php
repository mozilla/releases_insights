<?php

declare(strict_types=1);

$calendar = require_once MODELS . 'calendar_monthly.php';

(new ReleaseInsights\Template(
    'calendar_monthly.html.twig',
    [
        'page_title'        => 'General calendar of upcoming Firefox release milestones',
        'css_page_id'       => 'calendar_monthly',
        'upcoming_releases' => (new ReleaseInsights\Data)->getFutureReleases(),
        'calendar'          => $calendar,
    ]
))->render();
