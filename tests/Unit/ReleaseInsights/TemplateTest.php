<?php

declare(strict_types=1);

use ReleaseInsights\Template ;
const INSTALL_ROOT  = __DIR__ . '/../../../';

test('Template Class', function () {
    expect((new Template('file', ['data']))->data)->toEqual(['data']);
});
