<?php

declare(strict_types=1);

use ReleaseInsights\Request ;

test('Request->getController()', function ($input, $output) {
    expect($output)->toEqual((new Request($input))->getController());
})->with([
    ['/', 							'homepage'],
    ['about', 						'about'],
    ['nightly', 					'nightly'],
    ['release', 					'release'],
    ['api/nightly', 				'api/nightly'],
    ['api/release/schedule', 		'api/release_schedule'],
    ['api/release/owners', 			'api/release_owners'],
    ['api/nightly/crashes', 		'api/nightly_crashes'],
    ['calendar/release/schedule', 	'ics_release_schedule'],
    ['not a good path', 			'404'],
    ['not/a/goodpath', 				'404'],
]);
