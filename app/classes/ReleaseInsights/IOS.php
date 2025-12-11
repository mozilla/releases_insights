<?php

declare(strict_types=1);

namespace ReleaseInsights;

use DateTime;

class IOS extends Release
{
    public function __construct(
        string $version
    )
    {
        $this->version = new Version($version);
    }

    /**
     * Get The schedule for a release
     *
     * @return array<string, string>
     */
    public function getSchedule(): array
    {
        $version = $this->version->int;
        return match (true) {
            $version < 145  => ['error' => 'iOS schedule not supported before version 145.'],
            $version < BETA => $this->getPastSchedule(),
            default         => $this->getFutureSchedule(),
        };
    }

    /**
     * Get The schedule for a future release
     *
     * @return array<string, string>
     */
    public function getFutureSchedule(): array
    {
        $v = $this->version->normalized;
        $desktop_release = $this->getDesktopReleases()[$v] ?? null;

        if (! $desktop_release) {
            return ['error' => 'Not enough data for this version number.'];
        }

        // Future release date object
        $cycle_start = new DateTime($desktop_release . ' 02:00 UTC')->modify('-11 days');

       // Transform all the DateTime objects in the $schedule array into formated date strings
        $date = fn(string $day): string => $cycle_start->modify($day)->format('Y-m-d H:i:sP');

        $milestones = [
            'merge_day_0' => match ($v) {
                '147.0' => $date('2025-12-29'),
                default => $date('now'),
            },
            'rc_gtb_0'         => $date('+4 hours'),
            'qa_pre_signoff_0' => $date('Monday 17:00 UTC'),
            'qa_signoff_0'     => $date('Tuesday'),
            'appstore_sent_0'  => match ($v) {
                '147.0' => $date('2026-01-08'),
                default => $date('now'),
            },
            'merge_day_1'      => $date('Friday'),
            'rc_gtb_1'         => $date('+4 hours'),
            'release_0'        => $date('Monday 02:00 UTC'),
            'qa_pre_signoff_1' => $date('Monday 17:00 UTC'),
            'qa_signoff_1'     => $date('Tuesday'),
            'appstore_sent_1'  => $date('Thursday'),
            'merge_day_2'      => $date('Friday'),
            'rc_gtb_2'         => $date('+4 hours'),
            'release_1'        => $date('Monday 02:00 UTC'),
            'qa_pre_signoff_2' => $date('Monday 17:00 UTC'),
            'qa_signoff_2'     => $date('Tuesday'),
            'appstore_sent_2'  => $date('Thursday'),
            'merge_day_3'      => $date('Friday'),
            'rc_gtb_3'         => $date('+4 hours'),
            'release_2'        => $date('Monday 02:00 UTC'),
            'qa_pre_signoff_3' => $date('Monday 17:00 UTC'),
            'qa_signoff_3'     => $date('Tuesday'),
            'appstore_sent_3'  => $date('Thursday'),
            'release_3'        => $date('Monday 02:00 UTC'),
        ];

        // Temporary fixups
        if ($v == '146.0') {
            // We cancel Dot releases 2 and 3
            foreach (array_keys($milestones) as $value) {
                if (str_ends_with($value, '_2') || str_ends_with($value, '_3') ) {
                    unset($milestones[$value]);
                }
            }
        }

        // Extra dot releases only for 147
        if ($v === '147.0') {
            $milestones += [
                'merge_day_4'      => $date('2026-01-30'),
                'rc_gtb_4'         => $date('+4 hours'),
                'release_1'        => $date('Monday 02:00 UTC'),
                'qa_pre_signoff_4' => $date('Monday 17:00 UTC'),
                'qa_signoff_4'     => $date('Tuesday'),
                'appstore_sent_4'  => $date('Thursday'),
                'merge_day_5'      => $date('Friday'),
                'rc_gtb_5'         => $date('+4 hours'),
                'release_4'        => $date('Monday 02:00 UTC'),
                'qa_pre_signoff_5' => $date('Monday 17:00 UTC'),
                'qa_signoff_5'     => $date('Tuesday'),
                'appstore_sent_5'  => $date('Thursday'),
                'release_5'        => $date('Monday 02:00 UTC'),
            ];
        }

        if ($v == '148.0') {
            // Wellness day on March 6
            $milestones['merge_day_3'] = '2026-03-05 00:00:00+00:00';
            $milestones['rc_gtb_3'] = '2026-03-05 00:04:00+00:00';
        }


        return $this->normalize($milestones);
    }

    /**
     * Get The schedule for a past release
     *
     * @return array<string, string>
     */
    public function getPastSchedule() : array
    {
        $v = $this->version->normalized;
        $desktop_release = $this->getDesktopReleases()[$v];
        $ios_release = new DateTime($desktop_release . ' 02:00 UTC')->modify('-1 day');

        $milestones = [
            'release' => $ios_release,
            'dot_release_1' => (clone $ios_release)->modify('+7 days'),
            'dot_release_2' => (clone $ios_release)->modify('+14 days'),
            'dot_release_3' => (clone $ios_release)->modify('+21 days'),
        ];

        //  Longterm adjustments because we don't have the data in product-details
        if ($v == '146.0') {
            unset($milestones['dot_release_2'], $milestones['dot_release_3']);
        }

        if ($v === '147.0') {
            $milestones += [
                'dot_release_4' => (clone $ios_release)->modify('+28 days'),
                'dot_release_5' => (clone $ios_release)->modify('+35 days'),
            ];
        }

        return $this->normalize($milestones);
    }

    /**
     * Utility function to get the list of all dates of Desktop Major Releases
     *
     * @return array<string, string>
     */
    public function getDesktopReleases(): array
    {
        return new Data(URL::ProductDetails->value)->getMajorReleases();
    }
}
