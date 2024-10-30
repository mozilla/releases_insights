<?php

declare(strict_types=1);

use ReleaseInsights\Nightly;

test('Nightly Class', function () {
    $obj = new Nightly(TEST_FILES, TEST_FILES, 'nightly_updates_off.json');
    expect($obj->version)->toEqual('95.0a1');
    expect($obj->auto_updates)->toBeFalse();
    expect($obj->emergency_message)->toEqual('Nightly updates are disabled');
});

test('cycleStart()', function () {
    expect(Nightly::cycleStart(127))->toEqual('2024-04-16');
    expect(Nightly::cycleStart(16))->toEqual('2012-06-04');
    expect(Nightly::cycleStart(94))->toEqual('2021-09-07');
    expect(Nightly::cycleStart(1))->toEqual('2004-11-09');
    expect(Nightly::cycleStart(2))->toEqual('2006-10-24');
    expect(Nightly::cycleStart(4))->toEqual('2010-01-21');
});

test('getLatestBuildID()', function () {
    expect(Nightly::getLatestBuildID())->toEqual('20241029155057');
});
