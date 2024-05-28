<?php
declare(strict_types=1);

use ReleaseInsights\Model;

test('Model Class', function () {
    expect(
        (new Model('owners'))->get())->toBeArray();
});
