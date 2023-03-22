<?php

declare(strict_types=1);

use ReleaseInsights\Release;

test('Release->getSchedule()', function () {
    $obj = new Release('102.0');
    expect($obj->getSchedule(TEST_FILES))
        ->toBeArray();
    $obj = new Release('110.0');
    expect($obj->getSchedule(TEST_FILES))
        ->toBeArray();
    $obj = new Release('97.0');
    expect($obj->getSchedule(TEST_FILES))
        ->toBeArray();
    $obj = new Release('error');
    expect($obj->getSchedule(TEST_FILES))
        ->toBeArray();
    $obj = new Release('15.0'); // Beta is 14.0 that we didn't ship, fall back to 14.0.1
    expect($obj->getSchedule(TEST_FILES))
        ->toHaveKeys(['version', 'nightly_start', 'soft_code_freeze', 'string_freeze', 'merge_day', 'beta_1', 'beta_2', 'beta_3', 'sumo_1', 'beta_4', 'beta_5', 'beta_6', 'beta_7', 'sumo_2', 'beta_8', 'beta_9', 'rc_gtb', 'rc', 'release', 'planned_dot_release',]);
    $obj = new Release('16.0'); // Nightly is 14.0 that we didn't ship, fall back to 14.0.1
    expect($obj->getSchedule(TEST_FILES))
        ->toHaveKeys(['version', 'nightly_start', 'soft_code_freeze', 'string_freeze', 'merge_day', 'beta_1', 'beta_2', 'beta_3', 'sumo_1', 'beta_4', 'beta_5', 'beta_6', 'beta_7', 'sumo_2', 'beta_8', 'beta_9', 'rc_gtb', 'rc', 'release', 'planned_dot_release',]);
    $obj = new Release('110.0');
    expect($obj->getSchedule(TEST_FILES))
        ->toHaveKeys(['version', 'nightly_start', 'soft_code_freeze', 'string_freeze', 'merge_day', 'beta_1', 'beta_2', 'beta_3', 'sumo_1', 'beta_4', 'beta_5', 'beta_6', 'beta_7', 'sumo_2', 'beta_8', 'beta_9', 'rc_gtb', 'rc', 'release', 'planned_dot_release',]);
    $obj = new Release('111.0');
    expect($obj->getSchedule(TEST_FILES))
        ->not->toHaveKeys(['beta_9']);
    $obj = new Release('112.0');
    expect($obj->getSchedule(TEST_FILES)['nightly_start'])
        ->toBe('2023-02-13 00:00:00+00:00');
    $obj = new Release('116.0'); // merge day is Tuesday and we have one beta left
    expect($obj->getSchedule(TEST_FILES))
        ->toHaveKeys(['version', 'nightly_start', 'soft_code_freeze', 'string_freeze', 'merge_day', 'beta_1', 'beta_2', 'beta_3', 'sumo_1', 'beta_4', 'beta_5', 'beta_6', 'beta_7', 'sumo_2', 'beta_8', 'rc_gtb', 'rc', 'release', 'planned_dot_release',]);
});

test('Release->getNiceLabel()', function () {
    expect(Release::getNiceLabel('103', 'soft_code_freeze'))
        ->toEqual('103 soft Code Freeze starts at 08:00 UTC');
    expect(Release::getNiceLabel('104', 'release'))
        ->toEqual('104 Release');
    expect(Release::getNiceLabel('104', 'release', false))
        ->toEqual('Firefox 104 go-live @ 6AM PT');
});
