<?php

declare(strict_types=1);

namespace ReleaseInsights;

class ESR
{
    public static $esr_releases = [10, 17, 24, 31, 38, 45, 52, 68, 78, 91, 102];

    /**
     * Get the ESR release that corresponds to the Rapid release version
     *
     * @param int $version  Firefox version
     *
     * @return string A Firefox ESR version number such as 78.8.0
     */
    public static function getVersion(int $version): string
    {
        $match = self::$esr_releases[0];

        foreach(self::$esr_releases as $esr) {
            if ($esr > $version) {
                break;
            }

            if ($esr <= $version) {
                $match = $esr;
            }
        }

        return (string) $match . '.' . ($version - $match ) . '.0';
    }
}
