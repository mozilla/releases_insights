<?php
namespace ReleaseInsights;

use Cache\Cache;
use DateTime;

class Utils
{
    public static function getCrashesForBuildID(int $buildid) : array
    {
        // The date in the string varies so we create a unique file name in cache
        $cache_id = 'https://crash-stats.mozilla.com/api/SuperSearch/?build_id=' . $buildid . '&_facets=signature&product=Firefox';

        // If we can't retrieve cached data, we create and cache it.
        // We cache because we want to avoid http request latency
        if (!$data = Cache::getKey($cache_id, 30)) {
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

        return self::secureText((string) $date);
    }

    public static function getBuildID(string $buildid) : string
    {
        // Check that the string provided is correct
        if (!self::isBuildID($buildid)) {
            return '20191014213051'; // hardcoded fallback value
        }

        return self::secureText($buildid);
    }

    public static function isBuildID(string $buildid) : bool
    {
        //  BuildIDs should be 14 digits
        if (strlen($buildid) !== 14) {
            return false;
        }

        //  BuildIDs should be valid dates, if we can't create a date return false
        if (!$date = date_create($buildid)) {
            return false;
        }

        // The date shouldn't be in the future
        $date  = new DateTime($buildid);
        $today = new DateTime();

        if ($date > $today) {
            return false;
        }

        return true;
    }
    /**
     * Sanitize a string for security before template use.
     *
     * @param string $string The string we want to sanitize
     *
     * @return string Sanitized string for security
     */
    public static function secureText(string $string) : string
    {
        // CRLF XSS
        $string = str_replace(['%0D', '%0A'], '', $string);
        // We want to convert line breaks into spaces
        $string = str_replace("\n", ' ', $string);
        // Escape HTML tags and remove ASCII characters below 32
        $string = filter_var(
            $string,
            FILTER_SANITIZE_SPECIAL_CHARS,
            FILTER_FLAG_STRIP_LOW
        );

        return $string;
    }

    public static function getJson(string $url, int $ttl = 0) : array
    {
        if (!$data = Cache::getKey($url, $ttl = 0)) {
            $data = file_get_contents($url);

           // No data returned, bug or incorrect date, don't cache.
            if (empty($data)) {
                return [];
            }
            Cache::setKey($url, $data);
        }

        return json_decode($data, true);
    }

    public static function mtrim(string $string) : string
    {
        $string = explode(' ', $string);
        $string = array_filter($string);
        $string = implode(' ', $string);

        return $string;
    }

    /**
     * Check if $haystack starts with a string in $needles.
     * $needles can be a string or an array of strings.
     *
     * @param string $haystack String to analyse
     * @param array  $needles  The string to look for
     *
     * @return bool True if the $haystack string starts with a string in $needles
     */
    public static function startsWith($haystack, $needles) : bool
    {
        foreach ((array) $needles as $prefix) {
            if (!strncmp($haystack, $prefix, mb_strlen($prefix))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if $needles are in $haystack.
     *
     * @param string $haystack  String to analyze
     * @param mixed  $needles   The string (or array of strings) to look for
     * @param bool   $match_all True if we need to match all $needles, false
     *                          if it's enough to match one. Default: false
     *
     * @return bool True if the $haystack string contains any/all $needles
     */
    public static function inString($haystack, $needles, $match_all = false)
    {
        $matches = 0;
        foreach ((array) $needles as $needle) {
            if (mb_strpos($haystack, $needle, $offset = 0, 'UTF-8') !== false) {
                // If I need to match any needle, I can stop at the first match
                if (!$match_all) {
                    return true;
                }
                $matches++;
            }
        }

        if (!$match_all) {
            return false;
        }

        return $matches == count($needles);
    }

    /**
     * Utility function to get symfony dump() function output to the CLI
     * http://symfony.com/doc/current/components/var_dumper/
     */
    function cli_dump() : void
    {
        $cloner = new \Symfony\Component\VarDumper\Cloner\VarCloner();
        $dumper = new \Symfony\Component\VarDumper\Dumper\CliDumper();
        foreach (func_get_args() as $arg) {
            $dumper->dump($cloner->cloneVar($arg));
        }
    }
}
