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
        if (!$crashes = Cache::getKey($cache_id)) {
            $crashes = file_get_contents($cache_id);
        }

        return json_decode($crashes, true);
    }

}
