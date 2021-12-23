<?php
declare(strict_types=1);

use ReleaseInsights\Version;


test('Version::get', function () {
    $this->assertEquals('94.0', Version::get());
    $this->assertEquals('95.0', Version::get(FIREFOX_NIGHTLY));
    $this->assertEquals('94.0', Version::get(FIREFOX_BETA));
    $this->assertEquals('93.0', Version::get(FIREFOX_RELEASE));
    $this->assertEquals('100.0', Version::get('100'));
});

test('Version::getMajor', function ($input, $output) {
    expect($output)->toEqual(Version::getMajor($input));
})->with([
    ['91.1.0', 91],
    ['100', 100],
    ['100.5', 100],
    ['78.0.3', 78],
]);


test('Version::decrement', function ($version, $number, $output) {
    expect($output)->toEqual(Version::decrement($version, $number));
})->with([
    ['91', 2, '89.0'],
    ['91.0', 2, '89.0'],
    ['100', 3, '97.0'],
]);
