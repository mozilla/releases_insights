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

$rss = [];
foreach ($releases as $key => $values) {
    $rss[] = [
        'date'     => (new DateTime($values['date']))->setTime(13, 0)->format(DateTime::RFC822),
        'version'  => $key,
        'platform' => $values['platform'],
        'relnotes' => 'https://www.mozilla.org/en-US/firefox/' . $key . '/releasenotes/',
    ];
}

// We take the last update to the feed as the first item (latest release) in the sorted array
$latest_release_date = (new DateTime(reset($releases)['date']))->setTime(13,0)->format(DateTime::RFC822);

return [$latest_release_date, $rss];