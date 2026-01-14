<?php

declare(strict_types=1);

namespace ReleaseInsights;

/**
 * This class defines which file contains the model called by the controller
 */
readonly class Model
{
    /** @var array<string, string> */
    public readonly array $list;

    public function __construct(public string $model) {
        $this->list = [
            'about'                       => 'about.php',
            'api_beta_crashes'            => 'api/beta_crashes.php',
            'api_chemspills'              => 'api/chemspill_releases.php',
            'api_esr_releases'            => 'api/esr_releases.php',
            'api_esr_release_pairs'       => 'api/esr_release_pairs.php',
            'api_external'                => 'api/external.php',
            'api_firefox_releases'        => 'api/firefox_releases.php',
            'api_firefox_releases_future' => 'api/firefox_future_releases.php',
            'api_future_calendar'         => 'api/future_calendar.php',
            'api_nightly'                 => 'api/nightly.php',
            'api_nightly_crashes'         => 'api/nightly_crashes.php',
            'api_release_owners'          => 'api/release_owners.php',
            'api_release_schedule'        => 'api/release_schedule.php',
            'api_ios_release_schedule'    => 'api/ios_release_schedule.php',
            'api_wellness_days'           => 'api/wellness_days.php',
            'beta'                        => 'beta.php',
            'calendar'                    => 'calendar.php',
            'calendar_monthly'            => 'calendar_monthly.php',
            'changelog'                   => 'changelog.php',
            'esr_release'                 => 'esr_release.php',
            'future_release'              => 'future_release.php',
            'home'                        => 'home.php',
            'ics'                         => 'ics_release_schedule.php',
            'nightly'                     => 'nightly.php',
            'owners'                      => 'release_owners.php',
            'pre_firefox4_release'        => 'pre4_release.php',
            'past_release'                => 'past_release.php',
            'rss'                         => 'rss.php',
        ];
    }

    /**
     * Return data from the model
     *
     * @codeCoverageIgnore
     */
    public function get(): mixed
    {
        return include MODELS . match ($this->model) {
            default => array_key_exists($this->model, $this->list)
                ? $this->list[$this->model]
                :  '404.php'
        };
    }
}