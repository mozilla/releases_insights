<?php
declare(strict_types=1);

namespace ReleaseInsights;

use DateTimeImmutable;
use DateTimeZone;

final class IosSchedule
{
    private const MERGE_OFFSET_DAYS   = -11; // X.0 Merge = Desktop NEXT_RELEASE_DATE - 11
    private const ROLLOUT_OFFSET_DAYS =  9;  // rollout = merge + 9 (Sunday)
    private const STEP_DAYS           =  7;  // next weekly merge

    /** @return array<int, array{version:string, merge:DateTimeImmutable, rollout:DateTimeImmutable}> */
    public function buildFromDesktopNextReleaseDate(
        int $major,
        DateTimeImmutable $desktopNextReleaseDate,
        int $weeks = 4,
        ?DateTimeZone $tz = null
    ): array {
        $tz ??= new DateTimeZone('UTC');

        // normalize to date-only to avoid DST display quirks
        $anchor = new DateTimeImmutable($desktopNextReleaseDate->format('Y-m-d'), $tz);
        $merge0 = $anchor->modify(self::MERGE_OFFSET_DAYS . ' days');

        $rows = [];
        for ($i = 0; $i < $weeks; $i++) {
            $merge   = $merge0->modify('+' . ($i * self::STEP_DAYS) . ' days');
            $rollout = $merge->modify('+' . self::ROLLOUT_OFFSET_DAYS . ' days');
            $rows[] = [
                'version' => sprintf('%d.%d', $major, $i),
                'merge'   => $merge,
                'rollout' => $rollout,
            ];
        }
        return $rows;
    }
}
