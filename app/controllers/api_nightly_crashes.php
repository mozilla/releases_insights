<?php

// our Json view outputs data stored in the $json variable
$json = include MODELS . 'api_nightly_crashes.php';
require_once VIEWS . 'json.php';
