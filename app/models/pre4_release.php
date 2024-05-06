<?php

declare(strict_types=1);

use ReleaseInsights\{Json, URL, Utils, Version};

// Historical data from Product Details
$firefox_releases = Json::load(URL::ProductDetails->value . 'firefox.json')['releases'];

// Number of dot releases
$dot_release_count = count((array) array_filter(
    $firefox_releases,
    fn ($key) => str_starts_with((string) $key, 'firefox-' . Version::get() . '.'),
    ARRAY_FILTER_USE_KEY
));

$release_date = $firefox_releases['firefox-' . Version::get()]['date'];

return [$dot_release_count, $release_date];
