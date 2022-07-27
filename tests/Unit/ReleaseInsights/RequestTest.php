<?php

declare(strict_types=1);

use ReleaseInsights\Request;

test('Request->getController()', function ($input, $output) {
    expect($output)->toEqual((new Request($input))->getController());
})->with([
    ['/',                           'homepage'],
    ['/about',                      'about'],
    ['/nightly',                    'nightly'],
    ['/release',                    'release'],
    ['/release//',                  'release'],
    ['/release/?version=100',       'release'],
    ['/api/nightly',                'api/nightly'],
    ['/api/release/schedule',       'api/release_schedule'],
    ['/api/release/owners',         'api/release_owners'],
    ['/api/nightly/crashes',        'api/nightly_crashes'],
    ['/api/firefox/releases/',      'api/firefox_releases'],
    ['/api/esr/releases',           'api/esr_releases'],
    ['/calendar/',                  'calendar'],
    ['/calendar/release/schedule',  'ics_release_schedule'],
    ['/release/owners',             'release_owners'],

    ['not a good path',             '404'],
    ['not/a/goodpath',              '404'],
]);


test('Request::cleanPath', function ($input, $output) {
    expect($output)->toEqual(Request::cleanPath($input));
})->with([
    ['/',       '/'],
    ['//',      '/'],
    ['/about',  '/about/'],
    ['nightly', '/nightly/'],
]);
