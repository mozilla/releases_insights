<?php

declare(strict_types=1);

use ReleaseInsights\{Json, Model};

$data = (new Model('api_wellness_days'))->get();

(new Json($data))->render();
