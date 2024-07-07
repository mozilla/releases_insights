<?php

declare(strict_types=1);

use ReleaseInsights\{Json, Model};

$data = (new Model('api_nightly_crashes'))->get();

(new Json($data))->render();
