<?php
declare(strict_types=1);

use ReleaseInsights\Version;
use ReleaseInsights\ESR;


test('Version::get', function () {
    $this->assertEquals('94.0', Version::get());
    $this->assertEquals('95.0', Version::get(FIREFOX_NIGHTLY));
    $this->assertEquals('94.0', Version::get(FIREFOX_BETA));
    $this->assertEquals('93.0', Version::get(FIREFOX_RELEASE));
    $this->assertEquals('100.0', Version::get('100'));
    $this->assertEquals('1200.0', Version::get('1200'));

    // With GET
    $_GET['version'] = 'release';
    $this->assertEquals('93.0', Version::get());
    $_GET['version'] = 'beta';
    $this->assertEquals('94.0', Version::get());
    $_GET['version'] = 'nightly';
    $this->assertEquals('95.0', Version::get());
    $_GET['version'] = 'esr';
    $this->assertEquals('78.0', Version::get());
    $_GET['version'] = '90';
    $this->assertEquals('90.0', Version::get());

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
    ['1.0', 1, '1.0'],
    ['1.6', 2, '1.0'],
    ['91', 2, '89.0'],
    ['91.0', 2, '89.0'],
    ['100', 3, '97.0'],
]);
