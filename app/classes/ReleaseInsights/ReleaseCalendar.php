<?php

declare(strict_types=1);

namespace ReleaseInsights;

use DateTime;
use Eluceo\iCal\Component\{Calendar, Event};

class ReleaseCalendar
{
    /**
     *  @param array<string, string> $milestones
     *  @param array<string, string> $release_schedule_labels
     */
    public static function getICS(array $milestones, array $release_schedule_labels, string $calendar_name): string
    {
        $calendar = new Calendar($calendar_name);

        foreach ($milestones as $label => $date) {
            if ($label === 'version' || $label === 'rc') {
                continue;
            }

            $event = new Event();
            $start = new DateTime($date);
            $end   = new DateTime($date);

            if ($label === 'soft_code_freeze') {
                $end->modify('Sunday');
            }

            $event
                ->setDtStart($start)
                ->setDtEnd($end)
                ->setNoTime(true)
                ->setSummary($release_schedule_labels[$label]);

            $calendar->addComponent($event);
        }

        return $calendar->render();
    }
}
