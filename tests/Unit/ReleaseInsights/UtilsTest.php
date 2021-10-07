<?php
declare(strict_types=1);

use ReleaseInsights\Utils as U;

CONST FIREFOX_RELEASE = '93';
CONST FIREFOX_BETA = '94';
CONST FIREFOX_NIGHTLY = '95';

test('requestedVersion' , function () {
    $this->assertEquals('94.0', U::requestedVersion());
    $this->assertEquals('95.0', U::requestedVersion(FIREFOX_NIGHTLY));
    $this->assertEquals('94.0', U::requestedVersion(FIREFOX_BETA));
    $this->assertEquals('93.0', U::requestedVersion(FIREFOX_RELEASE));
    $this->assertEquals('100.0', U::requestedVersion('100'));
});

test('isBuildID' , function () {
    $this->assertFalse(U::isBuildID('01234587392871'));
    $this->assertFalse(U::isBuildID('oajoaoojoaooao'));
    $this->assertFalse(U::isBuildID('0123458739287122'));
    $this->assertFalse(U::isBuildID('012345873928712'));
    $this->assertFalse(U::isBuildID('20501229120000'));
    $this->assertTrue(U::isBuildID('20201229120000'));
});

test('getDate' , function () {

    // No get parameter, Today
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
});
