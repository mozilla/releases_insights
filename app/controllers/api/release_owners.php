<?php

use ReleaseInsights\Utils;

// our Json view outputs data stored in the $json variable
$json = include MODELS . 'api/release_owners.php';

require_once VIEWS . 'json.php';
