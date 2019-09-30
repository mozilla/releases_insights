<?php

$data = file_get_contents(
    'https://buildhub.moz.tools/api/search',
    false,
    stream_context_create($options)
);

// Extract into an array the values we want from the data source
$data = json_decode($data, true);
$data = array_column($data['hits']['hits'], '_source');

// Build a [buildid => revision] array
$filtered = [];
foreach($data as $value) {
    $filtered[$value['build']['id']] = $value['source']['revision'];
}

// Just in case we have duplicates
return array_unique($filtered);

