<?php

declare(strict_types=1);

use ReleaseInsights\{Bugzilla, ESR, UpliftCounts};

beforeEach(fn() => UpliftCounts::reset());

test('UpliftCounts::cacheKey() includes the parser version', function () {
    expect(UpliftCounts::cacheKey('esr_140.6.0'))
        ->toBe('uplift_count_p' . UpliftCounts::PARSER_VERSION . '_esr_140.6.0');
});

test('UpliftCounts::getESRLogUrl()', function ($version, $in_progress, $from, $to) {
    $url = UpliftCounts::getESRLogUrl($version, $in_progress);
    expect($url)
        ->toContain('releases/mozilla-esr' . (int) $version . '/json-pushes?')
        ->toContain('fromchange=' . $from . '&tochange=' . $to . '&full&version=2')
        ->not->toContain('&amp;');
})->with([
    ['140.6.0', false, 'FIREFOX_140_5_0esr_RELEASE', 'FIREFOX_140_6_0esr_RELEASE'],
    ['140.6.0', true, 'FIREFOX_140_5_0esr_RELEASE', 'tip'],
    // The initial release tag of a branch has no dot release part
    ['140.1.0', false, 'FIREFOX_140_0esr_RELEASE', 'FIREFOX_140_1_0esr_RELEASE'],
]);

test('UpliftCounts::getESRLogUrl() new ESR branch has no log', function () {
    expect(UpliftCounts::getESRLogUrl('175.0.0', false))->toBeNull();
});

test('UpliftCounts::getBetaLogUrls() for a shipped release', function () {
    expect(UpliftCounts::getBetaLogUrls(130))->toBe([
        TEST_FILES . 'releases/mozilla-beta/json-pushes?fromchange=FIREFOX_BETA_130_BASE&tochange=FIREFOX_BETA_130_END&full&version=2',
        TEST_FILES . 'releases/mozilla-release/json-pushes?fromchange=FIREFOX_RELEASE_130_BASE&tochange=FIREFOX_130_0_RELEASE&full&version=2',
    ]);
});

test('UpliftCounts::getBetaLogUrls() special cases', function () {
    // 125.0 never shipped
    expect(UpliftCounts::getBetaLogUrls(125)[1])->toContain('tochange=FIREFOX_125_0_1_RELEASE');
    // 119 beta range is corrupted by a central to beta merge
    expect(UpliftCounts::getBetaLogUrls(119)[0])->toContain('tochange=f2a69b23cb0aaf2b36bac4f9f197bf4282f542c4');
});

test('UpliftCounts::getESRReleases()', function () {
    // Product Details lists versions without the esr suffix, we accept both
    $shipped = ['91.8.0', '91.9.0', '115.26.0', '128.12.0esr', '128.13.0esr', '140.1.0', '78.15.0'];
    $releases = UpliftCounts::getESRReleases(BETA, $shipped);

    expect(array_key_first($releases))->toBe(UpliftCounts::ESR_FIRST_VERSION);
    expect(array_key_last($releases))->toBe(BETA);
    expect($releases[141])->toBe([115 => '115.26.0', 128 => '128.13.0', 140 => '140.1.0']);
    expect($releases[100])->toBe([91 => '91.9.0']);
    // 78.16.0 is in the theoretical schedule but never shipped
    expect($releases[94])->toBe([]);
    // The cycle in progress keeps the supported branches only
    expect(array_keys($releases[BETA]))->toBe(array_values(array_filter(
        array_map('intval', ESR::getVersionsForRelease(BETA)),
        fn(int $branch) => ESR::isSupported($branch)
    )));
});

test('UpliftCounts::getCycleWeeks()', function () {
    $weeks = UpliftCounts::getCycleWeeks([
        '124.0'   => '2024-03-19',
        '125.0.1' => '2024-04-16',
        '125.0.2' => '2024-04-23',
        '126.0'   => '2024-05-14',
        '127.0'   => '2024-06-11',
    ]);
    expect($weeks)->toBe([125 => 4.0, 126 => 4.0, 127 => 4.0]);
});

test('UpliftCounts::count() counts bugs in logs', function () {
    $file = TEST_FILES . 'beta97_json-pushes.json';
    $expected = count(Bugzilla::getBugsFromHgWeb($file, true)['total']);

    expect($expected)->toBeGreaterThan(0);
    expect(UpliftCounts::count('test', [$file], false))->toBe($expected);
    expect(UpliftCounts::count('test', [$file, $file], false))->toBe($expected * 2);
    expect(UpliftCounts::count('test', [TEST_FILES . 'empty_hg_pushes.json'], false))->toBe(0);
});

test('UpliftCounts::count() returns null when a log is not available', function ($file) {
    expect(UpliftCounts::count('test', [TEST_FILES . 'beta97_json-pushes.json', TEST_FILES . $file], false))->toBeNull();
    // After a failure we stop fetching logs
    expect(UpliftCounts::isBlocked())->toBeTrue();
    expect(UpliftCounts::count('test', [TEST_FILES . 'beta97_json-pushes.json'], false))->toBeNull();
})->with([
    'missing file'  => 'missing.json',
    'not json'      => 'bad.json',
    'not a hg log'  => 'firefox_versions.json',
]);
