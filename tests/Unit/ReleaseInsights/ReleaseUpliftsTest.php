<?php

declare(strict_types=1);

use ReleaseInsights\ReleaseUplifts;

test('ReleaseUplifts->getLogEndpoints() URLs use & not &amp;', function () {
    $obj = new ReleaseUplifts();
    $endpoints = $obj->getLogEndpoints();

    expect($endpoints)->toBeArray()->not->toBeEmpty();

    foreach ($endpoints as $url) {
        expect($url)->not->toContain('&amp;');
        expect($url)->toContain('&');
    }
});

test('ReleaseUplifts->getLogEndpoints() current release has -next entry', function () {
    $obj = new ReleaseUplifts();
    $endpoints = $obj->getLogEndpoints();

    $next_keys = array_filter(array_keys($endpoints), fn($k) => str_ends_with($k, '-next'));
    expect($next_keys)->not->toBeEmpty();
});

test('ReleaseUplifts->getLogEndpoints() past release with dot releases has correct tags', function () {
    $obj = new ReleaseUplifts(97);
    $endpoints = $obj->getLogEndpoints();

    if (empty($endpoints)) {
        $this->markTestSkipped('No dot release data available for version 97 in test context');
    }

    foreach ($endpoints as $version => $url) {
        expect($url)
            ->toContain('FIREFOX_97')
            ->not->toContain('&amp;');
    }
});

test('ReleaseUplifts->getLogEndpoints() android-only releases use FIREFOX-ANDROID_ tag prefix', function () {
    // Release 133 has two android-only dot releases (133.0.1, 133.0.2) followed by a both release (133.0.3)
    $obj = new ReleaseUplifts(133);
    $endpoints = $obj->getLogEndpoints();

    expect($endpoints['133.0.1'])->toContain('tochange=FIREFOX-ANDROID_133_0_1_RELEASE');
    expect($endpoints['133.0.1'])->not->toContain('tochange=FIREFOX_133_0_1_RELEASE');

    expect($endpoints['133.0.2'])->toContain('tochange=FIREFOX-ANDROID_133_0_2_RELEASE');
    expect($endpoints['133.0.2'])->not->toContain('tochange=FIREFOX_133_0_2_RELEASE');
});

test('ReleaseUplifts->getLogEndpoints() android tag is used as fromchange for subsequent release', function () {
    // 133.0.1 (android) -> 133.0.2 (android) -> 133.0.3 (both)
    // Each release must start from the previous release's tag, using the correct prefix
    $obj = new ReleaseUplifts(133);
    $endpoints = $obj->getLogEndpoints();

    expect($endpoints['133.0.2'])->toContain('fromchange=FIREFOX-ANDROID_133_0_1_RELEASE');
    expect($endpoints['133.0.3'])->toContain('fromchange=FIREFOX-ANDROID_133_0_2_RELEASE');
});

test('ReleaseUplifts->crashes() returns per-version totals, signatures, and a summary', function () {
    // Release 145 has two desktop dot releases (145.0.1, 145.0.2).
    // Fixture files crash-stats.mozilla.org_145.0.{1,2}.json provide the data.
    $obj = new ReleaseUplifts(145);
    $crashes = $obj->crashes();

    expect($crashes)->toHaveKeys(['145.0.1', '145.0.2', 'summary']);

    expect($crashes['145.0.1']['total'])->toBe(12);
    expect($crashes['145.0.1']['signatures'])->toBeArray()->not->toBeEmpty();
    expect($crashes['145.0.1']['signatures'][0])->toMatchArray(['term' => 'OOM | small', 'count' => 5]);

    expect($crashes['145.0.2']['total'])->toBe(7);
    expect($crashes['145.0.2']['signatures'])->toHaveCount(2);

    expect($crashes['summary']['total'])->toBe(19);
});

test('ReleaseUplifts->crashes() skips android-only dot releases', function () {
    // 133.0.1 and 133.0.2 are android-only and must be excluded from crash queries.
    // 133.0.3 is "both" but no fixture exists, so we expect it to be present with total=0.
    $obj = new ReleaseUplifts(133);
    $crashes = $obj->crashes();

    expect($crashes)->not->toHaveKey('133.0.1');
    expect($crashes)->not->toHaveKey('133.0.2');
    expect($crashes)->toHaveKeys(['133.0.3', 'summary']);
});
