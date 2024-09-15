<?php

declare(strict_types=1);

use ReleaseInsights\Data;

$data = new Data();

// We reformat the data to the same format as getDotReleases() before merging them
$major_releases = [];
foreach ($data->getMajorPastReleases() as $version => $date) {
    $major_releases[$version] = [
        'date' => $date,
        'platform' => 'both',
    ];
}

// We merge and reorder the releases to have the latest first
$releases = array_merge($major_releases, $data->getDotReleases());
krsort($releases, SORT_NATURAL);

// Limit our RSS feed to 30 items, roughly a year of releases
$releases = array_slice($releases, 0, 30);

$set_time = fn(string $date) => (new DateTime($date))->setTime(13, 0)->format(DateTime::RFC822);

$rss = [];
foreach ($releases as $key => $values) {
    $rss[] = [
        'version'  => $key,
        'date'     => $set_time($values['date']),
        'platform' => $values['platform'],
    ];
}

// We take the last update to the feed as the first item (latest release) in the sorted array
return [$set_time(reset($releases)['date']), $rss];