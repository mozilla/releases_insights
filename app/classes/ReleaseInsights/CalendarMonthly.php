<?php

declare(strict_types=1);

namespace ReleaseInsights;

use DateInterval, DatePeriod, DateTime;

class CalendarMonthly {

    /**
     * Utility function to get a list of months between now
     * and our next planned release.
     * This is used for the monthly calendar view.
     * @return array<string>
     */
    public static function getMonthsToLastPlannedRelease(): array
    {
        $start = (new DateTime('now'))->modify('first day of this month');

        /* The end date is the last planned major release */
        $end = array_key_last((new Data())->getFutureReleases());
        $end = (new Data())->getFutureReleases()[$end];
        $end = (new DateTime($end))->modify('first day of next month');
        $period = new DatePeriod(
            $start,
            DateInterval::createFromDateString('1 month'),
            $end
        );

        $upcoming_months = [];
        foreach ($period as $month) {
            $upcoming_months[] = $month->format("Y-m-d");
        }
        return $upcoming_months;
    }
}
