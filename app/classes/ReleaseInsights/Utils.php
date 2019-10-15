<?php
namespace ReleaseInsights;
use Cache\Cache;
use DateTime;

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

        // No date provided by the http call, return Today
        if (!isset($_GET['date'])) {
            return date('Ymd');
        }

        // Cast user provided date to an int for security
        $date = (int) $_GET['date'];

        return self::secureText($date);
    }

    public static function getBuildID() : string
    {
        $fallback_buildid = '20191014213051';

        // No buildid provided by the http call, return a default value
        if (!isset($_GET['buildid'])) {
            return $fallback_buildid;
        }

        // Check that the string provided is correct
        if (!self::isBuildID($_GET['buildid'])) {
            return $fallback_buildid;
        }

        return self::secureText($_GET['buildid']);

    }

    public static function isBuildID($buildid) : bool
    {
        //  BuildIDs should be 14 digits
        if (strlen($buildid) !==  14) {
            return false;
        }

        //  BuildIDs should be valid dates, if we can't create a date return false
        if (!$date = date_create($buildid)) {
            return false;
        }

        // The date shouldn't be in the future
        $date = new DateTime($buildid);
        $today = new DateTime();

        if ($date > $today) {
            return false;
        }

        return true;
    }
    /**
     * Sanitize a string or an array of strings for security before template use.
     *
     * @param string $string The string we want to sanitize
     *
     * @return string Sanitized string for security
     */
    public static function secureText($string)
    {
        $sanitize = function ($v) {
            // CRLF XSS
            $v = str_replace(['%0D', '%0A'], '', $v);
            // We want to convert line breaks into spaces
            $v = str_replace("\n", ' ', $v);
            // Escape HTML tags and remove ASCII characters below 32
            $v = filter_var(
                $v,
                FILTER_SANITIZE_SPECIAL_CHARS,
                FILTER_FLAG_STRIP_LOW
            );

            return $v;
        };

        return is_array($string) ? array_map($sanitize, $string) : $sanitize($string);
    }


}
