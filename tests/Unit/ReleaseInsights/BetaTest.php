<?php

declare(strict_types=1);

use ReleaseInsights\Beta;

test('Beta->number_betas', function () {
    $obj = new Beta(141);
    expect($obj->number_betas)
        ->toBeInt()
        ->toBe(9); // Make sure that we have 9 betas by default in a cycle
});

test('Beta->getLogEndpoints()', function () {
    $obj = new Beta();
    expect($obj->getLogEndpoints())
        ->toHaveKeys(['146.0b1', '146.0b2'])
        ->each->toBeString();
});

test('Beta->crashes()', function () {
    $obj = new Beta();
    expect($obj->crashes())
        ->toBeArray()
        ->toHaveKeys(['summary', '131.0b1']);
});
