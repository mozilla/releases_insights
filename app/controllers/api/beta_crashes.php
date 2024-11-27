<?php

declare(strict_types=1);

use ReleaseInsights\{Json, Model};

new Json(
    new Model('api_beta_crashes')->get()
)->render();
