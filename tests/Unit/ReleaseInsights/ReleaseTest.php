<?php

declare(strict_types=1);

use ReleaseInsights\Release;

test('Release->getSchedule()', function () {
    $obj = new Release('102.0');
    expect($obj->getSchedule())
        ->toBeArray();
    $obj = new Release('error');
    expect($obj->getSchedule())
        ->toBeArray();
});
