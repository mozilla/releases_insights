<?php

declare(strict_types=1);

use ReleaseInsights\ESR;

test('ESR::getVersion', function ($input, $output) {
    expect($output)->toEqual(ESR::getVersion($input));
})->with([
    [160, '153.7.0'],
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
    [94, '78.16.0'],
    [95, null],
    [10, null],
    [102, '91.11.0'],
    [136, '115.21.0'],
    // ESR 128 (retired in the 4-week cadence) is supported for 3 releases, until Firefox 143, then EOL.
    [143, '128.15.0'],
    [144, null],
    [155, '140.15.0'],
    // ESR 140's last release is 140.17 (Firefox 157); it is EOL from Firefox 158 (2026-10-13).
    [157, '140.17.0'],
    [158, null],
    [1000, null],
]);

test('ESR::getWin7SupportedVersion', function ($input, $output) {
    expect($output)->toEqual(ESR::getWin7SupportedVersion($input));
})->with([
    [114, null],
    [142, '115.27.0'],
    [154, '115.39.0'],
    [155, '115.40.0'],
    [168, '115.53.0'],
    [169, null],
]);

test('ESR::getMainDotVersion', function ($input, $output) {
    expect($output)->toEqual(ESR::getMainDotVersion($input));
})->with([
    ['78.12.0esr', '78.12'],
    ['91.4.1esr', '91.4'],
    ['68.0.0esr', '68.0'],
    [null, ''],
]);
