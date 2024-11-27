<?php

declare(strict_types=1);

use ReleaseInsights\{CalendarMonthly, Model, Template};

$data = new Model('calendar_monthly')->get();

new Template(
    'calendar_monthly.html.twig',
    [
        'page_title'      => 'General calendar of upcoming Firefox release milestones',
        'css_page_id'     => 'calendar_monthly',
        'upcoming_months' => CalendarMonthly::getMonthsToLastPlannedRelease(),
        'calendar'        => $data,
    ]
)->render();


