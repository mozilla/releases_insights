<?php

declare(strict_types=1);

use Json\Json;

// This view outputs a JSON or JSONP representation of search results
$json_data = new Json();

if (array_key_exists('error',$json)) {
    echo $json_data->outputError($json['error']);
} else {
    echo $json_data->outputContent(
        $json,
        $_GET['callback'] ?? false
    );
}
