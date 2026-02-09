<?php

declare(strict_types=1);

namespace ReleaseInsights;

use DateTime;
use DateTimeZone;

class Release
{
    /** @var array<int> $no_planned_dot_releases */
    public array $no_planned_dot_releases = [108, 111, 115, 120];

    protected Version $version;

    public string $product_details;

    public function __construct(
        string $version
    )
    {
        $this->version = new Version($version);
        $this->product_details = URL::ProductDetails->value;
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
        // TODO: remove after 148 ships
        // @codeCoverageIgnoreStart
        if ($this->version->normalized === '148.0') {
            return [
                'version'               => '148.0',
                'qa_request_deadline'   => '2025-12-05 00:00:00+00:00',
                'a11y_request_deadline' => '2025-12-05 00:00:00+00:00',
                'nightly_start'         => '2025-12-08 00:00:00+00:00',
                'qa_feature_done'       => '2025-12-19 21:00:00+00:00',
                'qa_test_plan_due'      => '2025-12-19 21:00:00+00:00',
                'soft_code_freeze'      => '2026-01-08 21:00:00+00:00',
                'string_freeze'         => '2026-01-09 00:00:00+00:00',
                'qa_pre_merge_done'     => '2026-01-09 14:00:00+00:00',
                'merge_day'             => '2026-01-12 00:00:00+00:00',
                'beta_1_gtb'            => '2026-01-12 00:00:00+00:00',
                'beta_1'                => '2026-01-13 00:00:00+00:00',
                'beta_2'                => '2026-01-14 13:00:00+00:00',
                'beta_3'                => '2026-01-16 13:00:00+00:00',
                'sumo_1'                => '2026-01-16 21:00:00+00:00',
                'beta_4'                => '2026-01-19 13:00:00+00:00',
                'beta_5'                => '2026-01-21 13:00:00+00:00',
                'beta_6'                => '2026-01-23 13:00:00+00:00',
                'beta_7'                => '2026-01-26 13:00:00+00:00',
                'sumo_2'                => '2026-01-26 21:00:00+00:00',
                'beta_8'                => '2026-01-28 13:00:00+00:00',
                'beta_9'                => '2026-01-30 13:00:00+00:00',
                'beta_10'               => '2026-02-02 00:00:00+00:00',
                'beta_11'               => '2026-02-04 00:00:00+00:00',
                'qa_pre_rc_signoff'     => '2026-02-04 14:00:00+00:00',
                'beta_12'               => '2026-02-06 00:00:00+00:00',
                'beta_13'               => '2026-02-09 00:00:00+00:00',
                'beta_14'               => '2026-02-11 00:00:00+00:00',
                'beta_15'               => '2026-02-13 00:00:00+00:00',
                'rc_gtb'                => '2026-02-16 21:00:00+00:00',
                'rc'                    => '2026-02-17 00:00:00+00:00',
                'release'               => '2026-02-24 14:00:00+00:00',
                'mobile_dot_release'    => '2026-03-03 00:00:00+00:00',
                'planned_dot_release'   => '2026-03-10 00:00:00+00:00',
            ];
        }
        // @codeCoverageIgnoreEnd

        $all_releases = new Data($this->product_details)->getMajorReleases();
        if (! array_key_exists($this->version->normalized, $all_releases)) {
            return ['error' => 'Not enough data for this version number.'];
        }

        // Future release date object
        $release = new DateTime($all_releases[$this->version->normalized] . ' 06:00 PST');

        $nightly_target = Version::decrement($this->version->normalized, 2);

        // Major version replaced by a dot release, make sure we don't pass NULL to DateTime
        $all_releases['14.0'] = $all_releases['14.0.1'];
        $all_releases['125.0'] = $all_releases['125.0.1'];

        // Calculate 1st day of the nightly cycle
        $nightly = new DateTime($all_releases[$nightly_target]);
        $nightly->modify('-1 day');

        // Transform all the DateTime objects in the $schedule array into formated date strings
        $date = fn(string|object $day): string => is_object($day) ? $day->format('Y-m-d H:i:sP') : $nightly->modify($day)->format('Y-m-d H:i:sP');

        $schedule = [
            # Starting with Firefox 136, QA request deadline is before the start of the nightly cycle
            'qa_request_deadline'   => $date($nightly->modify('-3 days')),
            'a11y_request_deadline' => $date($nightly),
            'nightly_start'         => $date($nightly->modify('+3 days')),
            'qa_feature_done'       => match ($this->version->normalized) {
                '149.0' => $date('Friday +3 weeks 21:00'),
                '154.0' => $date('Friday +2 weeks 21:00'),
                default => $date('Friday +1 week 21:00'),
            },
            'qa_test_plan_due'      => $date('Friday 21:00'),
            'relnotes_beta_ready'   => $date($nightly->modify('+1 week')->modify('Wednesday')),
            'qa_pre_merge_done'     => $date('Friday 14:00'),
            'string_freeze'         => $date('Friday'),
            'merge_day'             => $date('Monday'),
            // 'beta_1_gtb'            => $date('Wednesday'),
            'beta_1'                => $date('Wednesday'),
            'beta_2'                => $date('Friday 13:00'),
            'sumo_1'                => $date('Friday 21:00'), // Friday of Beta week 1
            'beta_3'                => $date('Monday 13:00'),
            'beta_4'                => $date('Wednesday 13:00'),
            'beta_5'                => $date('Friday 13:00'),
            'beta_6'                => $date('Monday 13:00'),
            'sumo_2'                => $date('Monday 21:00'), // Monday of Beta Week 3
            'beta_7'                => $date('Wednesday 13:00'),
            'qa_pre_rc_signoff'     => $date('Wednesday 17:00'),
            'beta_8'                => match ($this->version->normalized) {
                '159.0' => $date($nightly->modify('+1 week')->modify('Monday 13:00')), // Jan 4, 2026
                default => $date('Friday 13:00'),
            },
            'beta_9' => match ($this->version->normalized) {
                '159.0' => $date('Wednesday 13:00'),
                default => $date('Monday 13:00'),
            },
            'beta_10' => match ($this->version->normalized) {
                '159.0' => $date('Friday 13:00'),
                default => $date('Wednesday 13:00'),
            },
            'rc_gtb' => match ($this->version->normalized) {
                '153.0' => $date($nightly->modify('+1 week')),
                default => $date('Wednesday 17:00'),
            },
            'rc'      => $date('Wednesday 20:00'),
            'release' => $date($release->setTimezone(new \DateTimeZone('UTC'))),
        ];

        // Add extra (Android) 144 betas for https://bugzilla.mozilla.org/1992436
        if ($this->version->normalized === '144.0') {
            $schedule += ['beta_10' => '2025-10-06 17:00:00+00:00']; // @codeCoverageIgnore
            $schedule += ['beta_11' => '2025-10-07 17:00:00+00:00']; // @codeCoverageIgnore
        }

        // Add extra betas for 153 and reset some dates
        if ($this->version->normalized === '153.0') {
            $schedule += ['beta_11' => '2026-07-10 00:00:00+00:00'];
            $schedule += ['beta_12' => '2026-07-13 00:00:00+00:00'];
            $schedule += ['beta_13' => '2026-07-15 00:00:00+00:00'];
        }

        // Add extra betas for 159
        if ($this->version->normalized === '159.0') {
            $schedule['qa_pre_rc_signoff'] = '2027-01-06 17:00:00+00:00';
            $schedule += ['beta_11' => '2027-01-11 00:00:00+00:00'];
            $schedule += ['beta_12' => '2027-01-13 00:00:00+00:00'];
        }

        // Add the release notes deadline after all the beta special cases
        $schedule += ['relnotes_deadline' => $date((clone $release)->modify('-7 days'))];

        // Add the Android weekly release before the planned dot release mid-cycle
        $schedule += ['mobile_dot_release' => $date($release->modify('+1 week 00:00'))];

        // Add the planned dot release mid-cycle
        if (! in_array($this->version->int, $this->no_planned_dot_releases)) {
            if ($this->version->normalized === '146.0') {
                $schedule += ['planned_dot_release' => $date($release->modify('December 18 00:00'))];
            } else {
                $schedule += ['planned_dot_release' => $date($release->modify('+1 week 00:00'))];
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
        $all_releases = $data->getMajorReleases();

        // Major version replaced by a dot release, make sure we don't pass NULL to DateTime
        $all_releases['14.0'] = $all_releases['14.0.1'];
        $all_releases['125.0'] = $all_releases['125.0.1'];
        $release = new DateTime($all_releases[$this->version->normalized] . ' 06:00 PST');
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

        $milestones = [
            'nightly_start'  => new DateTime(Nightly::cycleStart($this->version->int)),
        ];

        $count = 0;
        foreach ($betas as $k => $date) {
            $count++;
            $milestones['beta_' . (string) $count] = new DateTime($date);
        }

        $milestones += [
            'merge_day' => (clone $milestones['beta_1'])->sub(new \DateInterval('P1D')),
        ];

        $milestones += [
            'release' => $release->setTimezone(new \DateTimeZone('UTC')),
        ];

        $count = 0;
        foreach ($dot_releases as $date) {
            $count++;
            $milestones['dot_release_' . (string) $count] = new DateTime($date . ' 00:00:00');
        }

        // Add desktop/android planned dot release if we haven't shipped it yet
        $shipped_dot_releases = array_filter(
            $milestones,
            fn($key) => str_starts_with($key, 'dot_release'), ARRAY_FILTER_USE_KEY
        );

        // Add planned mobile dot release, useful only for the current release cycle (monthly calendar)
        // $milestones['mobile_dot_release'] = new DateTime($this->getFutureSchedule()['mobile_dot_release']);
        $mobile_dot_release = $this->getFutureSchedule()['mobile_dot_release'] ?? null;
        if (isset($mobile_dot_release) && ! in_array(new DateTime($mobile_dot_release), $shipped_dot_releases)) {
            $milestones['mobile_dot_release'] = new DateTime($mobile_dot_release);
        }

        $planned_dot_release = $this->getFutureSchedule()['planned_dot_release'] ?? null;

        // This is a temporary 147.0.3 hack. TODO: remove after we ship it
        if ($this->version->normalized === '147.0') {
            $planned_dot_release = '2026-02-04'; // @codeCoverageIgnore
            $milestones['planned_dot_release'] = new DateTime($planned_dot_release); // @codeCoverageIgnore
        }

        if (isset($planned_dot_release) && ! in_array(new DateTime($planned_dot_release), $shipped_dot_releases)) {
            $milestones['planned_dot_release'] = new DateTime($planned_dot_release);
        }

        return $this->normalize($milestones);
    }

    /**
     * Get text labels that correspon to all our milestones fopr calendar/iCalendar use
     *
     * @return array<string, string>
     */
    public static function getLabels(string $version, bool $short = true): array
    {
        $short_version = (string) (int) $version;

        return [
            'qa_request_deadline'   => $short_version . ' QA request deadline',
            'a11y_request_deadline' => $short_version . ' a11y engineering request deadline for Nightly',
            'nightly_start'         => 'Nightly ' . $short_version . ' starts',
            'qa_feature_done'       => $short_version .' build ready for QA', #️⃣ AKA Feature Complete Milestone
            'qa_feature_done_1'     => $short_version .' build ready for nightly QA',
            'qa_feature_done_2'     => $short_version .' build ready for beta QA',
            'soft_code_freeze'      => ($short ? '' : 'Firefox ') . $short_version . ' soft Code Freeze starts at 08:00 UTC', # TODO: remove after 148 ships
            'relnotes_beta_ready'   => $short_version .' beta release notes ready',
            'qa_pre_merge_done'     => $short_version . ' regression testing completed',
            'qa_test_plan_due'      => 'Final deadline for QA’s Feature Test Plan approval',
            'string_freeze'         => $short_version . ' string freeze starts',
            'merge_day'             => 'Merge day',
            'beta_1_gtb'            => ($short ? '' : 'Firefox ') . $short_version . ' b1 GTB', # TODO: remove after 148 ships
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
            'relnotes_deadline'     => $short_version . ' release notes deadline',
            'rc_gtb'                => ($short ? '' : 'Firefox ') . $short_version . ' go to Build',
            'rc'                    => ($short ? '' : 'Firefox ') . 'RC',
            'release'               => ($short ? '<b>' : 'Firefox ') . $short_version . ($short ? ' Release</b>' : ' go-live @ 6AM PT'),
            'mobile_dot_release'    => ($short ? 'Potential Android ' : 'Potential Android ') . $version . ($short ? '.x' : ' dot release'),
            'planned_dot_release'   => ($short ? 'Planned ' : 'Planned Firefox ') . $version . ($short ? '.x' : ' dot release'),
        ];
    }

    public static function getNiceLabel(string $version, string $label, bool $short = true): string
    {
        return self::getLabels($version, $short)[$label];
    }

    /**
     * Normalize milestones with the same date format,
     * sort by date and return an array of dates for the release.
     *
     * @param array<mixed> $milestones
     *
     * @return array<string, string>
     */
    protected function normalize(array $milestones): array
    {
        // Convert all date objects to a date string
        $milestones = array_map(
            fn($date) => is_string($date) ? $date : $date->format('Y-m-d H:i:sP'),
            $milestones
        );

        // Sort the schedule by date
        asort($milestones);

        // The schedule starts with the release version number
        return ['version' => $this->version->normalized] + $milestones;
    }
}
