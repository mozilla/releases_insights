<?php

declare(strict_types=1);

use ReleaseInsights\{Json, Model, Utils};


$data = new Model('api_future_calendar')->get();
$output_format = 'json';

if (isset($_GET['format']) && $_GET['format'] != null) {
    $output_format = Utils::secureText($_GET['format']);
}


if ($output_format == 'csv' || $output_format == 'text') {
    if ($output_format == 'csv') {
        header("Content-type: text/csv");
    } else {
        header("Content-type: text/plain");
    }

    $csv_headers = ['Version', 'Nightly Start', 'Beta', 'Release Date', 'Release Owner'];
    echo implode(',', $csv_headers) . "\n";
    foreach ($data as $key => $values) {
        $row = [
            $key,
            new DateTime($values['nightly_start'])->format('Y-m-d'),
            new DateTime($values['beta_start'])->format('Y-m-d'),
            $values['release_date'],
            $values['owner'],
        ];
        echo implode(',', $row) . "\n";
    }
} else {
    new Json($data)->render();
}