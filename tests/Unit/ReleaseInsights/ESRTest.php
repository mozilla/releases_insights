<?php
declare(strict_types=1);

use ReleaseInsights\ESR ;

test('ESR::getVersion', function ($input, $output) {
    expect($output)->toEqual(ESR::getVersion($input));
})->with([
    [100, '91.9.0'],
    [95, '91.4.0'],
    [91, '91.0.0'],
    [79, '78.1.0'],
    [77, '68.9.0'],
    [60, '52.8.0'],
]);
