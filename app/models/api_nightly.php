<?php

use Cache\Cache;
Use ReleaseInsights\Utils as Utils;

$date = Utils::getDate();

$options = [
    'http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/json',
        'content' => '{
      "_source": ["build.id", "source.revision"],
      "query": {
        "bool": {
          "must": [
            { "match": { "source.product": "firefox" }},
            { "match": { "target.platform": "win64" }},
            { "match": { "target.locale": "en-US" }},
            { "match": { "target.channel": "nightly" }},
            { "regexp": { "build.target": "x86_64.*" }},
            { "regexp": { "build.id": "'.$date.'.*" }}
          ]
        }
      }
    }',
    ],
];

// The date in the string varies so we create a unique file name in cache
$cache_id = $options['http']['content'];

// If we can't retrieve cached data, we create and cache it.
// We cache because we want to avoid http request latency
if (!$data = Cache::getKey($cache_id)) {
    $data = file_get_contents(
        'https://buildhub.moz.tools/api/search',
        false,
        stream_context_create($options)
    );

    // Extract into an array the values we want from the data source
    $data = json_decode($data, true);
    $data = array_column($data['hits']['hits'], '_source');

    // No data returned, bug or incorrect date, don't cache.
    if (empty($data)) {
        return [];
    }

    // Build a [buildid => revision] array
    $filtered = [];
    foreach ($data as $value) {
        $filtered[$value['build']['id']] = $value['source']['revision'];
    }

    $data = $filtered;

    // We don't cache today because we may miss the second nightly build
    if ($date != date('Ymd')) {
        Cache::setKey($cache_id, $data);
    }
}

// Just in case we have duplicates
return array_unique($data);
