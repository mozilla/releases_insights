<?php

declare(strict_types=1);

use ReleaseInsights\Json;

$json = include MODELS . 'api/nightly.php';

// We want to send a simplified Json for our public API
$json_for_api = [];

foreach ($json as $key => $values) {
    $json_for_api[$key] = $values['revision'];
}

(new Json($json_for_api))->render();