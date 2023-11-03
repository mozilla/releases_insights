<?php

declare(strict_types=1);

namespace ReleaseInsights;

use DateInterval, DatePeriod, DateTime;

class Duration
{
    public function __construct(public readonly Datetime $start, public readonly Datetime $end)
    {
    }

    /**
     * Get The number of days between 2 dates
     */
    public function days(): int
    {
        return $this->start->diff($this->end)->days;
    }

    /**
     * Get The number of weeks between 2 dates
     * We round down to 0.5
     */
    public function weeks(): float
    {
        // We are limiting to 1 decimal for the week
        $weeks = number_format($this->days()/7, 1);

        // We want some logic to round down only the decimal to 0.5
        [$main, $minor] = explode('.', (string) $weeks);
        $minor =  $minor >= 5 ? 5 : 0;

        return (float) ($main . '.' . $minor);
    }

    /**
     * Check if a day is a workday
     */
    public function isWorkDay(DateTime $day)
    {
        return ! in_array($day->format('l'), ['Saturday','Sunday']);
    }

    /**
     * Get The number of working days between 2 dates
     */
    public function workDays(): int
    {
        $count = 0;

        // P1D is short for 'Period: 1 Day'
        $range = new DatePeriod(start: $this->start, end: $this->end, interval: new DateInterval('P1D'));

        foreach($range as $date){
            if ($this->isWorkDay($date)) {
                $count++;
            }
        }

        // We substract 1 because we don't count the first day of the period
        return $count - 1;
    }

    /**
     * Return all the data in an array for template use
     */
    public function report(): array
    {
        return [
            'days'     => $this->days(),
            'workdays' => $this->workDays(),
            'weeks'    => $this->weeks(),
        ];
    }
}