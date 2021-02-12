<?php

use ReleaseInsights\Utils;

$json = include MODELS . 'api/release_owners.php';

require_once VIEWS . 'json.php';
