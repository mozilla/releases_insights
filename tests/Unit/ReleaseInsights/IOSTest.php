<?php

declare(strict_types=1);

use ReleaseInsights\IOS;

test('IOS->getSchedule()', function () {
    $obj = new IOS('99999.0'); // Does not exist
    expect($obj->getSchedule())->toHaveKey('error');

    $obj = new IOS('144.0'); // We don't support releases < 145
    expect($obj->getSchedule())->toBeArray();
    expect($obj->getSchedule())->toHaveKey('error');

    $obj = new IOS('145.0'); // From the full schedule
    expect($obj->getPastSchedule()['release'])->toBe("2025-11-10 02:00:00+00:00");
    expect($obj->getPastSchedule())->toHaveKeys(['version', 'release', 'dot_release_1', 'dot_release_2', 'dot_release_3', ]);
    expect($obj->getSchedule())->not->toHaveKey('release'); // Test that the schedule in flight does not return the PastSchedule() milestones

    $obj = new IOS('146.0'); // From the full schedule
    expect($obj->getFutureSchedule())->not()->toHaveKeys(['dot_release_2', 'dot_release_3', 'release_2', 'release_3']);
    expect($obj->getPastSchedule())->not()->toHaveKeys(['dot_release_2', 'dot_release_3',]);

    $obj = new IOS('147.0'); // From the full schedule
    expect($obj->getSchedule())->toHaveKeys(['version', 'merge_day_0', 'rc_gtb_0', 'qa_pre_signoff_0', 'qa_signoff_0', 'appstore_sent_0', 'merge_day_1', 'rc_gtb_1', 'qa_pre_signoff_1', 'release_0', 'qa_signoff_1', 'appstore_sent_1', 'merge_day_2', 'rc_gtb_2', 'qa_pre_signoff_2', 'release_1', 'qa_signoff_2', 'appstore_sent_2', 'merge_day_3', 'rc_gtb_3', 'qa_pre_signoff_3', 'release_2', 'qa_signoff_3', 'appstore_sent_3', 'merge_day_4', 'rc_gtb_4', 'qa_pre_signoff_4', 'release_3', 'qa_signoff_4', 'appstore_sent_4', 'merge_day_5', 'rc_gtb_5', 'qa_pre_signoff_5', 'release_4', 'qa_signoff_5', 'appstore_sent_5', 'release_5',]);
    expect($obj->getSchedule()['qa_pre_signoff_0'])->toBe('2025-12-31 06:00:00+00:00');
    expect($obj->getSchedule()['qa_signoff_0'])->toBe('2026-01-08 06:00:00+00:00');
    expect($obj->getPastSchedule())->toHaveKeys(['dot_release_4', 'dot_release_5',]);

    $obj = new IOS('148.0'); // Wellness
    expect($obj->getSchedule()['merge_day_3'])->toBe('2026-03-05 00:00:00+00:00');
    expect($obj->getSchedule()['rc_gtb_3'])->toBe('2026-03-05 00:04:00+00:00');

    $obj = new IOS('152.0'); // Wellness and an extra release
    expect($obj->getSchedule()['merge_day_0'])->toBe('2026-06-04 00:00:00+00:00');
    expect($obj->getSchedule()['rc_gtb_0'])->toBe('2026-06-04 00:04:00+00:00');
    expect($obj->getSchedule())->toHaveKeys(['version', 'merge_day_0', 'rc_gtb_0', 'qa_pre_signoff_0', 'qa_signoff_0', 'appstore_sent_0', 'merge_day_1', 'rc_gtb_1', 'qa_pre_signoff_1', 'release_0', 'qa_signoff_1', 'appstore_sent_1', 'merge_day_2', 'rc_gtb_2', 'qa_pre_signoff_2', 'release_1', 'qa_signoff_2', 'appstore_sent_2', 'merge_day_3', 'rc_gtb_3', 'qa_pre_signoff_3', 'release_2', 'qa_signoff_3', 'appstore_sent_3', 'merge_day_4', 'rc_gtb_4', 'qa_pre_signoff_4', 'release_3', 'qa_signoff_4', 'appstore_sent_4', 'release_4',]);
    expect($obj->getPastSchedule())->toHaveKeys(['dot_release_4',]);

    $obj = new IOS('154.0'); // Wellness
    expect($obj->getSchedule()['merge_day_3'])->toBe('2026-08-27 00:00:00+00:00');
    expect($obj->getSchedule()['rc_gtb_3'])->toBe('2026-08-27 00:04:00+00:00');

    $obj = new IOS('156.0'); // Wellness
    expect($obj->getSchedule()['merge_day_3'])->toBe('2026-10-22 00:00:00+00:00');
    expect($obj->getSchedule()['rc_gtb_3'])->toBe('2026-10-22 00:04:00+00:00');
});


