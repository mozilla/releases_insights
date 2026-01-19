<?php

declare(strict_types=1);

use ReleaseInsights\Bugzilla as bz;

test('Bugzilla::getBugListLink', function () {
    $this->assertEquals(
        'https://bugzilla.mozilla.org/buglist.cgi?bug_id=101%2C102%2C103',
        bz::getBugListLink([101, 102, 103])
    );
    $this->assertEquals(
        'https://bugzilla.mozilla.org/buglist.cgi?bug_id=101%2C103',
        bz::getBugListLink([101, 101, 103])
    );
    $this->assertEquals(
        'https://bugzilla.mozilla.org/buglist.cgi?bug_id=101%2C103',
        bz::getBugListLink(['101', '101', '103'])
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
        ->toHaveLength(5)
        ->toHaveKeys(['bug_fixes', 'backouts', 'total', 'files', 'no_data'])
        ->sequence(
            fn ($value, $key) => $value->toBeArray(),
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
        ->toHaveLength(5)
        ->toHaveKeys(['bug_fixes', 'backouts', 'total',  'files', 'no_data'])
        ->sequence(
            fn ($value, $key) => $value->toBeArray(),
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
        ->toHaveLength(5)
        ->toHaveKeys(['bug_fixes', 'backouts', 'total',  'files', 'no_data'])
        ->sequence(
            fn ($value, $key) => $value->toBeArray(),
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
        ->toHaveKeys(['bug_fixes', 'backouts', 'total',  'files', 'no_data'])
        ->sequence(
            fn ($value, $key) => $value->toBeArray(),
            fn ($value, $key) => $value->toBeArray(),
            fn ($value, $key) => $value->toBeArray(),
            fn ($value, $key) => $value->toBeArray(),
            fn ($value, $key) => $value->toBeFalse(),
        );

        $json = '{
"lastpushid": 16379,
"pushes": {"16373": {"changesets": [{"author": "Mozilla Releng Treescript \u003crelease+treescript@mozilla.org\u003e", "branch": "default", "desc": "No bug - Tagging 7a209c1754b43543dbe7c45ffbd6fbf4c40d4240 with DEVEDITION_98_0b7_BUILD1 a=release CLOSED TREE DONTBUILD", "files": [".hgtags"], "node": "5c61d1b39323a6f0d5982483caf59b5f1916f625", "parents": ["7a209c1754b43543dbe7c45ffbd6fbf4c40d4240"], "tags": []}], "date": 1645392080, "user": "ffxbld"}}
}';
    expect(bz::getBugsFromHgWeb($json, true))
        ->toBeArray()
        ->toHaveKeys(['bug_fixes', 'backouts', 'total',  'files', 'no_data'])
        ->sequence(
            fn ($value, $key) => $value->toBeArray(),
            fn ($value, $key) => $value->toBeArray(),
            fn ($value, $key) => $value->toBeArray(),
            fn ($value, $key) => $value->toBeArray(),
            fn ($value, $key) => $value->toBeFalse(),
        );

});
