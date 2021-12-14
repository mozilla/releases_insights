<?php

declare(strict_types=1);

namespace ReleaseInsights;

class ESR
{
    public static $esr_releases = [10, 17, 24, 31, 38, 45, 52, 60, 68, 78, 91, 102];

    /**
     * Get the ESR release that corresponds to the Rapid release version
     */
    public static function getVersion(int $version): string
    {
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
        $current_ESR = Utils::getMajorVersion($current_ESR);
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
}
