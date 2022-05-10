<?php

declare(strict_types=1);

use ReleaseInsights\Nightly;

test('Nightly Class', function () {
    expect((new Nightly(__DIR__ .'/../../Files/'))->version)->toEqual('95.0a1');
});
