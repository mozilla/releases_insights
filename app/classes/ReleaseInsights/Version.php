<?php
 
declare(strict_types=1);
 
namespace ReleaseInsights;

class Version
{
    /**
     * Get the version number provided by the user in the query string
     * via the $_GET['version'] global and return a sanitized for a major
     * version number.
     *
     * beta, release and nightly are aliases
     *
     * For detection, the values rely on the FIREFOX_RELEASE, FIREFOX_BETA,
     * FIREFOX_NIGHTLY, ESR global constants.
     *
     * @param string $version Force a Firefox version
     *
     * @return string A Firefox version number such as 82.0
     */
    public static function get(?string $version = null): string
    {
        if (! $version) {
            if (! isset($_GET['version']) || $_GET['version'] === 'beta') {
                $version = FIREFOX_BETA;
            } elseif ($_GET['version'] === 'release') {
                $version = FIREFOX_RELEASE;
            } elseif ($_GET['version'] === 'nightly') {
                $version = FIREFOX_NIGHTLY;
            } elseif ($_GET['version'] === 'esr') {
                $version = ESR;
            } else {
                $version = $_GET['version'];
            }
        }

        // Normalize version number to XX.y
        return (string) number_format(abs((int) $version), 1, '.', '');
    }
 
    /**
     * Get the major version number (91) from a string such as 91.0.1
     */
    public static function getMajor(string $version): int
    {
        return (int) explode('.', $version)[0];
    }

    /**
     * Decrement a version number (91) provided as a string such as 91.0
     */
    public static function decrement(string $version, int $decrement): string
    {

        if ((int) $version - $decrement <= 1) {
            return '1.0';
        }

        return (string) ((int) $version - $decrement) . '.0';;
    }
}
