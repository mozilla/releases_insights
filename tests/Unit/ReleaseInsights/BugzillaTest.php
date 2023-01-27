<?php

declare(strict_types=1);

use ReleaseInsights\Bugzilla as bz;

test('Bugzilla::getBugListLink', function () {
    $this->assertEquals(
        'https://bugzilla.mozilla.org/buglist.cgi?bug_id=101%2C102%2C103',
        bz::getBugListLink([101, 102, 103])
    );
});

test('Bugzilla::linkify', function () {
    $this->assertEquals(
        'Le <a href="https://bugzilla.mozilla.org/125468">bug 125468</a> est fermé, le <a href="https://bugzilla.mozilla.org/7454654">Bug 7454654</a> est ouvert un essai de <a href="https://bugzilla.mozilla.org/458">bug  458</a> et de <a href="https://bugzilla.mozilla.org/1">bug 1</a>aa4.',
        bz::linkify('Le bug 125468 est fermé, le Bug 7454654 est ouvert un essai de bug  458 et de bug 1aa4.')
    );
});

test('Bugzilla::getBugsFromHgWeb', function () {
    expect(bz::getBugsFromHgWeb(TEST_FILES . 'beta97_json-pushes.json'))
        ->toBeArray()
        ->toHaveLength(4)
        ->toHaveKeys(['bug_fixes', 'backouts', 'total', 'no_data'])
        ->sequence(
            fn ($value, $key) => $value->toBeArray(),
            fn ($value, $key) => $value->toBeArray(),
            fn ($value, $key) => $value->toBeArray(),
            fn ($value, $key) => $value->toBeFalse(),
        );

    expect(bz::getBugsFromHgWeb(
        TEST_FILES . 'beta97_json-pushes.json',
        true
    ))
        ->toBeArray()
        ->toHaveLength(4)
        ->toHaveKeys(['bug_fixes', 'backouts', 'total', 'no_data'])
        ->sequence(
            fn ($value, $key) => $value->toBeArray(),
            fn ($value, $key) => $value->toBeArray(),
            fn ($value, $key) => $value->toBeArray(),
            fn ($value, $key) => $value->toBeFalse(),
        );

    expect(bz::getBugsFromHgWeb(
        TEST_FILES . 'empty_hg_pushes.json',
        true
    ))
        ->toBeArray()
        ->toHaveLength(4)
        ->toHaveKeys(['bug_fixes', 'backouts', 'total', 'no_data'])
        ->sequence(
            fn ($value, $key) => $value->toBeArray(),
            fn ($value, $key) => $value->toBeArray(),
            fn ($value, $key) => $value->toBeArray(),
            fn ($value, $key) => $value->toBeTrue(),
        );
    expect(bz::getBugsFromHgWeb(
        TEST_FILES . 'beta98_nobug_json-pushes.json',
        true
    ))
        ->toBeArray()
        ->toHaveLength(4)
        ->toHaveKeys(['bug_fixes', 'backouts', 'total', 'no_data'])
        ->sequence(
            fn ($value, $key) => $value->toBeArray(),
            fn ($value, $key) => $value->toBeArray(),
            fn ($value, $key) => $value->toBeArray(),
            fn ($value, $key) => $value->toBeFalse(),
        );

});
