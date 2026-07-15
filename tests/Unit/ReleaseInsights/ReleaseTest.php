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

    // 146 is a shipped release; exercise the legacy 4-week future schedule directly
    $obj = new Release('146.0');
    expect($obj->getFutureSchedule())
        ->toHaveKeys(['version', 'nightly_start', 'string_freeze', 'merge_day',
            'beta_1', 'beta_2', 'beta_3', 'sumo_1', 'beta_4', 'beta_5', 'beta_6', 'beta_7', 'beta_8',
            'beta_9', 'rc_gtb', 'release', 'dot_release_1', 'dot_release_2', 'dot_release_3',]);

    $obj = new Release('112.0');
    expect($obj->getSchedule()['nightly_start'])->toBe('2023-02-14 00:00:00+00:00');

    $obj = new Release('146.0');
    expect($obj->getFutureSchedule())
        ->toHaveKeys(['version', 'nightly_start', 'string_freeze', 'merge_day',
            'beta_1', 'beta_2', 'beta_3', 'sumo_1', 'beta_4', 'beta_5', 'beta_6', 'beta_7',
            'beta_8', 'rc_gtb', 'release', 'qa_request_deadline', 'qa_test_plan_due',
            'qa_feature_done', 'qa_pre_merge_done', 'qa_pre_rc_signoff']);

    $obj = new Release('146.0');
    expect($obj->getFutureSchedule()['qa_feature_done'])->toBe("2025-10-24 21:00:00+00:00");

    // Bug 1999793 - Accessibility Review deadline should match QA deadline - https://bugzil.la/1999793
    $obj = new Release('146.0')->getFutureSchedule();
    expect($obj['a11y_request_deadline'])->toEqual($obj['qa_request_deadline']);

    $obj = new Release('149.0');
    expect($obj->getFutureSchedule()['rc_gtb'])->toBe("2026-03-18 17:00:00+00:00");

    $obj = new Release('149.0');
    expect($obj->getFutureSchedule()['qa_feature_done'])->toBe("2026-02-06 21:00:00+00:00");

    $obj = new Release('150.0');
    expect($obj->getFutureSchedule()['dot_release_2'])->toBe("2026-05-07 15:00:00+00:00");
    expect($obj->getPastSchedule()['dot_release_2'])->toBe("2026-05-07 15:00:00+00:00");

    $obj = new Release('152.0');
    expect($obj->getFutureSchedule())->toHaveKey('dot_release_4');

    $obj = new Release('153.0');
    expect($obj->getSchedule())->toHaveKeys(['beta_11', 'beta_12', 'beta_13']);
    expect($obj->getSchedule()['beta_11'])->toBe("2026-07-10 00:00:00+00:00");
    expect($obj->getSchedule()['beta_12'])->toBe("2026-07-13 00:00:00+00:00");

    $obj = new Release('154.0');
    expect($obj->getSchedule()['qa_feature_done'])->toBe("2026-07-03 21:00:00+00:00");

    // From Firefox 160 the regular 2-week release cycle is in effect (155 is the
    // transition release and 163/164 are year-boundary special cases, tested
    // separately below). See getTwoWeekSchedule().
    $obj = new Release('160.0');
    expect($obj->getSchedule())
        ->toHaveKeys(['version', 'qa_request_deadline', 'a11y_request_deadline', 'nightly_start',
            'qa_feature_done', 'qa_test_plan_due', 'strings_handoff', 'relnotes_beta_ready',
            'qa_nightly_signoff', 'string_freeze', 'merge_day', 'beta_1', 'beta_2', 'sumo_1',
            'beta_3', 'beta_4', 'beta_5', 'relnotes_deadline', 'rc_gtb',
            'release', 'dot_release_1'])
        // A single planned dot release, no early beta and no more than 5 betas.
        // The 2-week cycle drops the legacy pre-merge / pre-RC QA milestones.
        ->not->toHaveKeys(['beta_6', 'beta_7', 'beta_8', 'beta_9', 'beta_10',
            'dot_release_2', 'dot_release_3', 'dot_release_4',
            'qa_pre_merge_done', 'qa_pre_rc_signoff']);

    // In a regular 2-week cycle the manual QA request deadline falls a week before
    // the Nightly cycle starts, while the a11y review deadline stays on day one.
    $sched = new Release('160.0')->getSchedule();
    expect($sched['a11y_request_deadline'])->toEqual($sched['nightly_start']);
    expect($sched['qa_request_deadline'])->toBeLessThan($sched['nightly_start']);

    // Development never stops: each Nightly cycle opens on the previous version's
    // merge day, so cycles are back-to-back with no gap (dates compared, times differ).
    $merge_date = fn(string $v) => substr(new Release($v)->getSchedule()['merge_day'], 0, 10);
    $nightly_date = fn(string $v) => substr(new Release($v)->getSchedule()['nightly_start'], 0, 10);
    expect($nightly_date('156.0'))->toBe($merge_date('155.0')); // chains off 155's transition merge
    expect($nightly_date('160.0'))->toBe($merge_date('159.0')); // regular
    expect($nightly_date('164.0'))->toBe($merge_date('163.0')); // chains off 163's early year-end merge

    // 156 chains off 155's Aug 13 merge -> a regular 2-week Nightly to its Aug 27 merge.
    expect(new Release('156.0')->getSchedule()['nightly_start'])->toBe("2026-08-13 00:00:00+00:00");

    // 164 chains off 163's early Dec 3 year-end merge -> long (~5-week) Nightly over
    // the holidays, then a normal 2-week Beta to the Jan 26 release.
    $sched = new Release('164.0')->getSchedule();
    expect($sched['nightly_start'])->toBe("2026-12-03 00:00:00+00:00");
    expect($sched['merge_day'])->toBe("2027-01-07 16:00:00+00:00");
    expect($sched['release'])->toBe("2027-01-26 14:00:00+00:00");

    // Firefox 155 is the transition release: a long (~4-week) Nightly then a
    // regular 2-week Beta with the full Mon/Wed/Fri cadence (5 betas before the RC).
    $sched = new Release('155.0')->getSchedule();
    expect($sched['nightly_start'])->toBe("2026-07-20 00:00:00+00:00");
    expect($sched['merge_day'])->toBe("2026-08-13 16:00:00+00:00");
    expect($sched['qa_nightly_signoff'])->toBe("2026-08-13 14:00:00+00:00");
    expect($sched['beta_1'])->toBe("2026-08-17 13:00:00+00:00");
    expect($sched['beta_4'])->toBe("2026-08-24 13:00:00+00:00");
    expect($sched['beta_5'])->toBe("2026-08-26 13:00:00+00:00");
    // Overrides: feature-complete stays around the W2 mark, and QA request,
    // a11y review and nightly start share day one.
    expect($sched['qa_feature_done'])->toBe("2026-08-04 21:00:00+00:00");
    expect($sched['qa_request_deadline'])->toEqual($sched['nightly_start']);
    expect($sched['a11y_request_deadline'])->toEqual($sched['nightly_start']);

    // 158 is on the 2-week cycle too, only a single planned dot release
    $obj = new Release('158.0');
    expect($obj->getSchedule())
        ->toHaveKey('dot_release_1')
        ->not->toHaveKeys(['dot_release_2', 'dot_release_3']);
    expect($obj->getSchedule()['dot_release_1'])->toBe("2026-10-20 14:00:00+00:00");
});

test('Release->getSchedule(): Milestones are in the right order (legacy 4-week cycle)', function () {
    $sched = new Release('154.0')->getFutureSchedule();
    unset($sched['version']); // not a date string
    $sched = array_map(fn($date) => new DateTime($date), $sched);
    expect($sched['qa_request_deadline'])->toEqual($sched['a11y_request_deadline']);
    expect($sched['a11y_request_deadline'])->toBeLessThan($sched['nightly_start']);
    expect($sched['nightly_start'])->toBeLessThan($sched['qa_feature_done']);
    expect($sched['qa_feature_done'])->toEqual($sched['qa_test_plan_due']);
    expect($sched['qa_test_plan_due'])->toBeLessThan($sched['relnotes_beta_ready']);
    expect($sched['relnotes_beta_ready'])->toBeLessThan($sched['string_freeze']);
    expect($sched['strings_handoff'])->toBeLessThan($sched['string_freeze']);
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
    expect($sched['beta_7'])->toBeLessThan($sched['qa_pre_rc_signoff']);
    expect($sched['qa_pre_rc_signoff'])->toBeLessThan($sched['beta_8']);
    expect($sched['beta_8'])->toBeLessThan($sched['beta_9']);
    expect($sched['beta_9'])->toBeLessThan($sched['beta_10']);
    expect($sched['relnotes_deadline'])->toBeLessThan($sched['beta_10']);
    expect($sched['beta_10'])->toBeLessThan($sched['rc_gtb']);
    expect($sched['rc_gtb'])->toBeLessThan($sched['release']);
    expect($sched['release'])->toBeLessThan($sched['dot_release_1']);
    // 154 has a single planned dot release
    expect($sched)->not->toHaveKeys(['dot_release_2', 'dot_release_3']);
});

test('Release->getSchedule(): Milestones are in the right order (2-week cycle)', function () {
    // 160 is a regular 2-week release (155 is the transition release; 163/164 are
    // year-boundary special cases).
    $sched = new Release('160.0')->getSchedule();
    unset($sched['version']); // not a date string
    $sched = array_map(fn($date) => new DateTime($date), $sched);
    // The manual QA request deadline is now a week ahead of the accessibility review deadline
    expect($sched['qa_request_deadline'])->toBeLessThan($sched['a11y_request_deadline']);
    // The accessibility request is due on the first day of the Nightly cycle
    expect($sched['a11y_request_deadline'])->toEqual($sched['nightly_start']);
    expect($sched['nightly_start'])->toBeLessThan($sched['qa_feature_done']);
    expect($sched['qa_feature_done'])->toEqual($sched['qa_test_plan_due']);
    expect($sched['qa_test_plan_due'])->toBeLessThan($sched['strings_handoff']);
    expect($sched['strings_handoff'])->toBeLessThan($sched['relnotes_beta_ready']);
    expect($sched['string_freeze'])->toBeLessThan($sched['relnotes_beta_ready']);
    expect($sched['string_freeze'])->toBeLessThan($sched['qa_nightly_signoff']);
    expect($sched['qa_nightly_signoff'])->toBeLessThan($sched['merge_day']);
    expect($sched['merge_day'])->toBeLessThan($sched['beta_1']);
    expect($sched['beta_1'])->toBeLessThan($sched['beta_2']);
    expect($sched['beta_2'])->toBeLessThan($sched['sumo_1']);
    expect($sched['sumo_1'])->toBeLessThan($sched['beta_3']);
    expect($sched['beta_3'])->toBeLessThan($sched['beta_4']);
    expect($sched['beta_4'])->toBeLessThan($sched['beta_5']);
    expect($sched['beta_5'])->toBeLessThan($sched['relnotes_deadline']);
    expect($sched['relnotes_deadline'])->toBeLessThan($sched['rc_gtb']);
    expect($sched['rc_gtb'])->toBeLessThan($sched['release']);
    expect($sched['release'])->toBeLessThan($sched['dot_release_1']);
});

test('Release->getNiceLabel()', function () {
    expect(Release::getNiceLabel('104', 'release'))->toEqual('<b>104 Release</b>');
    expect(Release::getNiceLabel('104', 'release', false))->toEqual('Firefox 104 go-live @ 6AM PT');
});