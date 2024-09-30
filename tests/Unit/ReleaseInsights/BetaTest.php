<?php

declare(strict_types=1);

use ReleaseInsights\Beta;

test('Beta->getLogEndpoints()', function () {
    $obj = new Beta();
    expect($obj->getLogEndpoints())
        ->toHaveKeys(['94.0b1', '94.0b2'])
        ->each->toBeString();
});

test('Beta->getCrashes()', function () {
    $obj = new Beta();
    expect($obj->getCrashes())
        ->toBeArray()
        ->toHaveKeys(['summary', '131.0b1']);
});
