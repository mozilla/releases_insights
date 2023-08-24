<?php

declare(strict_types=1);

namespace ReleaseInsights;

use DateTime;
use ReleaseInsights\{Data, Utils, Version};

enum Status
{
    case Past;
    case Current;
    case Future;
}

class Release
{
    private string $version;

    /** @var array<string> $no_planned_dot_releases */
    public array $no_planned_dot_releases = ['108.0', '111.0', '115.0'];

    /* @phpstan-ignore-next-line */
    private Status $release_status;

    public function __construct(string $version)
    {
        $this->version = Version::get($version);
        $major_version = Utils::getMajorVersion($version);

        $this->release_status = match (true) {
            $major_version === RELEASE => Status::Current,
            $major_version  >  RELEASE => Status::Future,
            default                    => Status::Past,
        };
    }

    /**
     * Get The schedule for a future Release
     *
     * @return array<string, string>
     */
    public function getSchedule(string $pd_url = 'https://product-details.mozilla.org/1.0/'): array
    {
        $all_releases = (new Data($pd_url))->getMajorReleases();
        if (! array_key_exists($this->version, $all_releases)) {
            return ['error' => 'Not enough data for this version number.'];
        }

        // Future release date object
        $release = new DateTime($all_releases[$this->version] . ' 06:00 PST');

        $beta_target = Version::decrement($this->version, 1);
        $nightly_target = Version::decrement($this->version, 2);

        if ($beta_target == '14.0') {
            $beta_target = '14.0.1';
        }

        if ($nightly_target == '14.0') {
            $nightly_target = '14.0.1';
        }

        // Previous release date object
        $previous_release = new DateTime($all_releases[$beta_target] . ' 06:00 PST');

        // Calculate 1st day of the nightly cycle
        $nightly = new DateTime($all_releases[$nightly_target]);
        $nightly->modify('-1 day');

        $x = match ($this->version) {
            '97.0'  => 4,
            '110.0' => 4,
            default => 3,
        };

        // Transform all the DateTime objects in the $schedule array into formated date strings
        $date = function(string|object $day) use ($nightly): string {
            return is_object($day) ? $day->format('Y-m-d H:i:sP') : $nightly->modify($day)->format('Y-m-d H:i:sP');
        };

        if ($this->version === '116.0') {
            $schedule = [
                'nightly_start'       => $date($nightly),
                'qa_request_deadline' => $date('Friday 21:00'),
                'qa_feature_done_1'   => $date('Friday +1 week 21:00'),
                'qa_feature_done_2'   => $date($nightly->modify('+' . ($x - 2) . ' weeks')->modify('Wednesday 21:00')),
                'soft_code_freeze'    => $date('Thursday 08:00'),
                'qa_pre_merge_done'   => $date('Friday 14:00'),
                'string_freeze'       => $date('Friday'),
                'merge_day'           => $date('Tuesday'),
                'beta_1'              => $date('Tuesday'),
                'beta_2'              => $date('Thursday 21:00'),
                'sumo_1'              => $date('Friday 21:00'),
                'beta_3'              => $date('Sunday 21:00'),
                'beta_4'              => $date('Tuesday 21:00'),
                'beta_5'              => $date('Thursday 21:00'),
                'beta_6'              => $date('Sunday 21:00'),
                'sumo_2'              => $date('Monday 21:00'),
                'beta_7'              => $date('Tuesday 21:00'),
                'qa_pre_rc_signoff'   => $date('Wednesday 14:00'),
                'beta_8'              => $date('Thursday 21:00'),
                'rc_gtb'              => $date('Monday 21:00'),
                'rc'                  => $date('Tuesday'),
                'release'             => $date($release->setTimezone(new \DateTimeZone('UTC'))),
            ];
        } elseif ($this->version === '122.0') {
            $schedule = [
                'nightly_start'       => $date($nightly),
                'qa_request_deadline' => $date('Friday'),
                'qa_feature_done_1'   => $date('Friday +1 week 21:00'),
                'qa_feature_done_2'   => $date($nightly->modify('+' . ($x - 2) . ' weeks')->modify('Wednesday 21:00')),
                'soft_code_freeze'    => $date('Thursday 08:00'),
                'qa_pre_merge_done'   => $date('Friday 14:00'),
                'string_freeze'       => $date('Friday'),
                'merge_day'           => $date('Monday'),
                'beta_1'              => $date('Monday'),
                'beta_2'              => $date('Tuesday 21:00'),
                'beta_3'              => $date('Thursday 21:00'),
                'sumo_1'              => $date('Friday 21:00'), // Friday of Beta week 1
                'beta_4'              => $date('Thursday 21:00'),
                'beta_5'              => $date('Tuesday 21:00'),
                'beta_6'              => $date('Thursday 21:00'),
                'beta_7'              => $date('Sunday 21:00'),
                'sumo_2'              => $date('Monday 21:00'), // Monday of Beta Week 3
                'beta_8'              => $date('Tuesday 21:00'),
                'qa_pre_rc_signoff'   => $date('Wednesday 14:00'),
                'beta_9'              => $date('Thursday 21:00'),
                'rc_gtb'              => $date('Monday 21:00'),
                'rc'                  => $date('Tuesday'),
                'release'             => $date($release->setTimezone(new \DateTimeZone('UTC'))),
            ];
        } else {
            $schedule = [
                'nightly_start'       => $this->version === '117.0' ? $date('+1 day') : $date($nightly),
                'qa_request_deadline' => $date('Friday'),
                'qa_feature_done_1'   => $date('Friday +1 week 21:00'),
                'qa_feature_done_2'   => $date($nightly->modify('+' . ($x - 2) . ' weeks')->modify('Wednesday 21:00')),
                'soft_code_freeze'    => $date('Thursday 08:00'),
                'qa_pre_merge_done'   => $date('Friday 14:00'),
                'string_freeze'       => $date('Friday'),
                'merge_day'           => match($this->version) {
                                            '123.0' => $date('Monday +1 week'),
                                            '135.0' => $date('Monday +2 week'),
                                            default => $date('Monday'),
                                        },
                'beta_1'              => $date('Monday'),
                'beta_2'              => $date('Tuesday 21:00'),
                'beta_3'              => $date('Thursday 21:00'),
                'sumo_1'              => $date('Friday 21:00'), // Friday of Beta week 1
                'beta_4'              => $date('Sunday 21:00'),
                'beta_5'              => $date('Tuesday 21:00'),
                'beta_6'              => $date('Thursday 21:00'),
                'beta_7'              => $date('Sunday 21:00'),
                'sumo_2'              => $date('Monday 21:00'), // Monday of Beta Week 3
                'beta_8'              => $date('Tuesday 21:00'),
                'qa_pre_rc_signoff'   => $date('Wednesday 14:00'),
                'beta_9'              => $date('Thursday 21:00'),
                'rc_gtb'              => $date('Monday 21:00'),
                'rc'                  => $date('Tuesday'),
                'release'             => $date($release->setTimezone(new \DateTimeZone('UTC'))),
            ];
        }

        if (! in_array($this->version, $this->no_planned_dot_releases)) {
            if ($this->version === '121.0') {
                $schedule = $schedule + ['planned_dot_release' => $date($release->modify('+3 weeks 00:00'))];
            } else {
                $schedule = $schedule + ['planned_dot_release' => $date($release->modify('+2 weeks 00:00'))];
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
        return ['version' => $this->version] + $schedule;
    }

    public static function getNiceLabel(string $version, string $label, bool $short=true): string
    {
        $short_version = (string) (int) $version;

        $labels = [
            'nightly_start'       => 'Nightly ' . $short_version . ' starts',
            'qa_request_deadline' => $short_version . ' QA request deadline for Nightly',
            'qa_feature_done_1'   => $short_version .' build ready for nightly QA',
            'qa_feature_done_2'   => $short_version .' build ready for beta QA',
            'soft_code_freeze'    => ($short ? '' : 'Firefox ') . $short_version . ' soft Code Freeze starts at 08:00 UTC',
            'qa_pre_merge_done'   => $short_version . ' regression testing completed',
            'string_freeze'       => 'String Freeze' . ($short ? '' : ' starts'),
            'merge_day'           => 'Merge day',
            'beta_1'              => ($short ? '' : 'Firefox ') . $short_version . ' b1 GTB',
            'beta_2'              => ($short ? '' : 'Firefox ') . $short_version . ' b2' . ($short ? ' GTB' : ' (GTB: 21:00 UTC)'),
            'beta_3'              => ($short ? '' : 'Firefox ') . $short_version . ' b3' . ($short ? ' GTB' : ' (GTB: 21:00 UTC)'),
            'sumo_1'              => 'SUMO deadline',
            'beta_4'              => ($short ? '' : 'Firefox ') . $short_version . ' b4' . ($short ? ' GTB' : ' (GTB: 21:00 UTC)'),
            'beta_5'              => ($short ? '' : 'Firefox ') . $short_version . ' b5' . ($short ? ' GTB' : ' (GTB: 21:00 UTC)'),
            'beta_6'              => ($short ? '' : 'Firefox ') . $short_version . ' b6' . ($short ? ' GTB' : ' (GTB: 21:00 UTC)'),
            'beta_7'              => ($short ? '' : 'Firefox ') . $short_version . ' b7' . ($short ? ' GTB' : ' (GTB: 21:00 UTC)'),
            'sumo_2'              => 'SUMO content localization starts',
            'beta_8'              => ($short ? '' : 'Firefox ') . $short_version . ' b8' . ($short ? ' GTB' : ' (GTB: 21:00 UTC)'),
            'qa_pre_rc_signoff'   => $short_version . ' QA sign off',
            'beta_9'              => ($short ? '' : 'Firefox ') . $short_version . ' b9' . ($short ? ' GTB' : ' (GTB: 21:00 UTC)'),
            'beta_10'             => ($short ? '' : 'Firefox ') . $short_version . ' b10' . ($short ? ' GTB' : ' (GTB: 21:00 UTC)'),
            'beta_11'             => ($short ? '' : 'Firefox ') . $short_version . ' b11' . ($short ? ' GTB' : ' (GTB: 21:00 UTC)'),
            'beta_12'             => ($short ? '' : 'Firefox ') . $short_version . ' b12' . ($short ? ' GTB' : ' (GTB: 21:00 UTC)'),
            'beta_13'             => ($short ? '' : 'Firefox ') . $short_version . ' b13' . ($short ? ' GTB' : ' (GTB: 21:00 UTC)'),
            'beta_14'             => ($short ? '' : 'Firefox ') . $short_version . ' b14' . ($short ? ' GTB' : ' (GTB: 21:00 UTC)'),
            'beta_15'             => ($short ? '' : 'Firefox ') . $short_version . ' b15' . ($short ? ' GTB' : ' (GTB: 21:00 UTC)'),
            'rc_gtb'              => ($short ? '' : 'Firefox ') . $short_version . ' go to Build',
            'rc'                  => ($short ? '' : 'Firefox ') . 'RC',
            'release'             => ($short ? '' : 'Firefox ') . $short_version . ($short ? ' Release' : ' go-live @ 6AM PT'),
            'planned_dot_release' => ($short ? 'Planned ' : 'Planned Firefox ') . $version . ($short ? '.x' : ' dot release'),
        ];

        return $labels[$label];
    }
}