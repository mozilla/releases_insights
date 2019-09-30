<?php
namespace ReleaseInsights;

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

$json = include MODELS . 'api_nightly.php';
require_once VIEWS . 'json.php';
