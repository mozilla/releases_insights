<?php

declare(strict_types=1);

use ReleaseInsights\Release;

test('Release->getSchedule()', function () {
    $obj = new Release('102.0', TEST_FILES);
    expect($obj->getSchedule())
        ->toBeArray();

    $obj = new Release('110.0');
    expect($obj->getSchedule())
        ->toBeArray();

    $obj = new Release('97.0');
    expect($obj->getSchedule())
        ->toBeArray();

    $obj = new Release('1200');
    expect($obj->getSchedule())
        ->toBeArray()
        ->toHaveKey('error');

    $obj = new Release('-1');
    expect($obj->getSchedule())
        ->toBeArray()
        ->toHaveKey('error');

    $obj = new Release('15.0'); // Beta is 14.0 that we didn't ship, fall back to 14.0.1
    expect($obj->getSchedule())
        ->toHaveKeys(['version', 'nightly_start', 'beta_1', 'beta_2', 'beta_3', 'beta_4', 'beta_5', 'beta_6', 'release',]);

    $obj = new Release('16.0'); // Nightly is 14.0 that we didn't ship, fall back to 14.0.1
    expect($obj->getSchedule())
        ->toHaveKeys(['version', 'nightly_start', 'beta_1', 'beta_2', 'beta_3', 'beta_4', 'beta_5', 'beta_6', 'release',]);

    $obj = new Release('110.0');
    expect($obj->getSchedule())
        ->toHaveKeys(['version', 'nightly_start', 'soft_code_freeze', 'string_freeze', 'merge_day', 'beta_1', 'beta_2', 'beta_3', 'sumo_1', 'beta_4', 'beta_5', 'beta_6', 'beta_7', 'sumo_2', 'beta_8', 'beta_9', 'rc_gtb', 'rc', 'release', 'planned_dot_release',]);

    $obj = new Release('112.0');
    expect($obj->getSchedule()['nightly_start'])
        ->toBe('2023-02-13 00:00:00+00:00');

    $obj = new Release('116.0'); // merge day is Tuesday and we have one beta left
    expect($obj->getSchedule())
        ->toHaveKeys(['version', 'nightly_start', 'soft_code_freeze', 'string_freeze', 'merge_day', 'beta_1', 'beta_2', 'beta_3', 'sumo_1', 'beta_4', 'beta_5', 'beta_6', 'beta_7', 'sumo_2', 'beta_8', 'rc_gtb', 'rc', 'release', 'planned_dot_release', 'qa_request_deadline', 'qa_test_plan_due', 'qa_feature_done', 'qa_pre_merge_done', 'qa_pre_rc_signoff']);

    $obj = new Release('141.0');
    expect($obj->getSchedule()['qa_feature_done'])
        ->toBe("2025-06-06 21:00:00+00:00");

    $obj = new Release('146.0');
    expect($obj->getSchedule()['planned_dot_release'])
        ->toBe("2025-12-18 00:00:00+00:00");
    expect($obj->getSchedule()['rc_gtb'])
        ->toBe("2025-12-01 21:00:00+00:00");

    $obj = new Release('147.0');
    expect($obj->getSchedule()['rc_gtb'])
        ->toBe("2026-01-05 21:00:00+00:00");

    $obj = new Release('148.0');
    expect($obj->getSchedule()['qa_feature_done'])
        ->toBe("2025-12-26 21:00:00+00:00");
    expect($obj->getSchedule())
        ->toHaveKeys(['beta_10', 'beta_11', 'beta_12']);
    expect($obj->getSchedule()['beta_10'])
        ->toBe("2026-02-02 00:00:00+00:00");
    expect($obj->getSchedule()['beta_11'])
        ->toBe("2026-02-04 00:00:00+00:00");
    expect($obj->getSchedule()['beta_12'])
        ->toBe("2026-02-06 00:00:00+00:00");

    $obj = new Release('153.0');
        expect($obj->getSchedule())
        ->toHaveKeys(['beta_10', 'beta_11', 'beta_12']);
        expect($obj->getSchedule()['beta_10'])
            ->toBe("2026-06-29 00:00:00+00:00");
        expect($obj->getSchedule()['beta_11'])
            ->toBe("2026-07-01 00:00:00+00:00");
        expect($obj->getSchedule()['beta_12'])
            ->toBe("2026-07-03 00:00:00+00:00");

    $obj = new Release('154.0');
    expect($obj->getSchedule()['qa_feature_done'])
        ->toBe("2026-06-26 21:00:00+00:00");

    $obj = new Release('158.0');
    expect($obj->getSchedule()['planned_dot_release'])
        ->toBe("2026-12-02 00:00:00+00:00");

    $obj = new Release('159.0');
    expect($obj->getSchedule()['rc_gtb'])
        ->toBe("2027-01-04 21:00:00+00:00");
});

test('Release->getNiceLabel()', function () {
    expect(Release::getNiceLabel('103', 'soft_code_freeze'))
        ->toEqual('103 soft Code Freeze starts at 08:00 UTC');
    expect(Release::getNiceLabel('104', 'release'))
        ->toEqual('104 Release');
    expect(Release::getNiceLabel('104', 'release', false))
        ->toEqual('Firefox 104 go-live @ 6AM PT');
});