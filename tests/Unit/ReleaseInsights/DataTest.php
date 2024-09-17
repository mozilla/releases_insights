<?php

declare(strict_types=1);

use ReleaseInsights\Data;
use ReleaseInsights\URL;

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

test('Data->getWellnessDays()', function () {
    $obj = new Data();
    expect($obj->getWellnessDays())
        ->toBeArray()

        ->each->toBeString();
});

test('Data->getReleaseDuty()', function () {
    $obj = new Data();
    expect($obj->getReleaseDuty())
        ->toBeArray()
        ->toHaveKeys(['1.0', '108.0'])
        ->each->toBeArray();
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

test('Data->getDesktopPastReleases()', function () {
    $obj = new Data(TEST_FILES);
    expect($obj->getDesktopPastReleases())
        ->toBeArray();
    expect($obj->getDesktopPastReleases()['3.6'])
        ->toBe('2010-01-21');
    expect($obj->getDesktopPastReleases()['102.0.1'])
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

test('Data->getDotReleases()', function () {
    $obj = new Data(TEST_FILES);
    expect($obj->getDotReleases())
        ->toBeArray()
        ->toHaveKeys(['128.0.1', '128.0.2']);

    expect($obj->getDotReleases()['128.0.2'])
        ->toBeArray()
        ->toHaveKeys(['date', 'platform']);

    expect($obj->getDotReleases()['128.0.1'])
        ->toBe(['date' => '2024-07-16', 'platform' => 'android']);

    expect($obj->getDotReleases()['128.0.2'])
        ->toBe(['date' => '2024-07-23', 'platform' => 'both']);
});


test('Data->isTodayReleaseDay()', function () {
    $obj = new Data(TEST_FILES);
    expect($obj->isTodayReleaseDay())
        ->toBeBool();
});



test('Data->getDesktopAdoptionRate()', function () {
    $obj = new Data(TEST_FILES);
    expect($obj->getDesktopAdoptionRate('130.0'))
        ->toBe(81)
        ->toBeInt();
});


