<?php

declare(strict_types=1);

use Cache\Cache;

test('Cache::setKey', function () {
    // We set and then unset the cache explicitely to not interfere with other tests
    // that use the caching system set globally to false
    Cache::$CACHE_ENABLED = true;
    expect(Cache::setKey('Unique ID', 'this string to put in a cached file'))->toBeTrue();
    expect(Cache::setKey('Unique ID 2', 'This is immutable data', -1))->toBeTrue();
    expect(Cache::setKey('Unique ID 3', 'I am data designed to be expired'))->toBeTrue();
    touch(Cache::getCachePath() . sha1('Unique ID 3') . '.cache', time() - 100);

    Cache::$CACHE_ENABLED = false;
});

test('Cache::getKey', function () {
    Cache::$CACHE_ENABLED = true;
    expect(Cache::getKey('Unique ID'))->toEqual('this string to put in a cached file');
    expect(Cache::getKey('Unique ID 2', -1))->toEqual('This is immutable data');
    expect(Cache::getKey('Unique ID 3', 1))->toBeFalse();
    expect(Cache::getKey('Unique ID which was never set', -1))->toBeFalse();
    Cache::$CACHE_ENABLED = false;
});

test('Cache::deleteKey', function () {
    Cache::$CACHE_ENABLED = true;
    expect(Cache::deleteKey('Unique ID 2', true))->toBeTrue();
    expect(Cache::deleteKey('I am a file not in cache'))->toBeFalse();

    // Create a file key we will lock to test that we can't delete it
    Cache::setKey('Unique ID', 'I am data designed to be expired');

    // Lock The file by making it read-only
    chmod(Cache::getKeyPath('Unique ID'), 0444);

    // We expect it to return false now
    expect(Cache::deleteKey('Unique ID'))->toBeFalse();

    Cache::$CACHE_ENABLED = false;
});

test('Cache::isActivated', function () {
    Cache::$CACHE_ENABLED = true;
    expect(Cache::isActivated())->toBeTrue();
    Cache::$CACHE_ENABLED = false;
    expect(Cache::isActivated())->toBeFalse();
});

test('Cache::flush', function () {
    expect(Cache::flush())->toBeTrue();
});


// Change the timestamp to 100 seconds in the past so we can test expiration
