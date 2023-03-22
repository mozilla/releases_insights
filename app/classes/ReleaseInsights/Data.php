<?php

declare(strict_types=1);

namespace ReleaseInsights;

use ReleaseInsights\Utils;

class Data
{
    /** @var array<string, string> $future_releases */
    private array $future_releases;

    /** @var array<string, string> $release_owners */
    private array $release_owners;

    private string $pd_url;

    public function __construct(string $pd_url = 'https://product-details.mozilla.org/1.0/')
    {
        $this->release_owners  = include DATA . 'release_owners.php';
        $this->future_releases = include DATA . 'upcoming_releases.php';
        $this->pd_url = $pd_url;
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
            function ($key) { return (int) $key > RELEASE; },
            ARRAY_FILTER_USE_KEY
        );
    }

    /** @return array<string, string> */
    public function getESRReleases(): array
    {
        // Historical data from Product Details, cache a week
        $esr_releases = Utils::getJson($this->pd_url . 'firefox.json', 604800)['releases'];

        // Reduce to only ESR releases
        $esr_releases = array_filter(
            $esr_releases,
            function ($key) { return str_ends_with($key, 'esr'); },
            ARRAY_FILTER_USE_KEY
        );

        // Rebuild a version_number => date array
        $esr_releases = array_column($esr_releases, 'date', 'version');

        // Sort releases by release date
        asort($esr_releases);

        return $esr_releases;
    }

    /**
     * Get all past Releases on the release channel, including dot releases
     *
     * @return array<string, string>
     */
    public function getPastReleases(bool $dot_releases = true): array
    {
        // Historical data from Product Details, cache a week
        $major_releases = Utils::getJson($this->pd_url . 'firefox_history_major_releases.json', 604800);
        $minor_releases =  $dot_releases == true ? Utils::getJson($this->pd_url . 'firefox_history_stability_releases.json', 604800) : [];
        $all_releases = array_merge($major_releases, $minor_releases);

        // Sort releases by release date
        asort($all_releases);

        // Remove all minor ESR releases
        $exclude_esr = function($version_number) {
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
     * Get all past Releases on the release channel, but not dot releases
     *
     * @return array<string, string>
     */
    public function getMajorPastReleases(): array
    {
        return Utils::getJson($this->pd_url . 'firefox_history_major_releases.json', 604800);
    }

    /**
     * Get all past and planned Releases on the release channel, but not dot releases
     *
     * @return array<string, string>
     */
    public function getMajorReleases(): array
    {
        return array_merge(
            $this->getMajorPastReleases(),
            $this->future_releases
        );
    }


    /** @return array<string, string> */
    public function getFirefoxVersions(): array
    {
        // Cache Product Details versions, 15mn cache
        return Utils::getJson($this->pd_url . 'firefox_versions.json', 900);
    }

}