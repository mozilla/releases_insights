<?php

declare(strict_types=1);

use ReleaseInsights\{Json, Model};

$data = (new Model('api_esr_releases'))->get();

(new Json($data))->render();
