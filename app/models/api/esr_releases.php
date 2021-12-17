<?php

declare(strict_types=1);

use ReleaseInsights\Utils;

// Historical data from Product Details, cache a week
$esr_releases = Utils::getJson(
    'https://product-details.mozilla.org/1.0/firefox.json',
    604800
)['releases'];

// Reduce to only ESR releases
$esr_releases = array_filter(
    $esr_releases,
    function ($key) { return str_ends_with($key, 'esr'); },
    ARRAY_FILTER_USE_KEY
);

// Rebuild a version_number => date array
return array_column($esr_releases, 'date', 'version');
