<?php

declare(strict_types=1);

namespace ReleaseInsights;

class Data
{
    /** @var array<string, string> $future_releases */
    private readonly array $future_releases;

    /** @var array<string, string> $release_owners */
    private(set) array $release_owners;

    /** @var array<string> $wellness_days */
    private(set) array $wellness_days;

    /** @var array<string> $chemspills */
    private(set) array $chemspills;

    /** @var array<int, mixed> $planned_dot_releases */
    private(set) array $planned_dot_releases;

    private readonly string $pd_url;

    public function __construct(
        ?string $pd_url = null,
        public int $cache_duration = 900 // 15 minutes
    ) {
        // Method calls aren't valid as constructor default values, so the
        // Product Details location is resolved here: local fixtures in tests
        // (via URL::target()), the remote endpoint otherwise.
        $this->pd_url = $pd_url ?? URL::ProductDetails->target();

        $this->release_owners       = include DATA . 'release_owners.php';
        $this->future_releases      = include DATA . 'upcoming_releases.php';
        $this->wellness_days        = include DATA . 'wellness_days.php';
        $this->chemspills           = include DATA . 'chemspill_releases.php';
        $this->planned_dot_releases = include DATA . 'planned_dot_releases.php';
    }

    /**
     * @param  bool|boolean $current do we include the current release
     * @return array<string, string>
     */
    public function getFutureReleases(bool $current = false): array
    {
        return array_filter(
            $this->future_releases,
            fn(string $key) => (int) $key > ($current ? RELEASE -1 : RELEASE),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Major version numbers (as int) that are the last release of their calendar
     * year in our upcoming releases schedule. Their beta cycle spans the
     * end-of-year holidays, so milestones may be adjusted for staff availability.
     *
     * A version is only flagged when it is followed by a release in a later year:
     * we never assume the last entry of the list is a year-end release, as more
     * releases may still be added to its year.
     *
     * @return list<int>
     */
    public function getLastReleasesOfYear(): array
    {
        $versions = array_keys($this->future_releases);
        $last = [];

        foreach ($versions as $i => $version) {
            $year = substr($this->future_releases[$version], 0, 4);
            $next = $versions[$i + 1] ?? null;

            if ($next !== null && substr($this->future_releases[$next], 0, 4) !== $year) {
                $last[] = (int) $version;
            }
        }

        return $last;
    }

    /** @return array<string, string> */
    public function getESRReleases(): array
    {
        // Historical data from Product Details, cache a week
        $esr_releases = Json::load($this->pd_url . 'firefox.json', $this->cache_duration)['releases'];

        // Reduce to only ESR releases
        $esr_releases = array_filter(
            $esr_releases,
            fn(string $key) => str_ends_with($key, 'esr'),
            ARRAY_FILTER_USE_KEY
        );

        // Rebuild a version_number => date array
        $esr_releases = array_column($esr_releases, 'date', 'version');

        // Sort releases by release date
        asort($esr_releases);

        return $esr_releases;
    }

    /**
     * Get the release date of our Latest Major release
     *
     * @return array<string, string>
     */
    public function getLatestMajorRelease(): array
    {
        $past_releases = $this->getMajorPastReleases();
        $last_release = array_key_last($past_releases);

        return [$last_release => $past_releases[$last_release]];
    }

    /**
     * Get all past Desktop Releases on the release channel,
     *
     * @return array<string, string>
     */
    public function getDesktopPastReleases(bool $dot_releases = true): array
    {
        // Historical data from Product Details, cache a week
        $major_releases = Json::load($this->pd_url . 'firefox_history_major_releases.json', $this->cache_duration);
        $minor_releases =  $dot_releases == true ? Json::load($this->pd_url . 'firefox_history_stability_releases.json', $this->cache_duration) : [];
        $all_releases = [...$major_releases, ...$minor_releases];

        // Sort releases by release date
        asort($all_releases);

        // Remove all minor ESR releases
        $exclude_esr = function (string $version_number) {
            // Those releases were not ESR releases despite the middle number
            if (in_array($version_number, ['33.1', '33.1.1', '50.1.0'])) {
                return true;
            }

            $data = explode('.', $version_number);

            // We started ESR releases with version 10
            if ((int) $data[0] < 10) {
                return true;
            }

            if ((int) $data[1] > 0) {
                return false;
            }

            return true;
        };

        return array_filter($all_releases, $exclude_esr, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get all Dot Releases for both Desktop and Android
     * We ignore Android dot releases before 126
     *
     * @return array<string, array<string,string>>
     */
    public function getDotReleases(): array
    {
        $filter = function(string $platform) {
            $target = $platform == 'desktop' ? 'firefox.json' : 'mobile_android.json';

            // Get source of Data where we can extract dot releases information
            $data = Json::load($this->pd_url . $target, $this->cache_duration)['releases'];

            // Extract all minor releases
            $data = array_filter($data, fn($v) => isset($v['category']) && $v['category'] == 'stability');

            if ($platform === 'desktop') {
               // Filter out ESR releases
                $data = array_filter($data, fn($k) => ! str_ends_with($k, 'esr'), ARRAY_FILTER_USE_KEY);
            }

            // Rebuild a simplified array: ['128.0.1' => '2024-07-16',...]
            $data = array_column($data, 'date', 'version');

            if ($platform === 'android') {
                // Filter out versions older than 126.
                // 126 is when we merged android and desktop code and aligned dot release naming.
                $data = array_filter($data, fn($k) => explode('.', $k)[0] > 125, ARRAY_FILTER_USE_KEY);
            }

            return $data;
        };

        $desktop = $filter('desktop');
        $android = $filter('android');

        $all = array_merge($desktop, $android);
        ksort ($all, SORT_NATURAL);

        $dot_releases = [];
        foreach ($all as $version => $date) {
            $platform = 'both';

            if (! array_key_exists($version, $android)) {
                $platform = 'desktop';
            }

            if (! array_key_exists($version, $desktop)) {
                $platform = 'android';
            }

            $dot_releases[$version] = ['date' => $date, 'platform' => $platform];
        }

        return $dot_releases;
    }

    /**
     * Get all past Betas
     *
     * @return array<string, string>
     */
    public function getPastBetas(): array
    {
        return Json::load($this->pd_url . 'firefox_history_development_releases.json', $this->cache_duration);
    }

    /**
     * Get all past Releases on the release channel, but not dot releases
     *
     * @return array<string, string>
     */
    public function getMajorPastReleases(): array
    {
        return Json::load($this->pd_url . 'firefox_history_major_releases.json', $this->cache_duration);
    }

    /**
     * Get extra planned dot releases
     *
     * @return array<int, mixed>
     */
    public function getPlannedDotReleases(): array
    {
        return $this->planned_dot_releases;
    }


    /**
     * Get all past and planned Releases on the release channel, but not dot releases
     *
     * @return array<string, string>
     */
    public function getMajorReleases(): array
    {
        return [
            ...$this->getMajorPastReleases(),
            ...$this->future_releases,
        ];
    }

    /** @return array<string, string> */
    public function getFirefoxVersions(): array
    {
        // Cache Product Details versions, 15mn cache
        return Json::load($this->pd_url . 'firefox_versions.json', $this->cache_duration);
    }

    public static function getDesktopAdoptionRate(string $version): ?float
    {
        // Check current uptake rate for the latest release
        // The underlying data is only updated once a day at 13:00 UTC
        // @codeCoverageIgnoreStart
        if (! defined('TESTING_CONTEXT')) {
            $uptake = Json::load(URL::Pollbot->value
                    . 'firefox/'
                    . $version
                    . '/telemetry/main-summary-uptake')['message'] ?? '0' ;
        } else {
        // @codeCoverageIgnoreEnd
            if ($version == '130.0') {
                $uptake = Json::load(URL::Pollbot->target() . 'main-summary-uptake.json')['message'] ?? '0' ;
            } else {
                $uptake = 'Query results contained no rows.';
            }
        }

        if ($uptake == 'Query results contained no rows.') {
            return null;
        }

        // This public data is stored as a string, extract only the number
        $uptake = preg_replace('/Telemetry uptake for version.*\(.*\) is /', '', $uptake);
        $uptake = str_replace('%', '', $uptake);

        return  (float) $uptake;
    }

    /**
     * On Release day we have a lot of special cases.
     */
    public function isTodayReleaseDay(): bool
    {
        return in_array(date('Y-m-d'), $this->getMajorReleases());
    }
}
