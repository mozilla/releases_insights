<?php

declare(strict_types=1);

use ReleaseInsights\Json;

test('Json::load', function () {
    expect(Json::load(__DIR__ . '/../../Files/firefox_versions.json'))->toBeArray();
    expect(Json::load('iDontExist.json'))
        ->toBe(['error' => 'URL provided no data'])
        ->toBeArray();
    expect(Json::load(__DIR__ . '/../../Files/empty.json'))
        ->toBe(['error' => 'URL provided no data'])
        ->toBeArray();
    expect(Json::load(__DIR__ . '/../../Files/bad.json'))
        ->toBe(['error' => 'Invalid JSON source'])
        ->toBeArray();
});

// Templating function, we capture the output
test('Json->render()', function () {
    ob_start();
    (new Json(['aa']))->render();
    $content = ob_get_contents();
    ob_end_clean();
    expect($content)
        ->toBeString()
        ->toEqual('["aa"]');

    $obj = new Json(['aa']);
    $_GET['callback'] = "myfunc";
    ob_start();
    $obj->render();
    $content = ob_get_contents();
    ob_end_clean();
    expect($content)
        ->toBeString()
        ->toEqual('myfunc(["aa"])');

    ob_start();
    (new Json(['error' => 'an error']))->render();
    $content = ob_get_contents();
    ob_end_clean();
    expect($content)
        ->toBeString()
        ->toEqual(  '{
    "error": "an error"
}');
});