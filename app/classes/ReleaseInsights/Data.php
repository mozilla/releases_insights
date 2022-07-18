<?php

declare(strict_types=1);

namespace ReleaseInsights;

use ReleaseInsights\Utils;

class Data
{
    // public  array $shipped;

    /** @var array<string, string> $future */
    private array $future;

    /** @var array<string, string> $owners */

    private string $pd_url;

    /** @var array<string, string> $owners */
    private array $owners;

    public function __construct(string $pd_url = 'https://product-details.mozilla.org/1.0/')
    {
        $this->owners = include DATA . 'release_owners.php';
        $this->future = include DATA . 'upcoming_releases.php';
        $this->pd_url = $pd_url;
    }

    /** @return array<string, string> */
    public function getOwners(): array
    {
        return $this->owners;
    }

    /** @return array<string, string> */
    public function getFutureReleases(): array
    {
        return $this->future;
    }

    /** @return array<string, string> */
    public function getFirefoxVersions(): array
    {
        // Cache Product Details versions, 15mn cache
        return Utils::getJson($this->pd_url . 'firefox_versions.json', 900);
    }

}
/*

$firefox_releases = Utils::getJson('https://product-details.mozilla.org/1.0/firefox.json')['releases'];
$devedition_releases = Utils::getJson('https://product-details.mozilla.org/1.0/devedition.json')['releases'];

*/