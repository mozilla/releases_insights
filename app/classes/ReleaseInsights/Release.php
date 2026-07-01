<?php

declare(strict_types=1);

namespace ReleaseInsights;

use DateInterval;
use DateTime;
use DateTimeZone;

class Release
{
    public readonly Version $version;

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
        // Starting with Firefox 155, we move to a 2-week release cycle.
        // The logic below is kept only for versions <155 and will be
        // removed once all those releases are shipped.
        if ($this->version->int >= 155) {
            return $this->getTwoWeekSchedule();
        }

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
            'strings_handoff'       => $date($nightly),
            'qa_pre_merge_done'     => $date('Friday 14:00'),
            'string_freeze'         => $date('Friday'),
            'merge_day'             => $date('Monday'),
            'beta_1'                => $date('Wednesday'),
            'beta_2'                => $date('Friday 13:00'),
            'sumo_1'                => $date('Friday 21:00'), // Friday of Beta week 1
            'beta_3'                => $date('Monday 13:00'),
            'beta_4'                => $date('Wednesday 13:00'),
            'beta_5'                => $date('Friday 13:00'),
            'beta_6'                => $date('Monday 13:00'),
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

        // Add  planned dot releases
        $schedule += [
            'dot_release_1' => $date($release->modify('+1 week 00:00')),
            'dot_release_2' => $date($release->modify('+1 week 00:00')),
            'dot_release_3' => $date($release->modify('+1 week 00:00')),
        ];

        // 152 has an extra dot release because of the long cycle
        // Ignoring in code coverage for now
        if ($this->version->normalized === '152.0') {
            $schedule['dot_release_4'] = $date($release->modify('+1 week 00:00')); // @codeCoverageIgnore
        }

        // 150 will have 2 planned dot releases, we start 3 planned dot releases with 151
        // Also, the second dot release is on May 7
        if ($this->version->normalized === '150.0') {
            $schedule['dot_release_2'] = '2026-05-07 15:00:00+00:00';
        }

        // Don't ship dot releases between Christmas and New year
        if ($this->version->normalized === '158.0') {
            $schedule['dot_release_3'] = '2027-01-05 15:00:00+00:00';
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
     * Get the schedule for a future release on the 2-week release cycle.
     *
     * Used from Firefox 155 onwards. Every milestone is anchored on the
     * release day (always a Tuesday) and expressed as a number of days
     * after the first day of the Nightly cycle (a Friday, 32 days before
     * release day). See milestones2weeksCycle.csv for the source of truth.
     *
     * @return array<string, string>
     */
    public function getTwoWeekSchedule(): array
    {
        $all_releases = new Data($this->product_details)->getMajorReleases();
        if (! array_key_exists($this->version->normalized, $all_releases)) {
            return ['error' => 'Not enough data for this version number.'];
        }

        // Release day object. Marketing ships at 6AM PT.
        $release_utc = new DateTime($all_releases[$this->version->normalized] . ' 06:00 PST')
            ->setTimezone(new DateTimeZone('UTC'));

        // The Nightly cycle starts on a Friday, 32 days before release day.
        // Anchored in UTC so every milestone is a +00:00 date like the rest of the app.
        $nightly_start = new DateTime($all_releases[$this->version->normalized])
            ->setTime(0, 0)
            ->modify('-32 days');

        // A milestone is $days days after the first day of the Nightly cycle.
        $d = fn(int $days, int $h = 0, int $m = 0): string =>
            (clone $nightly_start)->modify("+{$days} days")->setTime($h, $m)->format('Y-m-d H:i:sP');

        $schedule = [
            'qa_request_deadline'   => $d(-3),      // 3 days before Nightly starts
            'a11y_request_deadline' => $d(-3),      // matches the QA request deadline
            'nightly_start'         => $d(0),       // Nightly W0 Friday
            'qa_feature_done'       => $d(7, 21),   // Nightly W1 Friday, build ready for QA
            'qa_test_plan_due'      => $d(7, 21),   // Nightly W1 Friday
            'strings_handoff'       => $d(12),      // Nightly W2 Wednesday
            'relnotes_beta_ready'   => $d(13),      // Nightly W2 Thursday, draft beta release notes
            'qa_pre_merge_done'     => $d(13, 14),  // Nightly W2 Thursday
            'string_freeze'         => $d(13),      // Nightly W2 Thursday
            'merge_day'             => $d(14),      // Nightly W2 Friday
            'beta_1'                => $d(17, 13),  // Beta W1 Monday
            'beta_2'                => $d(19, 13),  // Beta W1 Wednesday
            'sumo_1'                => $d(19, 21),  // Beta W1 Wednesday, SUMO content creation
            'beta_3'                => $d(21, 13),  // Beta W1 Friday
            'qa_pre_rc_signoff'     => $d(21, 17),  // Beta W1 Friday, pre-release QA sign-off
            'beta_4'                => $d(24, 13),  // Beta W2 Monday
            'beta_5'                => $d(26, 13),  // Beta W2 Wednesday, security uplift deadline & last beta
            'relnotes_deadline'     => $d(27, 13),  // Beta W2 Thursday, release notes submission deadline
            'rc_gtb'                => $d(27, 17),  // Beta W2 Thursday, RC go to build
            'rc'                    => $d(28, 13),  // Beta W2 Friday, release notes finalized
            'release'               => $release_utc->format('Y-m-d H:i:sP'),
            // Single planned dot release, one week after the major release.
            'dot_release_1'         => (clone $release_utc)->modify('+7 days')->format('Y-m-d H:i:sP'),
        ];

        // Sort the schedule by date, needed for schedules with a fixup
        asort($schedule);

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

        $dot_release_1 = $this->getFutureSchedule()['dot_release_1'] ?? null;
        $dot_release_2 = $this->getFutureSchedule()['dot_release_2'] ?? null;
        $dot_release_3 = $this->getFutureSchedule()['dot_release_3'] ?? null;
        $dot_release_4 = $this->getFutureSchedule()['dot_release_4'] ?? null;

        if (isset($dot_release_1) && ! in_array(new DateTime($dot_release_1), $shipped_dot_releases)) {
            $milestones['dot_release_1'] = new DateTime($dot_release_1);
        }

        if (isset($dot_release_2) && ! in_array(new DateTime($dot_release_2), $shipped_dot_releases)) {
            $milestones['dot_release_2'] = new DateTime($dot_release_2);
        }

        if (isset($dot_release_3) && ! in_array(new DateTime($dot_release_3), $shipped_dot_releases)) {
            $milestones['dot_release_3'] = new DateTime($dot_release_3);
        }

        if (isset($dot_release_4) && ! in_array(new DateTime($dot_release_4), $shipped_dot_releases)) {
            $milestones['dot_release_4'] = new DateTime($dot_release_4); // @codeCoverageIgnore
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
            'relnotes_beta_ready'   => $short_version .' beta release notes ready',
            'strings_handoff'       => $short_version .' strings handed off to Engineering',
            'qa_pre_merge_done'     => $short_version . ' regression testing completed',
            'qa_test_plan_due'      => 'Final deadline for QA’s Feature Test Plan approval',
            'string_freeze'         => $short_version . ' string freeze starts',
            'merge_day'             => 'Merge day',
            'beta_1'                => ($short ? '' : 'Firefox ') . $short_version . ' b1 GTB',
            'beta_2'                => ($short ? '' : 'Firefox ') . $short_version . ' b2' . ($short ? ' GTB' : ' (GTB: 13:00 UTC)'),
            'beta_3'                => ($short ? '' : 'Firefox ') . $short_version . ' b3' . ($short ? ' GTB' : ' (GTB: 13:00 UTC)'),
            'sumo_1'                => 'SUMO deadline',
            'beta_4'                => ($short ? '' : 'Firefox ') . $short_version . ' b4' . ($short ? ' GTB' : ' (GTB: 13:00 UTC)'),
            'beta_5'                => ($short ? '' : 'Firefox ') . $short_version . ' b5' . ($short ? ' GTB' : ' (GTB: 13:00 UTC)'),
            'beta_6'                => ($short ? '' : 'Firefox ') . $short_version . ' b6' . ($short ? ' GTB' : ' (GTB: 13:00 UTC)'),
            'beta_7'                => ($short ? '' : 'Firefox ') . $short_version . ' b7' . ($short ? ' GTB' : ' (GTB: 13:00 UTC)'),
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
            'dot_release_1'         => ($short ? 'Planned ' : 'Planned Firefox ') . $version . ($short ? '.x' : ' dot release'),
            'dot_release_2'         => ($short ? 'Planned ' : 'Planned Firefox ') . $version . ($short ? '.y' : ' dot release'),
            'dot_release_3'         => ($short ? 'Planned ' : 'Planned Firefox ') . $version . ($short ? '.z' : ' dot release'),
            'dot_release_4'         => ($short ? 'Planned ' : 'Planned Firefox ') . $version . ($short ? '.a' : ' dot release'),
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
