<?php

declare(strict_types=1);

use benhall14\phpCalendar\Calendar;
use ReleaseInsights\{CalendarMonthly, Model, Template};

$events_by_month = new Model('calendar_monthly')->get();

// One calendar per month, each with only the events of that month
$calendars = [];
foreach (CalendarMonthly::getMonthsToLastPlannedRelease() as $month) {
    $calendars[$month] = new Calendar()->addEvents($events_by_month[substr($month, 0, 7)] ?? []);
}

new Template(
    'calendar_monthly.html.twig',
    [
        'page_title'      => 'General calendar of upcoming Firefox release milestones',
        'css_page_id'     => 'calendar_monthly',
        'calendars'       => $calendars,
    ]
)->render();
