<?php

declare(strict_types=1);

use ReleaseInsights\Json;

$json = include MODELS . 'api/firefox_releases.php';

(new Json($json))->render();
