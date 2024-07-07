<?php

declare(strict_types=1);

use ReleaseInsights\{Json, Model};

$data = (new Model('api_firefox_releases'))->get();

(new Json($data))->render();
