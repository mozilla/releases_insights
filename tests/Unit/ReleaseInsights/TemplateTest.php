<?php
declare(strict_types=1);

use ReleaseInsights\Template;

test('Template Class', function () {
    expect((new Template('file', ['data']))->data)->toEqual(['data']);
});

test('Template::asset', function () {
    // Existing file: a version based on its content is appended
    expect(Template::asset('/style/base.css'))
        ->toMatch('#^/style/base\.css\?version=[0-9a-f]{8}$#')
        ->toBe(Template::asset('/style/base.css'));

    // Different content, different version
    expect(explode('?', Template::asset('/style/base.css'))[1])
        ->not->toBe(explode('?', Template::asset('/robots.txt'))[1]);

    // Unknown file: the path is returned untouched
    expect(Template::asset('/style/nope.css'))->toBe('/style/nope.css');
});
