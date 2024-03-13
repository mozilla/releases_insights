<?php

declare(strict_types=1);

namespace ReleaseInsights;

class Data
{
    /** @var array<string, string> $future_releases */
    private readonly array $future_releases;

    /** @var array<string, string> $release_owners */
    private readonly array $release_owners;

    public function __construct(
        private readonly string $pd_url = 'https://product-details.mozilla.org/1.0/',
        public int $cache_duration = 900 // 15 minutes
    ) {
        $this->release_owners  = include DATA . 'release_owners.php';
        $this->future_releases = include DATA . 'upcoming_releases.php';
    }

    /** @return array<string, string> */
    public function getOwners(): array
    {
        return $this->release_owners;
    }

    /** @return array<string, string> */
    public function getFutureReleases(): array
    {
        return array_filter(
            $this->future_releases,
            fn(string $key) => (int) $key > RELEASE,
            ARRAY_FILTER_USE_KEY
        );
    }

    /** @return array<string, string> */
    public function getESRReleases(): array
    {
        // Historical data from Product Details, cache a week
        $esr_releases = Utils::getJson($this->pd_url . 'firefox.json', $this->cache_duration)['releases'];

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
     * Get all past Releases on the release channel, including dot releases
     *
     * @return array<string, string>
     */
    public function getPastReleases(bool $dot_releases = true): array
    {
        // Historical data from Product Details, cache a week
        $major_releases = Utils::getJson($this->pd_url . 'firefox_history_major_releases.json', $this->cache_duration);
        $minor_releases =  $dot_releases == true ? Utils::getJson($this->pd_url . 'firefox_history_stability_releases.json', $this->cache_duration) : [];
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
            if (intval($data[0]) < 10) {
                return true;
            }

            if (intval($data[1]) > 0) {
                return false;
            }

            return true;
        };

        return array_filter($all_releases, $exclude_esr, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get all past Betas
     *
     * @return array<string, string>
     */
    public function getPastBetas(): array
    {
        return Utils::getJson($this->pd_url . 'firefox_history_development_releases.json', $this->cache_duration);
    }

    /**
     * Get all past Releases on the release channel, but not dot releases
     *
     * @return array<string, string>
     */
    public function getMajorPastReleases(): array
    {
        return Utils::getJson($this->pd_url . 'firefox_history_major_releases.json', $this->cache_duration);
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
        return Utils::getJson($this->pd_url . 'firefox_versions.json', $this->cache_duration);
    }

    /**
     * On Release day we have a lot of special cases.
     */
    public function isTodayReleaseDay(): bool
    {
        return in_array(date('Y-m-d'), $this->getMajorReleases());
    }
}
