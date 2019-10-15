<?php
namespace ReleaseInsights;
use Cache\Cache;

class Utils
{
    /* Utility function to include a file and return the output as a string */
    public static function includeBuffering(string $file): string
    {
        ob_start();
        include $file;
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    public static function getCrashesForBuildID(int $buildid) : array
    {
        // The date in the string varies so we create a unique file name in cache
        $cache_id = 'https://crash-stats.mozilla.com/api/SuperSearch/?build_id=' . $buildid . '&_facets=signature';

        // If we can't retrieve cached data, we create and cache it.
        // We cache because we want to avoid http request latency
        if (!$data = Cache::getKey($cache_id)) {
            $data = file_get_contents($cache_id);

           // No data returned, bug or incorrect date, don't cache.
            if (empty($data)) {
                return [];
            }
            Cache::setKey($cache_id, $data);
        }

        return json_decode($data, true);
    }

    public static function getDate() : string
    {
        // Make sure we have a date, cast user provided string to an int for security
        return isset($_GET['date']) ? (int) $_GET['date'] : date('Ymd');
    }

}
