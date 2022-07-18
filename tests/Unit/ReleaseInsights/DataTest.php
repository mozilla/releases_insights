<?php

declare(strict_types=1);

use ReleaseInsights\Data;

test('Data->getOwners()', function () {
    $obj = new Data();
    expect($obj->getOwners())
        ->toBeArray()
        ->toHaveKeys(['1.0', '108.0'])
        ->toContain(
            'Not documented',
            'Pascal Chevrel',
            'Ryan VanderMeulen',
            'Julien Cristau',
            'Liz Henry',
            'Ritu Khotari',
            'Sylvestre Ledru',
            'Gerry Chang',
            'Bhavana Bajaj',
            'Lukas Blakk',
            'Lawrence Mandel',
            'Dianna Smith',
            'Donal Meehan'
        )
        ->each->toBeString();
});

test('Data->getFutureReleases()', function () {
    $obj = new Data();
    expect($obj->getFutureReleases())
        ->toBeArray();
});

test('Data->getFirefoxVersions()', function () {
    $obj = new Data(TEST_FILES);
    expect($obj->getFirefoxVersions())
        ->toBeArray();
});