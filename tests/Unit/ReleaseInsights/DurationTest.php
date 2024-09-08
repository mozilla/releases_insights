<?php

declare(strict_types=1);

use ReleaseInsights\Duration;

test('Duration->days()', function () {
    $obj = new Duration(new DateTime('2023-11-01'), new DateTime('2023-11-03'));
    expect($obj->days())
        ->toBe(2);
});

test('Duration->weeks()', function () {
    $obj = new Duration(new DateTime('2023-11-01'), new DateTime('2023-12-01'));
    expect($obj->weeks())
        ->toBe(4.0);

    $obj = new Duration(new DateTime('2023-11-01'), new DateTime('2023-11-28'));
    expect($obj->weeks())
        ->toBe(3.5);
});

test('Duration->isWorkDay()', function () {
    $obj = new Duration(new DateTime('2023-11-01'), new DateTime('2023-12-01'));
    expect($obj->isWorkDay(new DateTime('2023-11-01'))) // Working day
        ->toBeTrue();
    expect($obj->isWorkDay(new DateTime('2023-11-11'))) // Saturday
        ->toBeFalse();
    expect($obj->isWorkDay(new DateTime('2023-11-12'))) // Sunday
        ->toBeFalse();
    expect($obj->isWorkDay(new DateTime('2024-02-16'))) // Wellness day
        ->toBeFalse();
    expect($obj->isWorkDay(new DateTime())) // Today
        ->toBeFalse();
});

test('Duration->workDays()', function () {
    $obj = new Duration(new DateTime('2023-11-01'), new DateTime('2023-12-01'));
    expect($obj->workDays())
        ->toBe(22);
});

test('Duration->report()', function () {
    $obj = new Duration(new DateTime('2023-11-01'), new DateTime('2023-12-01'));
    expect($obj->report())
        ->toHaveCount(3);
});
