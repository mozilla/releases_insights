<?php

declare(strict_types=1);

namespace ReleaseInsights;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils as Promise;

use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\Psr6CacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

use ReleaseInsights\Bugzilla;
use ReleaseInsights\Release;
use ReleaseInsights\Json;
use ReleaseInsights\URL;

readonly class Beta
{
    public readonly int $count;
    public readonly int $number_betas;
    public readonly bool $beta_cycle_ended;

    public function __construct(public int $release = BETA) {
        $this->count = (int) explode('b', FIREFOX_BETA)[1];

        // We get the number of betas from the planned schedule
        $schedule = (new Release((string) $release))->getSchedule();
        $schedule = array_keys($schedule);
        $schedule = array_filter($schedule, fn($label) => str_starts_with($label, 'beta_'));
        $this->number_betas = count($schedule);

        // Check if the beta cycle is over
        $this->beta_cycle_ended = str_contains((string) get_headers(
            URL::Mercurial->value . 'releases/mozilla-beta/json-pushes?fromchange=' . 'FIREFOX_BETA_' . BETA . '_END')[0],
            '200');
    }

    /**
     * @return array<mixed>
     */
    public function getLogEndpoints(): array
    {
        $hg_end_points = [];

        /*
            Analyse Beta logs first
        */
        foreach (range(0, $this->count) as $beta_number) {
            $beta_start = ($beta_number == 0)
                ? 'FIREFOX_BETA_' . BETA . '_BASE'
                : 'FIREFOX_' . BETA . '_0b' . $beta_number . '_RELEASE';

            $beta_end = 'FIREFOX_' . BETA . '_0b' . (string) ($beta_number + 1) . '_RELEASE';

            if ($beta_number == $this->count) {
                $beta_end = 'tip';
                error_log((string) $beta_number);
                // Just after merge day, we don't want to use tip for beta_end but the newly created tag
                if ($this->beta_cycle_ended) {
                    $beta_end = 'FIREFOX_BETA_' . BETA . '_END';
                }
            }

            $beta_version = (string) BETA . '.0b' . (string) ($beta_number + 1);

            // This is what landed on mozilla-beta after the last beta but before the merge and RC1
            $beta_version = ($beta_number == $this->number_betas)
                ?  (string) BETA . '.0rc0' // @codeCoverageIgnore
                :  (string) BETA . '.0b' . (string) ($beta_number + 1);

            $hg_end_points[$beta_version] =
                'releases/mozilla-beta/json-pushes?fromchange='
                . $beta_start
                . '&amp;tochange='
                . $beta_end
                . '&amp;full&amp;version=2';
        }

        /*
            Analyse Release logs for RCs if we are in RC week

            Check if we have already shipped a Release Candidate build to the beta channel
            Remote balrog API can give a 404, we have a fallback to N/A
        */
        if (defined('TESTING_CONTEXT')) {
            $shipping_build = 'Firefox-94.0b9-build1';
        } else {
            $shipping_build = Json::load(URL::Balrog->value . 'rules/firefox-beta', 900)['mapping'] ?? 'N/A';// @codeCoverageIgnore
        }

        if ($shipping_build !== 'N/A') {
            // We have Release candidates
            [$product, $version, $build_number] = explode('-', (string) $shipping_build);
            $is_rc_build = ! str_contains($version, 'b');
            $number_rc_builds = $is_rc_build ? (int) str_replace('build', '', $build_number) : 0;

            if ($is_rc_build) {
                foreach (range(1, $number_rc_builds) as $rc_number) {
                    if ($rc_number == 1) {
                        $rc_start = 'FIREFOX_RELEASE_' . BETA . '_BASE';
                        $rc_end = 'FIREFOX_' . BETA . '_0_BUILD1';
                    } else {
                        $rc_start = 'FIREFOX_' . BETA . '_0_BUILD' . (string) ($rc_number - 1);
                        $rc_end = 'FIREFOX_' . BETA . '_0_BUILD' . (string) ($rc_number);
                    }

                    $rc_version = (string) BETA . '.0rc' . (string) $rc_number;

                    // This is what landed on mozilla-beta after the last beta but before the merge and RC1

                    $hg_end_points[$rc_version] =
                        'releases/mozilla-release/json-pushes?fromchange='
                        . $rc_start
                        . '&amp;tochange='
                        . $rc_end
                        . '&amp;full&amp;version=2';
                }
            }
        }

        return $hg_end_points;
    }

    /**
     * We don't unit test this function as this is all http requests
     *
     * @return array<mixed>
     * @codeCoverageIgnore
     */
    public function getBugsFromLogs(): array
    {
        // Create a HandlerStack
        $stack = HandlerStack::create();
        $TTL = 600;

        $cache_storage = new Psr6CacheStorage(
            new FilesystemAdapter(
                'guzzle', // Cache folder name
                $TTL,
                CACHE_PATH
            )
        );

        // Add Cache Method
        $stack->push(
            new CacheMiddleware(
                new GreedyCacheStrategy(
                    $cache_storage,
                    $TTL // the TTL in seconds
                )
            ),
            'greedy-cache'
        );

        // Initialize the client with the handler option
        $client = new Client(['handler' => $stack, 'base_uri' => URL::Mercurial->value]);

        // Initiate each request but do not block
        $promises = [];
        foreach ($this->getLogEndpoints() as $beta => $query) {
            $promises[$beta] = $client->getAsync($query);
        }

        $responses = Promise::settle($promises)->wait();

        $beta_logs = [];
        foreach ($responses as $key => $json_log) {
            $data = $json_log['value']->getBody()->getContents();
            $beta_logs[$key] = Bugzilla::getBugsFromHgWeb($data, true, 3600*24);
        }

        // Wait for the requests to complete, even if some of them fail
        return $beta_logs;
    }

    /**
     * Function relies heavily on external data, hard to unit test
     * @return array<mixed>
     * @codeCoverageIgnore
     */
    public function report(): array
    {

        $uplifts_per_beta = $this->getBugsFromLogs();
        $log_links = array_map(fn($query) => URL::Mercurial->value . $query, $this->getLogEndpoints());
        $log_links = array_map(fn($query) => str_replace('json-pushes', 'pushloghtml', $query), $log_links);

        foreach ($log_links as $beta => $url) {
            $uplifts_per_beta[$beta]['hg_link'] = $url;
        }

        foreach ($log_links as $beta => $url) {
            $uplifts_per_beta[$beta]['bugzilla'] = Bugzilla::getBugListLink($uplifts_per_beta[$beta]['bug_fixes']);
        }

        // We use a natural sort to avoid having a beta 10 listed after beta 1
        ksort($uplifts_per_beta, SORT_NATURAL);

        return $uplifts_per_beta;
    }
}

