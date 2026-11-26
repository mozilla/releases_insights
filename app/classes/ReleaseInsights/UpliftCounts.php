<?php

declare(strict_types=1);

namespace ReleaseInsights;

use Cache\Cache;

/**
 * Number of bugs uplifted in Mercurial logs, used by the uplift graphs and tables.
 *
 * Raw logs of shipped releases never change, they are cached forever in
 * .immutable files committed to the repository. Counts are derived from those
 * logs and are only cached in regular cache files: when the log parsing in
 * Bugzilla::getBugsFromHgWeb() improves, bump PARSER_VERSION and all past
 * cycles are recounted from the committed logs, without fetching them again.
 */
class UpliftCounts
{
    /** Bump when Bugzilla::getBugsFromHgWeb() changes how bugs are counted */
    public const int PARSER_VERSION = 1;

    /** Counts of shipped releases only change with the parser */
    public const int PAST_TTL = 86400 * 365;

    /** Branches of the cycle in progress receive uplifts daily */
    public const int IN_PROGRESS_TTL = 900;

    /** First Firefox cycle with ESR dot releases tagged FIREFOX_X_Y_0esr_RELEASE */
    public const int ESR_FIRST_VERSION = 26;

    /**
     * After a failed fetch (hg WAF, timeout…) we stop fetching logs for a while,
     * missing counts are fetched on a later page load.
     */
    public const int BLOCKED_TTL = 600;
    private const string BLOCKED_KEY = 'uplift_logs_fetch_blocked';
    private static bool $fetch_failed = false;

    public static function cacheKey(string $id): string
    {
        return 'uplift_count_p' . self::PARSER_VERSION . '_' . $id;
    }

    public static function isCached(string $id, bool $in_progress): bool
    {
        return Cache::getKey(self::cacheKey($id), $in_progress ? self::IN_PROGRESS_TTL : self::PAST_TTL) !== false;
    }

    /**
     * Number of bugs uplifted in a set of Mercurial logs.
     * Return null if one of the logs isn't available, nothing is cached then.
     *
     * @param list<string> $urls json-pushes urls
     */
    public static function count(string $id, array $urls, bool $in_progress): ?int
    {
        $ttl = $in_progress ? self::IN_PROGRESS_TTL : self::PAST_TTL;

        if (($cached = Cache::getKey(self::cacheKey($id), $ttl)) !== false) {
            return (int) $cached; // @codeCoverageIgnore
        }

        $total = 0;
        foreach ($urls as $url) {
            $log = self::getLog($url, $in_progress);
            if ($log === null) {
                return null;
            }
            $total += count(Bugzilla::getBugsFromHgWeb($log, true)['total']);
        }

        Cache::setKey(self::cacheKey($id), (string) $total, $ttl);

        return $total;
    }

    /**
     * Raw json-pushes log. Logs of shipped releases are stored as .immutable
     * files, logs of the cycle in progress get a short cache.
     *
     * We don't use Json::load() here: it memoizes decoded logs for the whole
     * request, which is too much memory for a hundred big beta logs, and it
     * would also store an hg error answer forever as it is valid Json.
     */
    public static function getLog(string $url, bool $in_progress): ?string
    {
        $ttl = $in_progress ? self::IN_PROGRESS_TTL : -1;

        $log = Cache::getKey($url, $ttl);
        if (is_string($log)) {
            return $log; // @codeCoverageIgnore
        }

        if (self::isBlocked()) {
            return null; // @codeCoverageIgnore
        }

        // @codeCoverageIgnoreStart
        if (! defined('TESTING_CONTEXT')) {
            // The hg WAF blocks us (406) when we fetch many logs too fast
            usleep(500_000);
        }
        // @codeCoverageIgnoreEnd

        $log = Utils::getFile($url);

        // Not a log: failed request, unknown tag, hg error message…
        if (! is_string($log) || ! json_validate($log) || ! array_key_exists('pushes', (array) json_decode($log, true))) {
            self::$fetch_failed = true;
            Cache::setKey(self::BLOCKED_KEY, '1', self::BLOCKED_TTL);
            return null;
        }

        Cache::setKey($url, $log, $ttl);

        return $log;
    }

    /**
     * Did a log fetch fail recently?
     */
    public static function isBlocked(): bool
    {
        return self::$fetch_failed || Cache::getKey(self::BLOCKED_KEY, self::BLOCKED_TTL) !== false;
    }

    /**
     * Count uplifts for a set of logs. Counting a cold cache fetches dozens
     * of logs, which takes minutes, so we show the waiting page meanwhile.
     *
     * @param array<string, array{urls: list<string>, in_progress: bool}> $logs id => logs
     *
     * @return array<string, ?int> id => count, null if not available yet
     *
     * @codeCoverageIgnore
     */
    public static function countAll(array $logs): array
    {
        $cold = false;
        if (Cache::isActivated() && ! self::isBlocked()) {
            foreach ($logs as $id => $log) {
                if (! $log['in_progress'] && ! self::isCached($id, false)) {
                    $cold = true;
                    break;
                }
            }
        }

        if ($cold) {
            Request::waitingPage('load');
        }

        $counts = [];
        foreach ($logs as $id => $log) {
            $counts[$id] = self::count($id, $log['urls'], $log['in_progress']);
        }

        if ($cold) {
            Request::waitingPage('leave');
        }

        return $counts;
    }

    /**
     * Allow fetching logs again after a failure, used in tests
     */
    public static function reset(): void
    {
        self::$fetch_failed = false;
    }

    /**
     * json-pushes urls of the logs of bugs uplifted to beta and to the
     * release candidates of a Firefox version:
     * - mozilla-beta from the nightly to beta merge to the end of beta
     * - mozilla-release from the beta to release merge to the first shipped release
     *
     * @return list<string>
     */
    public static function getBetaLogUrls(int $version): array
    {
        $hg = URL::Mercurial->target() . 'releases/';
        $beta_end = 'FIREFOX_BETA_' . $version . '_END';
        $rc_end = null;

        if ($version < BETA) {
            // 125.0 never shipped, the first 125 release is 125.0.1
            $rc_end = 'FIREFOX_' . $version . ($version === 125 ? '_0_1' : '_0') . '_RELEASE';
        } else {
            // @codeCoverageIgnoreStart
            // The Beta class queries hg about the state of the beta in progress
            $beta = new Beta($version);
            if (! $beta->beta_cycle_ended) {
                $beta_end = 'tip';
            }
            // _0_RELEASE is not tagged yet during RC week
            [$have_rc, $number_rc_builds] = $beta->RCStatus();
            if ($have_rc) {
                $rc_end = 'FIREFOX_' . $version . '_0_BUILD' . $number_rc_builds;
            }
            // @codeCoverageIgnoreEnd
        }

        // v119: an unwanted central-to-beta merge corrupts the range up to FIREFOX_BETA_119_END
        if ($version === 119) {
            $beta_end = 'f2a69b23cb0aaf2b36bac4f9f197bf4282f542c4';
        }

        $urls = [
            $hg . 'mozilla-beta/json-pushes?fromchange=FIREFOX_BETA_' . $version . '_BASE'
                . '&tochange=' . $beta_end . '&full&version=2',
        ];

        if ($rc_end !== null) {
            $urls[] = $hg . 'mozilla-release/json-pushes?fromchange=FIREFOX_RELEASE_' . $version . '_BASE'
                . '&tochange=' . $rc_end . '&full&version=2';
        }

        return $urls;
    }

    /**
     * json-pushes url of the log of bugs uplifted to an ESR dot release,
     * from the previous ESR release. The cycle in progress is read up to tip.
     * Return null for a new ESR branch (x.0.0), it has no uplifts.
     */
    public static function getESRLogUrl(string $esr_version, bool $in_progress): ?string
    {
        [$major, $minor] = array_map('intval', explode('.', $esr_version));

        if ($minor === 0) {
            return null;
        }

        // The tag of the initial release of an ESR branch has no dot release part
        $from = 'FIREFOX_' . $major . '_' . ($minor - 1) . ($minor - 1 === 0 ? '' : '_0') . 'esr_RELEASE';
        $to = $in_progress ? 'tip' : 'FIREFOX_' . $major . '_' . $minor . '_0esr_RELEASE';

        return URL::Mercurial->target() . 'releases/mozilla-esr' . $major
            . '/json-pushes?fromchange=' . $from . '&tochange=' . $to . '&full&version=2';
    }

    /**
     * ESR releases shipping with each Firefox cycle, from the first ESR cycle
     * we can count to the cycle in progress, keyed by ESR branch.
     * Ex: 128 => [115 => '115.14.0', 128 => '128.0.0']
     *
     * ESR::getVersionsForRelease() is a theoretical schedule, so shipped cycles
     * only keep the ESR releases that shipped, and the cycle in progress only
     * keeps the branches that are still supported.
     *
     * @param list<string> $shipped_esr ESR versions that shipped, ex: '128.14.0' or '128.14.0esr'
     *
     * @return array<int, array<int, string>>
     */
    public static function getESRReleases(int $in_progress_version, array $shipped_esr): array
    {
        $shipped_esr = array_flip(array_map(fn(string $version) => str_replace('esr', '', $version), $shipped_esr));
        $releases = [];
        foreach (range(self::ESR_FIRST_VERSION, $in_progress_version) as $version) {
            $releases[$version] = [];
            foreach (ESR::getVersionsForRelease($version) as $esr_version) {
                $keep = $version === $in_progress_version
                    ? ESR::isSupported((int) $esr_version)
                    : isset($shipped_esr[$esr_version]);
                if ($keep) {
                    $releases[$version][(int) $esr_version] = $esr_version;
                }
            }
            ksort($releases[$version]);
        }

        return $releases;
    }

    /**
     * Length in weeks of each Firefox release cycle, from the previous release
     * to this one. Holiday cycles are longer, 2-week cycles start with 155.
     *
     * @param array<string, string> $major_releases version => release date
     *
     * @return array<int, float>
     */
    public static function getCycleWeeks(array $major_releases): array
    {
        // First shipped release of each major version (125.0 never shipped, 125.0.1 did)
        $dates = [];
        foreach ($major_releases as $version => $date) {
            $major = (int) $version;
            $time = (int) strtotime($date);
            if (! isset($dates[$major]) || $time < $dates[$major]) {
                $dates[$major] = $time;
            }
        }

        $weeks = [];
        foreach ($dates as $major => $time) {
            if (isset($dates[$major - 1])) {
                $weeks[$major] = round(($time - $dates[$major - 1]) / (86400 * 7), 2);
            }
        }

        return $weeks;
    }
}
