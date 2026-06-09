<?php

declare(strict_types=1);

namespace ReleaseInsights\API;

use DateTime;
use ReleaseInsights\Beta;
use ReleaseInsights\Data;
use ReleaseInsights\Release;

/*
    This is only consumed by our API endpoint /api/lando/uplift/train/
    This is for use in the Lando target train selection widget
    See:
        - Bug 2044143 - Improve guidance on target train selection - https://bugzil.la/2044143
        - Bug 2045812 - Add an API endpoint for Lando uplift train selection guidance - https://bugzil.la/2045812

    REQUEST:
        We need a new API endpoint in whattrainisitnow.com that provides the data required for two changes in the Lando train selection:

        - A new widget to select the target release version for the uplift to land on, which will then resolve to a suggested train. For example, the user would say "I want to uplift to v152", which would then suggest beta or release depending on the timing in the release cycle.
        - A helpful hint for which release their uplift will land in when selecting a specific train. For example, selecting firefox-beta will display a message like "This will land in Firefox v152", or "This will land in Firefox v153" depending on timing in the cycle.

        Lando's front-end will send an API request to whattrainisitnow.com and hold the response in the browser, which will then be used to guide the user in selecting the appropriate uplift target.

    TODO:
        - Define what fields are needed for the widget
        - Update the About/API page section
    DONE:
        - Create a json endpoint for /api/lando/uplift/train/
        - Check if specific CORS needed if called client-side from Lando in JS?
        - Add http test
        - Add unit tests
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
        $date = fn($v) => new DateTime(new Release($v)->getSchedule()['release'])->format('Y-m-d');

        /*
            Don't use our NIGHTLY & BETA constants from product-details.
            In the case of Lando, we want to plan uplifts and during release week,
            our version numbers are not always fully sequentials if we have merge
            problems with beta and it gets delayed.
        */
        return [
            'nightly' => [
                'version'       => RELEASE + 2,
                'release_date'  => $date(RELEASE + 2 .'.0'),
            ],
            'beta' => [
                'version'        => RELEASE + 1,
                'release_date'   => $date(RELEASE + 1 .'.0'),
                'has_betas_left' => $this->beta->has_betas_left,
                'is_rc_shipped'  => $this->beta->hasRC(),
            ],
            'release' => [
                'version'       => RELEASE,
                'release_date'  => $date(FIREFOX_RELEASE),
            ],
        ];
    }
}
