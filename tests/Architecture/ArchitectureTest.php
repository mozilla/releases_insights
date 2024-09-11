<?php
arch()
   ->preset()
   ->php()
   ->ignoring(['debug_backtrace', 'die']);

test("Classes don't have stray debug calls")
    ->expect(['dd', 'dump', 'var_dump', 'error_log', 'ReleaseInsights\Debug'])
    ->not->toBeUsed()
    ->ignoring('ReleaseInsights\Performance');

test('Classes use strict types')
    ->expect(['ReleaseInsights', 'Cache'])
    ->toUseStrictTypes();

test('The Cache class should not depend on the app')
    ->expect('Cache')
    ->toUseNothing();

test('Don`t use the app core classes in other namespaces')
    ->expect('ReleaseInsights')
    ->toOnlyBeUsedIn('ReleaseInsights');
