<?php

declare(strict_types=1);

namespace ReleaseInsights;

use Cache\Cache;
use DateTime;
use GuzzleHttp\Client;

class Utils
{
    /**
     * Get the list of crashes for a Build ID from Socorro
     *
     * @param int $buildid Firefox build ID
     *
     * @return array<mixed> a list of crashes
     */
    public static function getCrashesForBuildID(int $buildid): array
    {
        // We only fetch Desktop builds crashes, I haven't found out how to get Fenix crashes per buildID yet
        $cache_id = URL::Socorro->value . 'SuperSearch/?build_id=' . $buildid . '&_facets=signature&product=Firefox';

        if (defined('TESTING_CONTEXT')) {
            $cache_id = TEST_FILES .'/crash-stats.mozilla.org.json';
            if ($buildid == '20190927094817') {
                $cache_id = TEST_FILES .'/empty.json';
            }
        }

        return Json::load(url: $cache_id, ttl: 30);
    }

    /**
     * Get the list of bugs for a Build ID from Socorro
     *
     * @param string $signature Crash signature
     * @return array<mixed> a list of crashes
     */
    public static function getBugsforCrashSignature(string $signature): array
    {
        // The signature in the string varies so we create a unique file name in cache
        $cache_id = URL::Socorro->value . 'Bugs/?signatures=' . $signature;

        if (defined('TESTING_CONTEXT')) {
            $cache_id = TEST_FILES .'/crash-stats.mozilla.org_signature.json';

            if ($signature == 'failure') {
                $cache_id = TEST_FILES .'/empty.json';
            }
        }

        // If we can't retrieve cached data, we create and cache it.
        // We cache because we want to avoid http request latency
        if (! $data = Cache::getKey($cache_id, 30)) {
            $data = file_get_contents($cache_id);

            // Error fetching data, don't cache.
            // @codeCoverageIgnoreStart
            if ($data === false) {
                return ['error' => 'URL provided no data'];
            }
            // @codeCoverageIgnoreEnd

            // No data returned, bug or incorrect date, don't cache.
            if (empty($data)) {
                return [];
            }
            Cache::setKey($cache_id, $data);
        }

        return Json::toArray($data);
    }

    /**
     * Get a date provided by the user in the query string.
     *Fallback to today's date.
     *
     * @return string Date as a Ymd string
     */
    public static function getDate(string $format = 'Ymd'): string
    {
        // No date provided by the http call, return Today
        if (! isset($_GET['date'])) {
            return date($format);
        }

        // Magical 'today' value
        if ($_GET['date'] === 'today') {
            return date($format);
        }

        // Cast user provided date to an int for security
        $date = Utils::secureText($_GET['date']);

        $d = DateTime::createFromFormat($format, $date);

        // Date is invalid, return Today
        if (! $d) {
            return date($format);
        }

        return $d->format($format);
    }

    /**
     * Get a Firefox BuildID and sanitize it
     *
     * @param int $buildid Firefox Build ID in format 20191014213051
     *
     * @return int sanitized buildID
     */
    public static function getBuildID(int $buildid): int
    {
        // Check that the string provided is correct
        if (! self::isBuildID((string) $buildid)) {
            return 20191014213051; // hardcoded fallback value
        }

        return $buildid;
    }

    public static function isBuildID(string $buildid): bool
    {
        //  BuildIDs should be 14 digits
        if (strlen($buildid) !== 14) {
            return false;
        }

        //  BuildIDs should be valid dates, if we can't create a date return false
        if (! $date = date_create($buildid)) {
            return false;
        }

        // The date shouldn't be in the future
        $date  = (new DateTime($buildid))->format('Ymd');
        $today = (new DateTime())->format('Ymd');

        if ($date > $today) {
            return false;
        }

        return true;
    }

    /**
     * Sanitize a string for security before template use.
     * This is in addition to twig default sanitizinf for cases
     * where we may want to disable it.
     */
    public static function secureText(string $string): string
    {
        // CRLF XSS
        $string = str_replace(['%0D', '%0A'], '', $string);
        // We want to convert line breaks into spaces
        $string = str_replace("\n", ' ', $string);
        // Escape HTML tags and remove ASCII characters below 32
        return filter_var(
            $string,
            FILTER_SANITIZE_SPECIAL_CHARS,
            FILTER_FLAG_STRIP_LOW
        );
    }

    /**
     * getFile code coverage is done through its main consumer Json::load()
     */
    public static function getFile(string $url): string|bool
    {
        // Local file
        if (! isset(parse_url($url)['scheme'])) {
            // Does it exist ?
            if (! file_exists($url)) {
                return '';
            }

            return file_get_contents($url);
        }

        // We don't want to make external requests in Unit Tests
        // @codeCoverageIgnoreStart
        $client = new Client([
            'headers' => [
                'User-Agent' => 'WhatTrainIsItNow/1.0',
                'Referer'    => 'https://whattrainisitnow.com'
            ]
        ]);

        // We know that some queries fail for hg.mozilla.org but we deal with that in templates
        // We ignore warnings for 404 errors as we don't want to spam Sentry
        $response = $client->request('GET', $url, ['http_errors' => false]);

        // Request to Product-details failed (no answer from remote)
        // We prefer to die here because this data is essential to the whole app.
        if ($response->getStatusCode() != 200 && str_contains($url, 'product-details.mozilla.org')) {
            die("Key external ressource {$url} currently not available, please try reloading the page.");
        }

        // Request failed, let's return an empty string for now
        if ($response->getStatusCode() != 200) {
            return '';
        }

        return $response->getBody()->getContents();
        // @codeCoverageIgnoreEnd
    }

    public static function mtrim(string $string): string
    {
        $string = explode(' ', $string);
        $string = array_filter($string);

        return implode(' ', $string);
    }

    /**
     * Check if $haystack starts with a string in $needles.
     * $needles can be a string or an array of strings.
     *
     * @param string $haystack String to analyse
     * @param string|array<string>  $needles  The string to look for
     *
     * @return bool True if the $haystack string starts with a string in $needles
     */
    public static function startsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if (str_starts_with($haystack, $needle)) {
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
    public static function inString(string $haystack, mixed $needles, bool $match_all = false): bool
    {
        $needles = (array) $needles;
        $matches = 0;
        foreach ((array) $needles as $needle) {
            // Missing needle
            if (! str_contains($haystack, (string) $needle) && $match_all) {
                return false;
            }

            if (str_contains($haystack, (string) $needle)) {
                // If I need to match any needle, I can stop at the first match
                if (! $match_all) {
                    return true;
                }
                $matches++;
            }
        }

        if (! $match_all) {
            return false;
        }

        return $matches == count($needles) > 0;
    }

    /**
     * Utility function to get symfony dump() function output to the CLI
     * http://symfony.com/doc/current/components/var_dumper/
     *
     * @codeCoverageIgnore
     */
    public static function dump(): void
    {
        if (! class_exists(\Symfony\Component\VarDumper\Dumper\CliDumper::class)) {
            return;
        }

        $cloner = new \Symfony\Component\VarDumper\Cloner\VarCloner();
        $dumper = new \Symfony\Component\VarDumper\Dumper\CliDumper();
        foreach (func_get_args() as $arg) {
            $dumper->dump($cloner->cloneVar($arg));
        }
    }

    /**
     * @param DateTime $date Date that is to be checked if it falls between $startDate and $endDate
     * @param DateTime $startDate Date should be after this date to return true
     * @param DateTime $endDate Date should be before this date to return true
     */
    public static function isDateBetweenDates(DateTime $date, DateTime $startDate, DateTime $endDate): bool
    {
        return $date > $startDate && $date < $endDate;
    }
}
