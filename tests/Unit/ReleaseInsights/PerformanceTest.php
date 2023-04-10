<?php

declare(strict_types=1);

use ReleaseInsights\Performance;

test('Performance Class', function () {
    $obj = new Performance();
    expect($obj->getScriptPerformances())->toBeArray();
    expect($obj->getScriptPerformances())->toHaveCount(3);
});
