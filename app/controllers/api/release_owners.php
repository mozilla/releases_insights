<?php

declare(strict_types=1);

use ReleaseInsights\Json;

$json = include MODELS . 'api/release_owners.php';

(new Json($json))->render();
