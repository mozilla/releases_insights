<?php

declare(strict_types=1);

use ReleaseInsights\Release;

test('Release->getSchedule()', function () {
    $obj = new Release('102.0');
    expect($obj->getSchedule())
        ->toBeArray();
    $obj = new Release('110.0');
    expect($obj->getSchedule())
        ->toBeArray();
    $obj = new Release('97.0');
    expect($obj->getSchedule())
        ->toBeArray();
    $obj = new Release('error');
    expect($obj->getSchedule())
        ->toBeArray();
    $obj = new Release('110.0');
    expect($obj->getSchedule())
        ->toHaveKeys(['version', 'nightly_start', 'soft_code_freeze', 'string_freeze', 'merge_day', 'beta_1', 'beta_2', 'beta_3', 'sumo_1', 'beta_4', 'beta_5', 'beta_6', 'beta_7', 'sumo_2', 'beta_8', 'beta_9', 'rc_gtb', 'rc', 'release', 'planned_dot_release',]);
});

test('Release->getNiceLabel()', function () {
    expect(Release::getNiceLabel('103', 'soft_code_freeze'))
        ->toEqual('103 soft Code Freeze starts at 08:00 UTC');
    expect(Release::getNiceLabel('104', 'release'))
        ->toEqual('104 Release');
    expect(Release::getNiceLabel('104', 'release', false))
        ->toEqual('Firefox 104 go-live @ 6AM PT');
});
