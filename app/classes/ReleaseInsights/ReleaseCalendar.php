<?php

declare(strict_types=1);

namespace ReleaseInsights;

use DateTime;
use Eluceo\iCal\Component\{Calendar, Event};

class ReleaseCalendar
{
    public const string PRODID = '-//Mozilla//whattrainisitnow.com//EN';

    /**
     * Event UIDs are built from $uid_prefix and the milestone so that they stay the
     * same across downloads: calendar clients use them to update subscribed events.
     *
     *  @param array<string, string> $milestones
     *  @param array<string, string> $release_schedule_labels
     */
    public static function getICS(array $milestones, array $release_schedule_labels, string $calendar_name, string $uid_prefix): string
    {
        $calendar = new Calendar(self::PRODID);
        $calendar
            ->setName($calendar_name)
            // Hint for calendar clients on how often to refresh a subscription
            ->setPublishedTTL('PT6H');

        foreach ($milestones as $label => $date) {
            if ($label === 'version' || $label === 'rc') {
                continue;
            }

            $event = new Event();
            $start = new DateTime($date);
            $end   = new DateTime($date);

            // This is used only for the Firefox Future Major Versions API
            if (preg_match('/^\d+\.\d+$/', $label)) {
                $release_schedule_labels[$label] = 'Firefox ' . Version::getMajor($label) . ' go-live @ 06:00 AM PT';
            }

            $event
                ->setUniqueId($uid_prefix . '-' . $label . '@whattrainisitnow.com')
                ->setDtStart($start)
                ->setDtEnd($end)
                ->setNoTime(true)
                ->setSummary($release_schedule_labels[$label]);

            $calendar->addComponent($event);
        }

        return $calendar->render();
    }
}
