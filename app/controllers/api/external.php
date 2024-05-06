<?php

declare(strict_types=1);

use ReleaseInsights\Json;

$json = include MODELS . 'api/external.php';

(new Json($json))->render();
