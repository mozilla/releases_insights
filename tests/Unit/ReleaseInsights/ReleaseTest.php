<?php

declare(strict_types=1);

use ReleaseInsights\Release;

test('Release->getSchedule()', function () {
    $obj = new Release('102.0', TEST_FILES);
    expect($obj->getSchedule())->toBeArray();

    $obj = new Release('110.0');
    expect($obj->getSchedule())->toBeArray();

    $obj = new Release('97.0');
    expect($obj->getSchedule())->toBeArray();

    $obj = new Release('1200');
    expect($obj->getSchedule())
        ->toBeArray()
        ->toHaveKey('error');

    $obj = new Release('-1');
    expect($obj->getSchedule())
        ->toBeArray()
        ->toHaveKey('error');

    $obj = new Release('15.0'); // Beta is 14.0 that we didn't ship, fall back to 14.0.1
    expect($obj->getSchedule())->toHaveKeys(['version', 'nightly_start', 'beta_1', 'beta_2',
        'beta_3', 'beta_4', 'beta_5', 'beta_6', 'release',]);

    $obj = new Release('16.0'); // Nightly is 14.0 that we didn't ship, fall back to 14.0.1
    expect($obj->getSchedule())->toHaveKeys(['version', 'nightly_start', 'beta_1', 'beta_2',
        'beta_3', 'beta_4', 'beta_5', 'beta_6', 'release',]);

    $obj = new Release('146.0');
    expect($obj->getSchedule())
        ->toHaveKeys(['version', 'nightly_start', 'string_freeze', 'merge_day',
            'beta_1', 'beta_2', 'beta_3', 'sumo_1', 'beta_4', 'beta_5', 'beta_6', 'beta_7', 'sumo_2',
            'beta_8', 'beta_9', 'rc_gtb', 'rc', 'release', 'planned_dot_release', 'mobile_dot_release']);

    $obj = new Release('112.0');
    expect($obj->getSchedule()['nightly_start'])->toBe('2023-02-14 00:00:00+00:00');

    $obj = new Release('146.0'); // merge day is Tuesday and we have one beta left
    expect($obj->getSchedule()) // future release
        ->toHaveKeys(['version', 'nightly_start', 'string_freeze', 'merge_day',
            'beta_1', 'beta_2', 'beta_3', 'sumo_1', 'beta_4', 'beta_5', 'beta_6', 'beta_7', 'sumo_2',
            'beta_8', 'rc_gtb', 'rc', 'release', 'planned_dot_release', 'qa_request_deadline', 'qa_test_plan_due',
            'qa_feature_done', 'qa_pre_merge_done', 'qa_pre_rc_signoff']);

    $obj = new Release('146.0'); // future release
    expect($obj->getSchedule()['qa_feature_done'])->toBe("2025-10-24 21:00:00+00:00");

    // Bug 1999793 - Accessibility Review deadline should match QA deadline - https://bugzil.la/1999793
    $obj = new Release('146.0')->getSchedule();
    expect($obj['a11y_request_deadline'])->toEqual($obj['qa_request_deadline']);

    $obj = new Release('146.0');
    expect($obj->getSchedule()['planned_dot_release'])->toBe("2025-12-18 00:00:00+00:00");

    $obj = new Release('149.0');
    expect($obj->getSchedule()['rc_gtb'])->toBe("2026-03-18 17:00:00+00:00");

    $obj = new Release('148.0');
    expect($obj->getSchedule())->toHaveKeys(['beta_10', 'beta_11', 'beta_12']);
    expect($obj->getSchedule()['beta_10'])->toBe("2026-02-02 00:00:00+00:00");
    expect($obj->getSchedule()['beta_11'])->toBe("2026-02-04 00:00:00+00:00");
    expect($obj->getSchedule()['beta_12'])->toBe("2026-02-06 00:00:00+00:00");
    expect($obj->getSchedule()['qa_feature_done'])->toBe("2025-12-19 21:00:00+00:00");

    $obj = new Release('149.0');
    expect($obj->getSchedule()['qa_feature_done'])->toBe("2026-02-06 21:00:00+00:00");

    $obj = new Release('153.0');
    expect($obj->getSchedule())->toHaveKeys(['beta_11', 'beta_12', 'beta_13']);
    expect($obj->getSchedule()['beta_11'])->toBe("2026-07-10 00:00:00+00:00");
    expect($obj->getSchedule()['beta_12'])->toBe("2026-07-13 00:00:00+00:00");

    $obj = new Release('154.0');
    expect($obj->getSchedule()['qa_feature_done'])->toBe("2026-07-03 21:00:00+00:00");

    $Ymd = fn($date) => (new DateTime($date))->format('Y-m-d');
    $obj = new Release('159.0');
    expect($obj->getSchedule())->toHaveKeys(['beta_11', 'beta_12']);
    expect($Ymd($obj->getSchedule()['rc_gtb']))->toBe("2027-01-13");

});

test('Release->getSchedule(): Milestones are in the right order', function () {
    $sched = new Release('159.0')->getSchedule();
    unset($sched['version']); // not a date string
    $sched = array_map(fn($date) => new DateTime($date), $sched);
    expect($sched['qa_request_deadline'])->toEqual($sched['a11y_request_deadline']);
    expect($sched['a11y_request_deadline'])->toBeLessThan($sched['nightly_start']);
    expect($sched['nightly_start'])->toBeLessThan($sched['qa_feature_done']);
    expect($sched['qa_feature_done'])->toEqual($sched['qa_test_plan_due']);
    expect($sched['qa_test_plan_due'])->toBeLessThan($sched['relnotes_beta_ready']);
    expect($sched['relnotes_beta_ready'])->toBeLessThan($sched['string_freeze']);
    expect($sched['string_freeze'])->toBeLessThan($sched['qa_pre_merge_done']);
    expect($sched['qa_pre_merge_done'])->toBeLessThan($sched['merge_day']);
    expect($sched['merge_day'])->toBeLessThan($sched['beta_1']);
    expect($sched['beta_1'])->toBeLessThan($sched['beta_2']);
    expect($sched['beta_2'])->toBeLessThan($sched['beta_3']);
    expect($sched['sumo_1'])->toBeLessThan($sched['beta_3']);
    expect($sched['beta_3'])->toBeLessThan($sched['beta_4']);
    expect($sched['beta_4'])->toBeLessThan($sched['beta_5']);
    expect($sched['beta_5'])->toBeLessThan($sched['beta_6']);
    expect($sched['beta_6'])->toBeLessThan($sched['beta_7']);
    expect($sched['beta_7'])->toBeLessThan($sched['beta_8']);
    expect($sched['beta_8'])->toBeLessThan($sched['beta_9']);
    expect($sched['beta_9'])->toBeLessThan($sched['qa_pre_rc_signoff']);
    expect($sched['qa_pre_rc_signoff'])->toBeLessThan($sched['beta_10']);
    expect($sched['beta_10'])->toBeLessThan($sched['rc_gtb']);
    expect($sched['relnotes_deadline'])->toBeLessThan($sched['rc_gtb']);
    expect($sched['rc_gtb'])->toBeLessThan($sched['rc']);
    expect($sched['rc'])->toBeLessThan($sched['release']);
    expect($sched['release'])->toBeLessThan($sched['mobile_dot_release']);
    expect($sched['mobile_dot_release'])->toBeLessThan($sched['planned_dot_release']);
    expect($sched['planned_dot_release'])->toBeGreaterThan($sched['mobile_dot_release']);
});

test('Release->getNiceLabel()', function () {
    expect(Release::getNiceLabel('104', 'release'))->toEqual('<b>104 Release</b>');
    expect(Release::getNiceLabel('104', 'release', false))->toEqual('Firefox 104 go-live @ 6AM PT');
});