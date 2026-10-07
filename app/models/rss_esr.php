<?php

declare(strict_types=1);

use ReleaseInsights\Data;

// ESR releases, latest first
$releases = new Data()->getESRReleases();
uksort($releases, fn(string $a, string $b) => strcmp($releases[$b], $releases[$a]) ?: strnatcmp($b, $a));

// Limit our RSS feed to 30 items, several ESR branches ship on the same day
$releases = array_slice($releases, 0, 30, true);

$set_time = fn(string $date) => new DateTime($date)->setTime(13, 0)->format(DateTime::RSS);

$rss = [];
foreach ($releases as $version => $date) {
    $rss[] = [
        'version'  => (string) $version,
        'date'     => $set_time($date),
        'platform' => 'esr',
    ];
}

// We take the last update to the feed as the first item (latest release) in the sorted array
return [$set_time(reset($releases)), $rss];
