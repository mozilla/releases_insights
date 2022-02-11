<?php

declare(strict_types=1);

use ReleaseInsights\Utils;

$release_owners = include MODELS . 'api/release_owners.php';

// We need to remove the releases still in planning
$releases = array_filter(
    $release_owners,
    fn($k) => $k <= RELEASE,
    ARRAY_FILTER_USE_KEY
);

$owners = array_values(array_unique($releases));

$output = [];
foreach ($owners as $owner) {
    $output[] = [
        'owner' => $owner,
        'releases' => array_keys($releases, $owner),
        'total' => count(array_keys($releases, $owner))
    ];
}

array_multisort(array_column($output, 'total'), SORT_DESC, $output);

return $output;


