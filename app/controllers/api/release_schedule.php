<?php

declare(strict_types=1);

use ReleaseInsights\{Json, Model};

$data = (new Model('api_release_schedule'))->get();

(new Json($data))->render();
