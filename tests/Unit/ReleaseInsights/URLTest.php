<?php

declare(strict_types=1);

use ReleaseInsights\URL;

test('URL->target()', function () {
    $value = URL::Bugzilla->target();
    expect($value)->toEndWith('/tests/Files/');
});