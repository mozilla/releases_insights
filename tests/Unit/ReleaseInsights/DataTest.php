<?php

declare(strict_types=1);

use ReleaseInsights\Data;

test('Data->getOwners()', function () {
    $obj = new Data();
    expect($obj->getOwners())
        ->toBeArray()
        ->toHaveKeys(['1.0', '108.0'])
        ->toContain(
            'Basil Hashem',
            'Mike Beltzner',
            'Alex Keybl',
            'Christian Legnitto',
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

test('Data->getESRReleases()', function () {
    $obj = new Data(TEST_FILES);
    expect($obj->getESRReleases())
        ->toBeArray();
    expect($obj->getESRReleases()['102.0.1'])
        ->toBe('2022-07-13');
});

test('Data->getLatestMajorRelease()', function () {
    $obj = new Data(TEST_FILES);
    expect($obj->getLatestMajorRelease())
        ->toBe(['102.0' => '2022-06-28']);
});

test('Data->getPastReleases()', function () {
    $obj = new Data(TEST_FILES);
    expect($obj->getPastReleases())
        ->toBeArray();
    expect($obj->getPastReleases()['3.6'])
        ->toBe('2010-01-21');
    expect($obj->getPastReleases()['102.0.1'])
        ->toBe('2022-07-13');
});

test('Data->getPastBetas()', function () {
    $obj = new Data(TEST_FILES);
    expect($obj->getPastBetas())
        ->toBeArray();
    expect($obj->getPastBetas()['22.0b3'])
        ->toBe('2013-05-30');
});

test('Data->getMajorPastReleases()', function () {
    $obj = new Data(TEST_FILES);
    expect($obj->getMajorPastReleases())
        ->toBeArray();
});

test('Data->getMajorReleases()', function () {
    $obj = new Data(TEST_FILES);
    expect($obj->getMajorReleases())
        ->toBeArray();
});

test('Data->isTodayReleaseDay()', function () {
    $obj = new Data(TEST_FILES);
    expect($obj->isTodayReleaseDay())
        ->toBeBool();
});