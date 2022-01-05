<?php

declare(strict_types=1);

namespace ReleaseInsights;

class Nightly
{
    public string $pd;
    public string $AUS;
    public string $version;
    public bool $auto_updates = true;

    public function __construct(
        string $pd  = 'https://product-details.mozilla.org/1.0/',
        string $AUS = 'https://aus-api.mozilla.org/api/v1/',
        // Testing url below
        // string $AUS = 'https://stage.balrog.nonprod.cloudops.mozgcp.net/api/v1/',
    )
    {
        $this->pd  = $pd;
        $this->AUS = $AUS;
        $this->version = Utils::getJson(
            $this->pd . 'firefox_versions.json',
            604800
        )['FIREFOX_NIGHTLY'];
        $this->auto_updates = str_contains(
            get_headers($this->AUS . 'emergency_shutoff/Firefox/nightly')[0],
            '404'
        );
    }
}
