<?php

declare(strict_types=1);

use ReleaseInsights\Json;

$json = include MODELS . 'api/esr_releases.php';

(new Json($json))->render();
