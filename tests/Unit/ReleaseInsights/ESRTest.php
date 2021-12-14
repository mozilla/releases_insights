<?php

declare(strict_types=1);

use ReleaseInsights\ESR ;

// use ReleaseInsights\Utils;

test('ESR::getVersion', function ($input, $output) {
    expect($output)->toEqual(ESR::getVersion($input));
})->with([
    [100, '91.9.0'],
    [95, '91.4.0'],
    [91, '91.0.0'],
    [79, '78.1.0'],
    [77, '68.9.0'],
    [60, '60.0.0'],
    [59, '52.7.0'],
]);

test('ESR::getOlderSupportedVersion', function ($input, $output) {
    expect($output)->toEqual(ESR::getOlderSupportedVersion($input));
})->with([
    [68, '60.8.0'],
    [78, '68.10.0'],
    [90, null],
    [91, '78.13.0'],
    [93, '78.15.0'],
    [94, null],
    [102, '91.11.0'],
]);
