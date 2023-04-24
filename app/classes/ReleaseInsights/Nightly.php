<?php

declare(strict_types=1);

namespace ReleaseInsights;

use ReleaseInsights\Utils;

class Nightly
{
    public string $version;
    public bool   $auto_updates = true;
    public string $emergency_message = '';

    public function __construct(
        public string $pd  = 'https://product-details.mozilla.org/1.0/',
        public string $AUS = 'https://aus-api.mozilla.org/api/v1/',
        // Testing url below
        // string $AUS = 'https://stage.balrog.nonprod.cloudops.mozgcp.net/api/v1/',
        public string $update_status = 'emergency_shutoff/Firefox/nightly',
    )
    {
        $this->version = Utils::getJson(
            $this->pd . 'firefox_versions.json',
            604800
        )['FIREFOX_NIGHTLY'];

        // @codeCoverageIgnoreStart
        // Check that the URL is valid (We are not in an HTTP (useful for unit tests that test a local file)
        if (filter_var($this->AUS . $this->update_status, FILTER_VALIDATE_URL)) {
            // The JSON file only exists when updates are stopped.
            // If there is no file at the URL, it means that automatic updates are enabled.
            $this->auto_updates = str_contains(
                get_headers($this->AUS . $this->update_status)[0],
                '404'
            );
        }
        // @codeCoverageIgnoreEnd

        if (defined('UNIT_TESTING')) {
            $this->auto_updates = false;
        }

        if ($this->auto_updates === false) {
            $tmp = Utils::getJson($this->AUS . $this->update_status, 1);
            $tmp = $tmp['comment'] ?? '';
            $this->emergency_message = Utils::secureText($tmp);
            unset($tmp);
        }
    }
}
