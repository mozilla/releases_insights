<?php

declare(strict_types=1);

namespace ReleaseInsights\API;

use Cache\Cache;
use DateTime;
use ReleaseInsights\Beta;
use ReleaseInsights\Data;
use ReleaseInsights\Release;
use ReleaseInsights\URL;
use ReleaseInsights\Utils;
use ReleaseInsights\Version;

/*
    This is only consumed by our API endpoint /api/lando/uplift/train/
    This is for use in the Lando target train selection widget.
    See:
        - Bug 2044143 - Improve guidance on target train selection - https://bugzil.la/2044143
        - Bug 2045812 - Add an API endpoint for Lando uplift train selection guidance - https://bugzil.la/2045812

    REQUEST:
        We need a new API endpoint in whattrainisitnow.com that provides the data required for two changes in the Lando train selection:

        - A new widget to select the target release version for the uplift to land on, which will then resolve to a suggested train. For example, the user would say "I want to uplift to v152", which would then suggest beta or release depending on the timing in the release cycle.
        - A helpful hint for which release their uplift will land in when selecting a specific train. For example, selecting firefox-beta will display a message like "This will land in Firefox v152", or "This will land in Firefox v153" depending on timing in the cycle.

        Lando's front-end will send an API request to whattrainisitnow.com and hold the response in the browser, which will then be used to guide the user in selecting the appropriate uplift target.
*/


class LandoUpliftTrain
{
    // How long we cache main's version.txt during the merge day window, see nightlyVersion()
    private const int MAIN_CACHE_TTL_BEFORE_BUMP = 10800; // 3 hours
    private const int MAIN_CACHE_TTL_AFTER_BUMP  = 86400; // 24 hours

    private object $beta;

    public function __construct() {
        $this->beta = new Beta(NIGHTLY - 1);
    }

    /**
     *  This is the API endpoint returned by the model at /api/lando/uplift/train/
     *  https://bugzil.la/2045812
     *
     * @return array<string, mixed>
     */
    public function getTrains(): array
    {
        /* Output a 2026-05-01 format string */
        $date = fn($version, $milestone = 'release') => new DateTime(new Release($version)->getSchedule()[$milestone])->format('Y-m-d');

        /*
            Anchor every train on NIGHTLY, the version mozilla-central carries.

            Lando plans uplifts, so what matters is the version each *branch* holds,
            not the version users are running. NIGHTLY is bumped by central on merge
            day, the same day beta moves on to release, so nightly - 1 and nightly - 2
            name mozilla-beta and mozilla-release for the whole cycle.

            Don't anchor on RELEASE: LATEST_FIREFOX_VERSION only moves on release go-live day,
            which under the 2 week cadence is up to 5 days after merge day. In that
            window RELEASE + 1 and RELEASE + 2 still name the previous cycle.

            Don't anchor on BETA either: it only moves once a beta build ships.

            NIGHTLY itself lags on merge day: product-details only moves once a nightly
            is built with the bumped version. See nightlyVersion().
        */
        $nightly = $this->nightlyVersion();
        $beta    = $nightly - 1;
        $release = $nightly - 2;

        /*
            We need specific logic for this API for the period between merge day and
            shipping beta 1, a few days under the 2 week cadence (Thursday to Monday).
            In this window, the state of the Beta class reflects the state of the previous
            beta cycle, not the one we are going to enter as the Beta class is built with
            end-users in mind, not Firefox developers.
        */

        // Safe defaults
        $has_betas = true;
        $has_rc    = false;

        // Covers RC week
        if ($this->beta->hasRC()) {
            $has_betas = false;
            $has_rc    = true;
        }

        // @codeCoverageIgnoreStart
        /*
            Have we merged to beta but not shipped beta 1 yet? Then BETA still names the
            previous cycle, so Beta::hasRC() answered about that older version (RCStatus()
            compares against the BETA constant) and its answer has to be discarded: the
            branch we are pointing uplifts at is about to start its betas.
        */
        if (BETA !== $beta) {
            $has_betas = true;
            $has_rc    = false;
        }
        // @codeCoverageIgnoreEnd

        return [
            'nightly' => [
                'version'      => $nightly,
                'release_date' => $date($nightly . '.0'),
            ],
            'beta' => [
                'version'        => $beta,
                'release_date'   => $date($beta . '.0'),
                'has_betas_left' => $has_betas,
                'is_rc_shipped'  => $has_rc,
            ],
            'release' => [
                'version'      => $release,
                'release_date' => $date($release . '.0'),
            ],
            'esr' => [
                'version' => NEXT_ESR ?: CURRENT_ESR,
            ],
            'esr_previous' => [
                'version' => NEXT_ESR && CURRENT_ESR ? CURRENT_ESR : null,
            ],
        ];
    }

    /**
     *  The version main carries.
     *
     *  From merge day until product-details picks up the new nighty bump, FIREFOX_NIGHTLY
     *  still names the version that just merged to beta. In that merge day window, read
     *  version.txt on main instead. Outside of the window, this does not depend on GitHub at all.
     *  gate on the merge day date, before the bump version.txt just agrees with NIGHTLY.
     */
    private function nightlyVersion(): int
    {
        $merge_day = new Release(NIGHTLY . '.0')->getSchedule()['merge_day'];

        // @codeCoverageIgnoreStart
        if (date('Y-m-d') < substr($merge_day, 0, 10)) {
            return NIGHTLY;
        }
        // @codeCoverageIgnoreEnd

        $url       = URL::FirefoxMain->target() . 'version.txt';
        $failed_id = $url . '#failed';
        $bumped_id = $url . '#bumped';

        // GitHub failed recently, don't make every request wait on it again.
        if (Cache::getKey($failed_id, 300)) {
            return NIGHTLY; // @codeCoverageIgnore
        }

        /*
            Main is bumped once per window. Until then, recheck every 3 hours. Once
            bumped, the value is final until product-details catches up and closes
            the window, so keep it for 24 hours. The cache applies the TTL on read,
            hence two keys.
        */
        if (! $data = Cache::getKey($bumped_id, self::MAIN_CACHE_TTL_AFTER_BUMP) ?: Cache::getKey($url, self::MAIN_CACHE_TTL_BEFORE_BUMP)) {
            $data = Utils::getFile($url);

            // Fetch failed or unexpected content, fall back for 5 minutes.
            // @codeCoverageIgnoreStart
            if (! is_string($data) || ! preg_match('/^\d+\./', $data)) {
                Cache::setKey($failed_id, true, 300);

                return NIGHTLY;
            }
            // @codeCoverageIgnoreEnd

            // The version.txt fixture is always the bumped version (NIGHTLY + 1), so the
            // "not bumped yet" branch can't be reached in unit tests.
            (int) $data === NIGHTLY + 1
                ? Cache::setKey($bumped_id, $data, self::MAIN_CACHE_TTL_AFTER_BUMP)
                : Cache::setKey($url, $data, self::MAIN_CACHE_TTL_BEFORE_BUMP); // @codeCoverageIgnore
        }

        /*
            Main is either still on NIGHTLY or one version ahead, anything else is invalid.
            Before the version bump main still carries NIGHTLY, after it NIGHTLY + 1, and
            both are correct answers. Anything else (bad file content, product-details
            still stuck when the next merge day comes) falls back to NIGHTLY, the answer
            this API gave before we read main.
        */
        $version = (int) $data;

        return in_array($version, [NIGHTLY, NIGHTLY + 1], true) ? $version : NIGHTLY;
    }
}
