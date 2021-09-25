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
