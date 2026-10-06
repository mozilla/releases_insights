<?php

declare(strict_types=1);

namespace ReleaseInsights;

use Cache\Cache;

class Nightly
{
    public string $version;
    public bool $auto_updates = true;
    public string $emergency_message = '';

    public function __construct(
        public string $pd  = URL::ProductDetails->value,
        public string $AUS = URL::Balrog->value,
        public string $update_status = 'emergency_shutoff/Firefox/nightly',
    ) {
        $this->version = Json::load(
            $this->pd . 'firefox_versions.json',
            604800
        )['FIREFOX_NIGHTLY'];

        // @codeCoverageIgnoreStart
        // Check that the URL is valid (We are not in an HTTP (useful for unit tests that test a local file)
        if (filter_var($this->AUS . $this->update_status, FILTER_VALIDATE_URL)) {
            // The JSON file only exists when updates are stopped.
            // If there is no file at the URL, it means that automatic updates are enabled.
            // Cached for a minute to avoid an HTTP request on every page view.
            $cache_key = 'nightly_auto_updates_' . $this->AUS . $this->update_status;
            if (($auto_updates = Cache::getKey($cache_key, 60)) === false) {
                $auto_updates = str_contains(
                    (string) (Utils::getHeaders($this->AUS . $this->update_status)[0] ?? ''),
                    '404'
                ) ? 'true' : 'false';
                Cache::setKey($cache_key, $auto_updates, 60);
            }
            $this->auto_updates = $auto_updates === 'true';
        }
        // @codeCoverageIgnoreEnd

        if (defined('TESTING_CONTEXT')) {
            $this->auto_updates = false;
        }

        if ($this->auto_updates === false) {
            $msg = Json::load($this->AUS . $this->update_status, 60)['comment'] ?? '';
            if ($msg !== '') {
                $this->emergency_message = Utils::secureText($msg);
            }
        }
    }

    /**
     * Determine the start of the cycle.
     * This is almost always the release date of the release-2
     * In our train model.
     * Exceptions to this rule are hardcoded
     */
    public static function cycleStart(int $version): string
    {
        $releases = Json::load(URL::ProductDetails->target() . 'firefox.json')['releases'];

        return match($version) {
            1   => '2004-11-09',
            2   => '2006-10-24',
            4   => '2010-01-21',
            16  => '2012-06-04', // We never had a 14.0 release, so this is hardcoded
            127 => '2024-04-16', // We never had a 125.0 release, so this is hardcoded
            default => $releases['firefox-' . (string) ($version - 2) . '.0']['date'],
        };
    }

    /**
     * Get the latest BuildID from Balrog
     */
    public static function getLatestBuildID(): string
    {
        // Get the latest nightly build ID, used as a tooltip on the nightly version number
        $latest_nightly = Json::load(
            URL::Balrog->target() . 'releases/Firefox-mozilla-central-nightly-latest',
            1800
        );

        return $latest_nightly['platforms']['WINNT_x86_64-msvc']['locales']['en-US']['buildID'] ?? 'N/A';
    }
}
