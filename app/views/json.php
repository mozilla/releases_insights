<?php

declare(strict_types=1);

use Json\Json;

// This view outputs a JSON or JSONP representation of search results
$json_data = new Json();

echo $json_data->outputContent(
    $json,
    $_GET['callback'] ?? false
);
