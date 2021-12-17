<?php

declare(strict_types=1);

use Cache\Cache;
use ReleaseInsights\Utils as U;

const CACHE_ENABLED = false;
const FIREFOX_RELEASE = '93';
const FIREFOX_BETA = '94';
const FIREFOX_NIGHTLY = '95';

test('Utils::requestedVersion', function () {
    $this->assertEquals('94.0', U::requestedVersion());
    $this->assertEquals('95.0', U::requestedVersion(FIREFOX_NIGHTLY));
    $this->assertEquals('94.0', U::requestedVersion(FIREFOX_BETA));
    $this->assertEquals('93.0', U::requestedVersion(FIREFOX_RELEASE));
    $this->assertEquals('100.0', U::requestedVersion('100'));
});

test('Utils::isBuildID', function () {
    $this->assertFalse(U::isBuildID('01234587392871'));
    $this->assertFalse(U::isBuildID('oajoaoojoaooao'));
    $this->assertFalse(U::isBuildID('0123458739287122'));
    $this->assertFalse(U::isBuildID('012345873928712'));
    $this->assertFalse(U::isBuildID('20501229120000'));
    $this->assertTrue(U::isBuildID('20201229120000'));
});

test('Utils::getBuildID', function () {
    // Test fallback value
    $this->assertEquals('20191014213051', U::getBuildID('20501229120000'));

    // Test good value
    $this->assertEquals('20201229120000', U::getBuildID('20201229120000'));
});

test('Utils::secureText', function ($input, $output) {
    expect($output)->toEqual(U::secureText($input));
})->with([
    ["achat des couteaux\nsuisses", 'achat des couteaux suisses'],
    ['<b>foo</b>', '&#60;b&#62;foo&#60;/b&#62;'],
]);

test('Utils::getDate', function () {

    // No GET parameter, Today
    $this->assertEquals(date('Ymd'), U::getDate());

    $_GET['date'] = 'today';
    $this->assertEquals(date('Ymd'), U::getDate());

    // Not a date format
    $_GET['date'] = '5a ';
    $this->assertEquals(date('Ymd'), U::getDate());

    // Invalid, there is a space
    $_GET['date'] = '20191231 ';
    $this->assertEquals(date('Ymd'), U::getDate());

    // Valid date
    $_GET['date'] = '20210912';
    $this->assertEquals('20210912', U::getDate());
    unset($_GET['date']);
});

test('Utils::getJson', function () {
    expect(U::getJson(__DIR__ . '/../../Files/firefox_versions.json'))->toBeIterable();
});

test('Utils::mtrim', function ($input, $output) {
    expect($output)->toEqual(U::mtrim($input));
})->with([
    ['Le cheval  blanc ', 'Le cheval blanc'],
    ['  Le cheval  blanc', 'Le cheval blanc'],
    ['  Le cheval  blanc  ', 'Le cheval blanc'],
    ['Le cheval  blanc', 'Le cheval blanc'],
]);

test('Utils::startsWith', function ($input, $matches, $result) {
    expect($result)->toEqual(U::startsWith($input, $matches));
})->with([
    ['it is raining', 'it', true],
    [' foobar starts with a nasty space', 'foobar', false],
    ['multiple matches test', ['horse', 'multiple'], true],
    ['multiple matches test', ['not', 'there'], false],
]);

test('Utils::getMajorVersion', function ($input, $output) {
    expect($output)->toEqual(U::getMajorVersion($input));
})->with([
    ['91.1.0', 91],
    ['100', 100],
    ['100.5', 100],
    ['78.0.3', 78],
    ['', null],
]);
