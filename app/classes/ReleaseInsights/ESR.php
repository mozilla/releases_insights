<?php

declare(strict_types=1);

namespace ReleaseInsights;

class ESR
{
    /**
     *  @var array<int> $esr_releases
     */
    public static array $esr_releases = [10, 17, 24, 31, 38, 45, 52, 60, 68, 78, 91, 102, 115];

    /**
     * Get the ESR release that corresponds to the Rapid release version.
     * Return null if there is none.
     */
    public static function getVersion(int $version): ?string
    {
        // We don't have an older ESR than the first ESR
        if ($version < 10) {
            return null;
        }

        // For very future versions, safeguard to ESR + 13 versions
        if ($version > self::$esr_releases[count(self::$esr_releases)-1] + 13) {
            return null;
        }

        $match = self::$esr_releases[0];

        foreach (self::$esr_releases as $esr) {
            if ($esr > $version) {
                break;
            }

            if ($esr <= $version) {
                $match = $esr;
            }
        }

        return (string) $match . '.' . ($version - $match) . '.0';
    }

    /**
     * Get the previous ESR release that corresponds to the Rapid release version
     * and that is still supported. Return null if there is none.
     */
    public static function getOlderSupportedVersion(int $version): ?string
    {
        $current_ESR = self::getVersion($version);

        // We can't find a matching ESR, return now to avoid PHP warnings
        if (is_null($current_ESR)) {
            return null;
        }

        $current_ESR = Utils::getMajorVersion($current_ESR);

        // We don't have an older ESR than the first ESR
        if (self::$esr_releases[0] == $current_ESR) {
            return null;
        }

        $previous_ESR = self::$esr_releases[
            array_search(
                $current_ESR,
                self::$esr_releases
            )-1
        ];

        // We support 2 ESR branches for 3 releases only since Version 68.
        // Before that, we had 2 cycles only with 2 ESR branches
        // because cycles lasted longer
        if (($version - $current_ESR) > ($version < 78 ? 1 : 2)) {
            return null;
        }
        return (string) $previous_ESR . '.' . ($version - $previous_ESR) . '.0';
    }

    /**
     * Get a XX.YY version number from a full ESR number like 91.4.1esr
     * We drop the dot release part
     */
    public static function getMainDotVersion(?string $version): string
    {
        // This is a safety net as we may loop on a range with a version we didn't ship
        if (is_null($version)) {
            return '';
        }

        $version = explode('.', $version);
        array_pop($version);

        return implode('.', $version);
    }
}
