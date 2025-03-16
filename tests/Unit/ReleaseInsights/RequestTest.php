<?php

declare(strict_types=1);

use ReleaseInsights\Request;

test('Request->getController()', function ($input, $output) {
    expect($output)->toEqual((new Request($input))->getController());
})->with([
    ['/',                           'homepage'],
    ['//',                          'homepage'],
    ['//yolo',                      'homepage'],
    ['/about',                      'about'],
    ['/nightly',                    'nightly'],
    ['/beta',                       'beta'],
    ['/release',                    'release'],
    ['/release//',                  'release'],
    ['/release/?version=100',       'release'],
    ['/api/beta/crashes',           'api/beta_crashes'],
    ['/api/external',               'api/external'],
    ['/api/nightly',                'api/nightly'],
    ['/api/release/schedule',       'api/release_schedule'],
    ['/api/release/owners',         'api/release_owners'],
    ['/api/nightly/crashes',        'api/nightly_crashes'],
    ['/api/firefox/releases/',      'api/firefox_releases'],
    ['/api/firefox/calendar/future/', 'api/future_calendar'],
    ['/api/firefox/releases/future/', 'api/firefox_releases_future'],
    ['/api/esr/releases',           'api/esr_releases'],
    ['/api/wellness/days',          'api/wellness_days'],
    ['/calendar/',                  'calendar'],
    ['/calendar/monthly/',          'calendar_monthly'],
    ['/calendar/release/schedule',  'ics_release_schedule'],
    ['/release/owners',             'release_owners'],
    ['/release/owners',             'release_owners'],
    ['/rss',                        'rss'],
    ['/sitemap/',                   'sitemap'],
    ['not a good path',             '404'],
    ['not/a/goodpath',              '404'],
]);


test('Request->cleanPath()', function ($input, $output) {
    expect($output)->toEqual((new Request($input))->cleanPath($input));
})->with([
    ['/',       '/'],
    ['//',      '/'],
    ['/about',  '/about/'],
    ['nightly', '/nightly/'],
]);
