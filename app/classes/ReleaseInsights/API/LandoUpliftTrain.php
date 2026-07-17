<?php

declare(strict_types=1);

namespace ReleaseInsights\API;

use DateTime;
use ReleaseInsights\Beta;
use ReleaseInsights\Data;
use ReleaseInsights\Release;
use ReleaseInsights\Debug;

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
    private object $beta;

    public function __construct() {
        $this->beta = new Beta(RELEASE + 1);
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
            Don't use our NIGHTLY & BETA constants from product-details.
            In the case of Lando, we want to plan uplifts and during release week,
            our version numbers in product-details are not always fully sequentials
            if we have merge problems with beta.
        */
        $nightly = RELEASE + 2;
        $beta    = RELEASE + 1;

        /*
            We need specific logic for this API for the period of time when we are past RC,
            or have shipped to release, but not yet shipped our first beta. That is about 3 days,
            from main->beta merge day on Monday to shipping our beta 1 build2 on Wednesday.
            In this window, the state of the Beta class reflects the state of the previous beta cycle,
            not the one we are going to enter as the Beta class is built with end-users in mind, not
            Firefox developers.

            We are going to use merge day as the marker for the values of has_betas_left and is_rc_shipped
        */
        $has_betas = $this->beta->has_betas_left;
        $has_rc    = $this->beta->hasRC();

        // Compare today with new nightly to beta merge date
        if (date('Y-m-d') > $date($nightly . '.0', 'merge_day')) {
            $has_betas = true;
            $has_rc    = false;
        }


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
                'version'      => RELEASE,
                'release_date' => $date(FIREFOX_RELEASE),
            ],
        ];
    }
}
