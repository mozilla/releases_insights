<?php

declare(strict_types=1);

use ReleaseInsights\Json;

$json = include MODELS . 'api/nightly_crashes.php';

(new Json($json))->render();
