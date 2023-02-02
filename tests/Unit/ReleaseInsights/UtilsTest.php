<?php

declare(strict_types=1);

use Cache\Cache;
use \DateTime\Datime;
use ReleaseInsights\Utils as U;

test('Utils::isBuildID', function () {
    $this->assertFalse(U::isBuildID('1234587392871'));
    $this->assertFalse(U::isBuildID('123458739287122'));
    $this->assertFalse(U::isBuildID('12345873928712'));
    $this->assertFalse(U::isBuildID('20501229120000'));
    $this->assertTrue(U::isBuildID('20201229120000'));
    $this->assertFalse(U::isBuildID('20501229120000'));
    $this->assertTrue(U::isBuildID('20201229120000'));
    $this->assertTrue(U::isBuildID('20220220120000'));
    // Invalid date
    $this->assertFalse(U::isBuildID('99999999999999'));

    // Today is a valid date
    $this->assertTrue(U::isBuildID(
        (new DateTime())->format('Ymdhhs')
    ));
});

test('Utils::getBuildID', function () {
    // Test fallback value
    $this->assertEquals(20191014213051, U::getBuildID('20501229120000'));

    // Test good value
    $this->assertEquals(20201229120000, U::getBuildID('20201229120000'));
});

test('Utils::secureText', function ($input, $output) {
    expect($output)->toEqual(U::secureText($input));
})->with([
    ["achat des couteaux\nsuisses", 'achat des couteaux suisses'],
    ['<b>foo</b>', '&#60;b&#62;foo&#60;/b&#62;'],
    ['<b>foo%0D</b>', '&#60;b&#62;foo&#60;/b&#62;'],
    ['<b>foo%0A</b>', '&#60;b&#62;foo&#60;/b&#62;'],
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
    expect(U::getJson(__DIR__ . '/../../Files/firefox_versions.json'))->toBeArray();
    expect(U::getJson(__DIR__ . '/../../Files/empty.json'))
        ->toBeEmpty()
        ->toBeArray();
    expect(U::getJson(__DIR__ . '/../../Files/iDontExist.json'))
        ->toBeEmpty()
        ->toBeArray();
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
    [null, null],
]);
test('Utils::isDateBetweenDates', function ($date, $startDate, $endDate, $result) {
    expect(U::isDateBetweenDates(
        new DateTime($date),
        new DateTime($startDate),
        new DateTime($endDate)
    ))->toEqual($result);
})->with([
    ['2022-01-10', '2022-01-05', '2022-01-15', true],
    ['2022-01-01', '2022-01-05', '2022-01-15', false],
    ['2022-01-10', '2022-01-05', '2022-01-09', false],
]);

// Templating function, we capture the output
test('Utils::renderJson', function () {
    ob_start();
    U::renderJson(['aa']);
    $content = ob_get_contents();
    ob_end_clean();
    expect($content)
        ->toBeString()
        ->toEqual('["aa"]');

    ob_start();
    U::renderJson(['error' => 'an error']);
    $content = ob_get_contents();
    ob_end_clean();
    expect($content)
        ->toBeString()
        ->toEqual(  '{
    "error": "an error"
}');
});

test('Utils::inString', function ($a, $b, $c, $d) {
    expect(U::inString($a, $b, $c))->toEqual($d);
})->with([
    ['La maison est blanche', 'blanche', false, true],
    ['La maison est blanche', 'blanche', true, true],
    ['La maison est blanche', ['blanche', 'maison'], true, true],
    ['La maison est blanche', ['blanche', 'maison'], false, true],
    ['La maison est blanche', ['blanche', 'noire'], true, false],
    ['La maison est blanche', ['blanche', 'noire'], false, true],
    ['Le ciel est bleu', 'noir', false, false],
    ['Le ciel est bleu', 'Le', false, true],
]);

test('Utils::getCrashesForBuildID', function () {
    expect(U::getCrashesForBuildID(20190927094817))->toBeArray()->toBeEmpty();
    expect(U::getCrashesForBuildID(20200927094817))->toBeArray()->not->toBeEmpty();
});

test('Utils::getIP', function () {
    expect(U::getIP())->toBeNull();

    $_SERVER['HTTP_CLIENT_IP'] = '1.1.1.1';
    expect(U::getIP())->toBeString();
    unset($_SERVER['HTTP_CLIENT_IP']);

    $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.1.1.1';
    expect(U::getIP())->toBeString();
    unset($_SERVER['HTTP_X_FORWARDED_FOR']);

    $_SERVER['HTTP_X_FORWARDED'] = '1.1.1.1';
    expect(U::getIP())->toBeString();
    unset($_SERVER['HTTP_X_FORWARDED']);

    $_SERVER['HTTP_FORWARDED_FOR'] = '1.1.1.1';
    expect(U::getIP())->toBeString();
    unset($_SERVER['HTTP_FORWARDED_FOR']);

    $_SERVER['HTTP_FORWARDED'] = '1.1.1.1';
    expect(U::getIP())->toBeString();
    unset($_SERVER['HTTP_FORWARDED']);

    $_SERVER['REMOTE_ADDR'] = '1.1.1.1';
    expect(U::getIP())->toBeString();
    unset($_SERVER['REMOTE_ADDR']);
});


