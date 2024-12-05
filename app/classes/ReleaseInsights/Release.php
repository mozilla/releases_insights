<?php

declare(strict_types=1);

namespace ReleaseInsights;

use DateTime;

class Release
{
    /** @var array<int> $no_planned_dot_releases */
    public array $no_planned_dot_releases = [108, 111, 115, 120];

    private readonly Version $version;

    public function __construct(
        string $version,
        public readonly string $product_details = URL::ProductDetails->value,
    )
    {
        $this->version = new Version($version);
    }

    /**
     * Get The schedule for a future Release
     *
     * @return array<string, string>
     */
    public function getSchedule(): array
    {
        if ($this->version->int < 1) {
            return ['error' => 'Invalid version number.'];
        }

        if ($this->version->int < BETA) {
            return $this->getPastSchedule();
        }
        return $this->getFutureSchedule();
    }


    /**
     * Get The schedule for a future Release
     *
     * @return array<string, string>
     */
    public function getFutureSchedule(): array
    {
        $all_releases = new Data($this->product_details)->getMajorReleases();
        if (! array_key_exists($this->version->normalized, $all_releases)) {
            return ['error' => 'Not enough data for this version number.'];
        }

        // Future release date object
        $release = new DateTime($all_releases[$this->version->normalized] . ' 06:00 PST');

        $nightly_target = Version::decrement($this->version->normalized, 2);

        // Calculate 1st day of the nightly cycle
        $nightly = new DateTime($all_releases[$nightly_target]);
        $nightly->modify('-1 day');

        // Transform all the DateTime objects in the $schedule array into formated date strings
        $date = fn(string|object $day): string => is_object($day) ? $day->format('Y-m-d H:i:sP') : $nightly->modify($day)->format('Y-m-d H:i:sP');

        #️⃣ TODO: remove this conditional once 135.0 has shipped
        if ($this->version->int < 136) {
            $schedule = [
                'nightly_start'         => $date($nightly),
                'a11y_request_deadline' => $date('Friday'),
                'qa_request_deadline'   => match ($this->version->normalized) {
                    '133.0' => $date('yesterday'),
                    default => $date('Friday'),
                },
                'qa_feature_done_1'     => match ($this->version->normalized) {
                    '142.0' => $date('July 3 21:00'),
                    default => $date('Friday +1 week 21:00'),
                },
                'qa_feature_done_2'  => match ($this->version->normalized) {
                    '135.0'          => $date($nightly->modify('+3 weeks')->modify('Thursday 08:00')),
                    '141.0', '142.0' => $date($nightly->modify('+1 week')->modify('Wednesday 08:00')),
                    default          => $date($nightly->modify('+1 week')->modify('Thursday 08:00')),
                },
                'soft_code_freeze'    => $date('Thursday 08:00'),
                'qa_pre_merge_done'   => $date('Friday 14:00'),
                'qa_test_plan_due'    => $date('Friday'),
                'string_freeze'       => $date('Friday'),
                'merge_day'           => $date('Monday'),
                'beta_1'              => $date('Monday'),
                'beta_2'              => $date('Wednesday 13:00'),
                'beta_3'              => $date('Friday 13:00'),
                'sumo_1'              => $date('Friday 21:00'), // Friday of Beta week 1
                'beta_4'              => $date('Monday 13:00'),
                'beta_5'              => $date('Wednesday 13:00'),
                'beta_6'              => $date('Friday 13:00'),
                'beta_7'              => $date('Monday 13:00'),
                'sumo_2'              => $date('Monday 21:00'), // Monday of Beta Week 3
                'beta_8'              => $date('Wednesday 13:00'),
                'qa_pre_rc_signoff'   => $date('Wednesday 14:00'),
                'beta_9'              => $date('Friday 13:00'),
                'rc_gtb'              => match ($this->version->normalized) {
                    '147.0' => $date($nightly->modify('+1 week')->modify('Monday 21:00')),
                    default => $date('Monday 21:00'),
                },
                'rc'                  => $date('Tuesday'),
                'release'             => $date($release->setTimezone(new \DateTimeZone('UTC'))),
            ];
        } else {
            $schedule = [
                #️⃣ Starting with Firefox 136, QA request deadline is before the start of the nightly cycle
                'qa_request_deadline'   => $date($nightly->modify('-3 days')),
                'nightly_start'         => $date($nightly->modify('+3 days')),
                'a11y_request_deadline' => $date('Friday'),
                'qa_feature_done'     => $date('Friday +1 week 21:00'),
                'qa_test_plan_due'    => $date('Friday 21:00'),
                'soft_code_freeze'    => $date($nightly->modify('+1 week')->modify('Thursday 08:00')),
                'qa_pre_merge_done'   => $date('Friday 14:00'),
                'string_freeze'       => $date('Friday'),
                'merge_day'           => $date('Monday'),
                'beta_1'              => $date('Monday'),
                'beta_2'              => $date('Wednesday 13:00'),
                'beta_3'              => $date('Friday 13:00'),
                'sumo_1'              => $date('Friday 21:00'), // Friday of Beta week 1
                'beta_4'              => $date('Monday 13:00'),
                'beta_5'              => $date('Wednesday 13:00'),
                'beta_6'              => $date('Friday 13:00'),
                'beta_7'              => $date('Monday 13:00'),
                'sumo_2'              => $date('Monday 21:00'), // Monday of Beta Week 3
                'beta_8'              => $date('Wednesday 13:00'),
                'qa_pre_rc_signoff'   => $date('Wednesday 14:00'),
                'beta_9'              => $date('Friday 13:00'),
                'rc_gtb'              => match ($this->version->normalized) {
                    '147.0' => $date($nightly->modify('+1 week')->modify('Monday 21:00')),
                    default => $date('Monday 21:00'),
                },
                'rc'                  => $date('Tuesday'),
                'release'             => $date($release->setTimezone(new \DateTimeZone('UTC'))),
            ];
        }

        if (! in_array($this->version->int, $this->no_planned_dot_releases)) {
            if ($this->version->normalized === '146.0') {
                $schedule += ['planned_dot_release' => $date($release->modify('December 18 00:00'))];
            } else {
                $schedule += ['planned_dot_release' => $date($release->modify('+2 weeks 00:00'))];
            }
       }

        // Sort the schedule by date, needed for schedules with a fixup
        asort($schedule);

        // Dev mode: assert that qa_pre_rc_signoff happens before the last beta
        // If not, this causes a 1 week shift in rc_gtb calculation
        $post_qa_step = array_search('qa_pre_rc_signoff', array_keys($schedule)) + 1;

        assert(
            str_starts_with(
                array_keys($schedule)[$post_qa_step],
                'beta'
            ) === true
        );

        // The schedule starts with the release version number
        return ['version' => $this->version->normalized] + $schedule;
    }

    /**
     * Get The schedule for a past Release
     *
     * @return array<string, string>
     */
    public function getPastSchedule() : array
    {
        $data = new Data($this->product_details);
        $releases = $data->getMajorReleases();

        $release = new DateTime($releases[$this->version->normalized] . ' 06:00 PST');
        $betas = $data->getPastBetas();
        $betas = array_filter(
            $betas,
            fn($k) => str_starts_with($k, $this->version->normalized),
            ARRAY_FILTER_USE_KEY
        );

        $dot_releases = $data->getDesktopPastReleases();
        $dot_releases = array_filter(
            $dot_releases,
            fn($k) => $k != $this->version->normalized && str_starts_with($k, $this->version->normalized),
            ARRAY_FILTER_USE_KEY
        );

        // Transform all the DateTime objects in the $schedule array into formated date strings
        $format_date = fn(string $day): string => new DateTime($day)->format('Y-m-d H:i:sP');

        $schedule = [
            'nightly_start'  => $format_date(Nightly::cycleStart($this->version->int)),
        ];

        $count = 0;
        foreach ($betas as $k => $date) {
            $count++;
            $schedule['beta_' . (string) $count] = $format_date($date);
        }

        $schedule += [
            'release' => $release->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:sP'),
        ];

        $count = 0;
        foreach ($dot_releases as $k => $date) {
            $count++;
            $schedule['dot_release_' . (string) $count] = $format_date($date);
        }

        // The schedule starts with the release version number
        return ['version' => $this->version->normalized] + $schedule;
    }

    public static function getNiceLabel(string $version, string $label, bool $short=true): string
    {
        $short_version = (string) (int) $version;

        $labels = [
            'qa_request_deadline'   => $short_version . ' QA request deadline',
            'nightly_start'         => 'Nightly ' . $short_version . ' starts',
            'a11y_request_deadline' => $short_version . ' a11y engineering request deadline for Nightly',
            'qa_feature_done'       => $short_version .' build ready for QA', #️⃣ AKA Feature Complete Milestone
            'qa_feature_done_1'     => $short_version .' build ready for nightly QA',
            'qa_feature_done_2'     => $short_version .' build ready for beta QA',
            'soft_code_freeze'      => ($short ? '' : 'Firefox ') . $short_version . ' soft Code Freeze starts at 08:00 UTC',
            'qa_pre_merge_done'     => $short_version . ' regression testing completed',
            'qa_test_plan_due'      => 'Final deadline for QA’s Feature Test Plan approval',
            'string_freeze'         => 'String Freeze' . ($short ? '' : ' starts'),
            'merge_day'             => 'Merge day',
            'beta_1'                => ($short ? '' : 'Firefox ') . $short_version . ' b1 GTB',
            'beta_2'                => ($short ? '' : 'Firefox ') . $short_version . ' b2' . ($short ? ' GTB' : ' (GTB: 13:00 UTC)'),
            'beta_3'                => ($short ? '' : 'Firefox ') . $short_version . ' b3' . ($short ? ' GTB' : ' (GTB: 13:00 UTC)'),
            'sumo_1'                => 'SUMO deadline',
            'beta_4'                => ($short ? '' : 'Firefox ') . $short_version . ' b4' . ($short ? ' GTB' : ' (GTB: 13:00 UTC)'),
            'beta_5'                => ($short ? '' : 'Firefox ') . $short_version . ' b5' . ($short ? ' GTB' : ' (GTB: 13:00 UTC)'),
            'beta_6'                => ($short ? '' : 'Firefox ') . $short_version . ' b6' . ($short ? ' GTB' : ' (GTB: 13:00 UTC)'),
            'beta_7'                => ($short ? '' : 'Firefox ') . $short_version . ' b7' . ($short ? ' GTB' : ' (GTB: 13:00 UTC)'),
            'sumo_2'                => 'SUMO content localization starts',
            'beta_8'                => ($short ? '' : 'Firefox ') . $short_version . ' b8' . ($short ? ' GTB' : ' (GTB: 13:00 UTC)'),
            'qa_pre_rc_signoff'     => $short_version . ' QA sign off',
            'beta_9'                => ($short ? '' : 'Firefox ') . $short_version . ' b9' . ($short ? ' GTB' : ' (GTB: 13:00 UTC)'),
            'beta_10'               => ($short ? '' : 'Firefox ') . $short_version . ' b10' . ($short ? ' GTB' : ' (GTB: 13:00 UTC)'),
            'beta_11'               => ($short ? '' : 'Firefox ') . $short_version . ' b11' . ($short ? ' GTB' : ' (GTB: 13:00 UTC)'),
            'beta_12'               => ($short ? '' : 'Firefox ') . $short_version . ' b12' . ($short ? ' GTB' : ' (GTB: 13:00 UTC)'),
            'beta_13'               => ($short ? '' : 'Firefox ') . $short_version . ' b13' . ($short ? ' GTB' : ' (GTB: 13:00 UTC)'),
            'beta_14'               => ($short ? '' : 'Firefox ') . $short_version . ' b14' . ($short ? ' GTB' : ' (GTB: 13:00 UTC)'),
            'beta_15'               => ($short ? '' : 'Firefox ') . $short_version . ' b15' . ($short ? ' GTB' : ' (GTB: 13:00 UTC)'),
            'rc_gtb'                => ($short ? '' : 'Firefox ') . $short_version . ' go to Build',
            'rc'                    => ($short ? '' : 'Firefox ') . 'RC',
            'release'               => ($short ? '' : 'Firefox ') . $short_version . ($short ? ' Release' : ' go-live @ 6AM PT'),
            'planned_dot_release'   => ($short ? 'Planned ' : 'Planned Firefox ') . $version . ($short ? '.x' : ' dot release'),
        ];

        return $labels[$label];
    }

    /**
     * Get The list of existing milestones per sub-cycle
     *
     * @return array<string, array<string>>
     */
    public static function getMilestonesNames(): array
    {
        $nightly_milestones = [
            'nightly_start',
            'a11_request_deadline',
            'qa_request_deadline',
            'qa_feature_done_1',
            'qa_feature_done_2',
            'soft_code_freeze',
            'qa_pre_merge_done',
            'qa_test_plan_due',
            'string_freeze',
        ];

        $beta_milestones = [
            'merge_day',
            'beta_1',
            'beta_2',
            'beta_3',
            'sumo_1',
            'beta_4',
            'beta_5',
            'beta_6',
            'beta_7',
            'sumo_2',
            'beta_8',
            'qa_pre_rc_signoff',
            'beta_9',
            'beta_10',
            'beta_11',
            'beta_12',
            'beta_13',
            'beta_14',
            'beta_15',
        ];

        $release_milestones = [
            'rc_gtb',
            'rc',
            'release',
            'planned_dot_release',
        ];

        return [
            'nightly' => $nightly_milestones,
            'beta'    => $beta_milestones,
            'release' => $release_milestones,
        ];
    }
}
