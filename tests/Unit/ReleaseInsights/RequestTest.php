<?php

declare(strict_types=1);

use ReleaseInsights\Request;

test('Request->getController()', function ($input, $output) {
    expect($output)->toEqual((new Request($input))->getController());
})->with([
    ['/',                                 'homepage'],
    ['//',                                'homepage'],
    ['//yolo',                            'homepage'],
    ['/about',                            'about'],
    ['/nightly',                          'nightly'],
    ['/beta',                             'beta'],
    ['/release',                          'release'],
    ['/release//',                        'release'],
    ['/release/?version=100',             'release'],
    ['/api',                              'api_doc'],
    ['/api/beta/crashes',                 'api/beta_crashes'],
    ['/api/external',                     'api/external'],
    ['/api/nightly',                      'api/nightly'],
    ['/api/release/schedule',             'api/release_schedule'],
    ['/api/release/schedule/ios/',             'api/ios_release_schedule'],
    ['/api/release/owners',               'api/release_owners'],
    ['/api/nightly/crashes',              'api/nightly_crashes'],
    ['/api/firefox/chemspills/',          'api/chemspill_releases'],
    ['/api/firefox/releases/',            'api/firefox_releases'],
    ['/api/firefox/releases/esr/',        'api/esr_release_pairs'],
    ['/api/firefox/releases/esr/future/', 'api/esr_release_pairs'],
    ['/api/firefox/calendar/future/',     'api/future_calendar'],
    ['/api/firefox/releases/future/',     'api/firefox_future_releases'],
    ['/api/esr/releases',                 'api/esr_releases'],
    ['/api/wellness/days',                'api/wellness_days'],
    ['/calendar/',                        'calendar'],
    ['/calendar/monthly/',                'calendar_monthly'],
    ['/calendar/future/releases/',        'ics_future_major_releases'],
    ['/calendar/release/schedule',        'ics_release_schedule'],
    ['/release/owners',                   'release_owners'],
    ['/release/owners',                   'release_owners'],
    ['/release-notes',                    'relnotes_doc'],
    ['/rss',                              'rss'],
    ['/sitemap/',                         'sitemap'],
    ['not a good path',                   '404'],
    ['not/a/goodpath',                    '404'],
    ['/changelog/',                       'changelog'],
]);

test('Request->cleanPath()', function ($input, $output) {
    expect($output)->toEqual((new Request($input))->cleanPath($input));
})->with([
    ['/',       '/'],
    ['//',      '/'],
    ['/about',  '/about/'],
    ['nightly', '/nightly/'],
]);
