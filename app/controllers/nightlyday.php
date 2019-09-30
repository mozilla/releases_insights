<?php
/*
    Proof of concept.
    PHP script which queries BuildHub for a date (https://buildhub.moz.tools)
    and returns a json array with the Firefox Nightly build IDs for this day
    as keys and the mercurial revision they were built from as values.

    Documention and other scripts on querying buildhub:
    - https://buildhub2.readthedocs.io/en/latest/user.html#example-is-this-an-official-build-id
    - https://github.com/mozilla/crash-stop-addon/blob/master/crashstop/buildhub.py#L183
    - https://github.com/mozilla/pushlog-addon/blob/master/content.js#L120
    - https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html
    - https://www.compose.com/articles/using-query-string-queries-in-elasticsearch/

    Use:
    http://localhost/?date=20190707
*/

/*
    These are the options we send to the JSON API endpoint.
    We send a query in JSON format and receive JSON as well.
    The API only works with POST requests.
*/
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
            { "regexp": { "build.id": "' . ($_GET['date'] ?? '20190101') . '.*" }}
          ]
        }
      }
    }'
    ]
];

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
$filtered = array_unique($filtered);

header('Content-Type:application/json');
print json_encode($filtered);
exit;
