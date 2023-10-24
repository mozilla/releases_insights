<?php

declare(strict_types=1);

namespace Cache;

/**
 * Cache class
 *
 * A simple and fast file caching system.
 *
 * 3 global constants are used: CACHE_ENABLED, CACHE_PATH and CACHE_TIME
 * If those app constants are not available, the system temp folder
 * and the class variables $CACHE_ENABLED and $CACHE_TIME are used.
 *
 * @package Cache
 */
class Cache
{
    /*
        Fallback for activation of Cache
     */
    public static bool $CACHE_ENABLED = false;

    /*
        Cache expiration time (seconds)
     */
    public static int $CACHE_TIME = 3600;

    /**
     * Create a cache file with serialized data
     *
     * We use PHP serialization, and not other formats like Json for example,
     * as it allows storing not only data but also data representations and
     * instantiated objects.
     *
     * @param string $id   UID of the cache
     * @param mixed  $data Data to store
     * @param int    $ttl  Time to live (seconds) for the cached data, defaults to 0
     *
     * @return bool True if cache file is created
     *              False if there was an error
     */
    public static function setKey(string $id, mixed $data, int $ttl = 0): bool
    {
        if (! self::isActivated()) {
            return false;
        }

        $immutable = ($ttl === -1) ? true : false;

        return file_put_contents(self::getKeyPath($id, $immutable), serialize($data)) ? true : false;
    }

    /**
     * Get the cached serialized data via its UID
     *
     * @param string $id  UID of the cache
     * @param int    $ttl Number of seconds for time to live.
     *                    Defaults to 0 which calls the default duration. -1 means forever.
     *
     * @return mixed Unserialized cached data, or false
     */
    public static function getKey(string $id, int $ttl = 0): mixed
    {
        // By default, we cache data that is mutable over time and has an expiry date
        $immutable = false;

        if (! self::isActivated()) {
            return false;
        }

        if ($ttl === 0) {
            $ttl = defined('CACHE_TIME') ? CACHE_TIME : self::$CACHE_TIME;
        }

        // External immutable data, we keep this data almost forever (30 years here)
        if ($ttl === -1) {
            $immutable = true;
        }

        return self::isValidKey($id, $ttl)
               ? unserialize(file_get_contents(self::getKeyPath($id, $immutable)))
               : false;
    }

    /**
     * Flush the current cache
     *
     * @return bool True if files in cache are deleted
     *              False if some files were not deleted
     */
    public static function flush(): bool
    {
        $files = glob(self::getCachePath() . '*.cache');

        return ! in_array(false, array_map('unlink', $files));
    }

    /**
     * Is the caching system activated?
     * We look if the CACHE constant is available and if it's set to True
     *
     * @return bool True if activated
     *              False if deactivated
     */
    public static function isActivated(): bool
    {
        // We don't want a global switch for the cache in Unit Tests
        // because we want to test functions with and without caching.
        if (defined('TESTING_CONTEXT')) {
            return self::$CACHE_ENABLED;
        }

        return defined('CACHE_ENABLED') ? CACHE_ENABLED : self::$CACHE_ENABLED; // @codeCoverageIgnore
    }

    /**
     * Delete a cache file based to its UID
     *
     * @param string $id        UID of the cached data
     * @param bool   $immutable Is that immutable data? Default to false
     *
     * @return bool True if data was deleted
     *              False if it doesn't exist
     */
    public static function deleteKey(string $id, bool $immutable = false): bool
    {
        $file = self::getKeyPath($id, $immutable);

        // Make sure this wasn't already deleted and the server file cache is lying
        clearstatcache(true, $file);

        if (! file_exists($file)) {
            return false;
        }

        // if there is a lock on the file, we can't delete it,
        // Can happen if it's being created by another php process
        if (! is_writable($file)) {
            return false;
        }

        return unlink($file);
    }

    /**
     * Get the path to the cached file
     *
     * Filename is in the form a840d513be5240045ccc979208f739a168946332.cache
     * Immutable cached files are in the form a840d513be5240045ccc979208f739a168946332.immutable
     *
     * @param string $id UID of the cached file
     * @param bool   $immutable is that immutable data? Default to false
     *
     * @return string Path to the file
     */
    public static function getKeyPath(string $id, bool $immutable = false): string
    {
        return self::getCachePath() . sha1($id) . ($immutable ? '.immutable' : '.cache');
    }

    /**
     * Get the path to the cache folder
     *
     * Use a CACHE_PATH global constant if defined, otherwise use OS
     * default folder for temporary files.
     *
     * @return string Path to cache folder
     */
    public static function getCachePath(): string
    {
        return defined('CACHE_PATH') ? CACHE_PATH : sys_get_temp_dir() . '/';
    }

    /**
     * Check if cached data for a key is usable
     *
     * @param string  $id  UID for the data
     * @param int     $ttl Number of seconds for time to live
     *
     * @return bool True if valid data
     *              False if cached data is not usable
     */
    private static function isValidKey(string $id, int $ttl): bool
    {
        $immutable = ($ttl === -1) ? true : false;

        // No cache file
        if (! file_exists(self::getKeyPath($id, $immutable))) {
            return false;
        }

        // Cache is obsolete and was deleted
        if (self::isObsoleteKey($id, $ttl)) {
            self::deleteKey($id);

            return false;
        }

        // All good, cache is valid
        return true;
    }

    /**
     * Check if the data has not expired
     *
     * @param string $id  UID of the cached file
     * @param int    $ttl Number of seconds for time to live
     *
     * @return bool True if file is obsolete
     *              False if it is still usable
     */
    private static function isObsoleteKey(string $id, int $ttl): bool
    {
        // Immutable data is never obsolete
        if ($ttl === -1) {
            return false;
        }

        return filemtime(self::getKeyPath($id)) < time() - $ttl;
    }
}
