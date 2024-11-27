<?php

declare(strict_types=1);

use ReleaseInsights\{Json, Model};

new Json(
    new Model('api_release_duty')->get()
)->render();
