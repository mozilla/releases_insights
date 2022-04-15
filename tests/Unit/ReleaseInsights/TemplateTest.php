<?php
declare(strict_types=1);

use ReleaseInsights\Template;

test('Template Class', function () {
    expect((new Template('file', ['data']))->data)->toEqual(['data']);
});
