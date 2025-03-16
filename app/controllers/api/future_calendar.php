<?php

declare(strict_types=1);

use ReleaseInsights\{Json, Model};

// We want to send simplified Json data for our public API
$data = [];
foreach (new Model('api_future_calendar')->get() as $key => $values) {
    $data[$key] = $values['release_date'];
}

new Json($data)->render();