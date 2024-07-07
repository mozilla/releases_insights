<?php

declare(strict_types=1);

use ReleaseInsights\{Json, Model};

$data = (new Model('api_external'))->get();

(new Json($data))->render();
