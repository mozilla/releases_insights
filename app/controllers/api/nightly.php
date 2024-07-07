<?php

declare(strict_types=1);

use ReleaseInsights\{Json, Model};

$data = (new Model('api_nightly'))->get();

// We want to send a simplified Json for our public API
$data_for_api = [];
foreach ($data as $key => $values) {
    $data_for_api[$key] = $values['revision'];
}

(new Json($data_for_api))->render();
