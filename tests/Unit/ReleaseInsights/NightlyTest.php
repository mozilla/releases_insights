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
    expect(Nightly::cycleStart(125))->toEqual('2024-04-16');
    expect(Nightly::cycleStart(16))->toEqual('2012-06-04');
    expect(Nightly::cycleStart(94))->toEqual('2021-11-02');
});