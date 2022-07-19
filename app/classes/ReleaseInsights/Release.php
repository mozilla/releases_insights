<?php

declare(strict_types=1);

namespace ReleaseInsights;

use DateTime;
use ReleaseInsights\Data;
use ReleaseInsights\Utils;
use ReleaseInsights\Version;

enum Status
{
    case Past;
    case Current;
    case Future;
}

class Release
{
    private string $version;

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
    public function getSchedule(): array
    {
        $all_releases = (new Data)->getMajorReleases();

        if (! array_key_exists($this->version, $all_releases)) {
            return ['error' => 'Not enough data for this version number.'];
        }

        // Future release date object
        $release = new DateTime($all_releases[$this->version]);

        // Previous release object
        $previous_release = new DateTime($all_releases[Version::decrement($this->version, 1)]);

        // Calculate 1st day of the nightly cycle
        $nightly = new DateTime($all_releases[Version::decrement($this->version, 2)]);

        $nightly->modify('-1 day');

        $x = match ($this->version) {
            '97.0' => 4,
            default => 3,
        };

        // Transform all the DateTime objects in the $schedule array into formated date strings
        $date = function(string|object $day) use ($nightly): string {
            return is_object($day) ? $day->format('Y-m-d H:i') : $nightly->modify($day)->format('Y-m-d H:i');
        };

        $schedule = [
            'nightly_start'    => $this->version === '100.0' ? $date('+1 day') : $date($nightly),
            'soft_code_freeze' => $date($nightly->modify('+' . $x .' weeks')->modify('Thursday')),
            'string_freeze'    => $date('Friday'),
            'merge_day'        => $date('Monday'),
            'beta_1'           => $date('Monday'),
            'beta_2'           => $date('Tuesday'),
            'beta_3'           => $date('Thursday'),
            'beta_4'           => $date('Sunday'),
            'beta_5'           => $date('Tuesday'),
            'beta_6'           => $date('Thursday'),
            'beta_7'           => $date('Sunday'),
            'beta_8'           => $date('Tuesday'),
            'beta_9'           => $date('Thursday'),
            'rc_gtb'           => $date('Monday'),
            'rc'               => $date('Tuesday'),
            'release'          => $date($release),
        ];

        // Sort the schedule by date, needed for schedules with a fixup
        asort($schedule);

        // The schedule starts with the release version number
        return ['version' => $this->version] + $schedule;
    }

}