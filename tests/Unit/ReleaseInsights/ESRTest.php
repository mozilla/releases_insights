<?php

declare(strict_types=1);

use ReleaseInsights\ESR;

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
    [11, '10.1.0'],
    [9, null],
    [1000, null],
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
    [10, null],
    [102, '91.11.0'],
    [1000, null],
]);

test('ESR::getMainDotVersion', function ($input, $output) {
    expect($output)->toEqual(ESR::getMainDotVersion($input));
})->with([
    ['78.12.0esr', '78.12'],
    ['91.4.1esr', '91.4'],
    ['68.0.0esr', '68.0'],
    [null, ''],
]);
