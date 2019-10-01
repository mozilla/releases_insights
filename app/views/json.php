<?php

use Json\Json;

// This view outputs a JSON or JSONP representation of search results

$json_data = new Json();

echo $json_data->outputContent(
    $json,
    isset($_GET['callback']) ? $_GET['callback'] : false
);
