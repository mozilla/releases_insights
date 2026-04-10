<?php

declare(strict_types=1);

namespace ReleaseInsights;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils as Promise;

use Cache\Cache;
use ReleaseInsights\Bugzilla;
use ReleaseInsights\Release;
use ReleaseInsights\Json;
use ReleaseInsights\URL;

readonly class Beta
{
    public int $count;
    public int $number_betas;
    public bool $beta_cycle_ended;

    public function __construct(public int $release = BETA) {
        // We get the number of betas from the planned schedule
        $schedule = new Release((string) $release)->getSchedule();
        $schedule = array_keys($schedule);
        $schedule = array_filter(
            $schedule,
            fn($label) => str_starts_with($label, 'beta_') && ! str_ends_with($label, '_gtb')
        );
        $this->number_betas = count($schedule);

        if ($this->release < BETA) {
            // Past beta cycle: all betas have shipped
            $this->count = $this->number_betas;
            $this->beta_cycle_ended = true;
        } else {
            $this->count = (int) explode('b', FIREFOX_BETA)[1];

            // Check if the beta cycle is over, this avoids a miscount for RC builds
            if ($this->count >= $this->number_betas && ! defined('TESTING_CONTEXT')) {
                // @codeCoverageIgnoreStart
                $cache_key = 'beta_cycle_ended_' . $this->release;
                if (($cached = Cache::getKey($cache_key, 900)) === false) {
                    $http_code = get_headers(
                        URL::Mercurial->value . 'releases/mozilla-beta/json-pushes?fromchange=' . 'FIREFOX_BETA_' . $this->release . '_END'
                    );
                    $http_code = array_filter($http_code, fn($v) => str_starts_with($v, 'HTTP/'));
                    $http_code = end($http_code); // We want the last HTTP code to workaround the hg-edge 302 redirect
                    $cached = str_contains($http_code, '200') ? 'true' : 'false';
                    Cache::setKey($cache_key, $cached, 900);
                }
                $this->beta_cycle_ended = $cached === 'true';
                // @codeCoverageIgnoreEnd
            } else {
                $this->beta_cycle_ended = false;
            }
        }
    }

    /**
     * @return array<mixed>
     */
    public function getLogEndpoints(): array
    {
        $hg_end_points = [];

        [$have_rc, $number_rc_builds] = ($this->release === BETA)
            ? $this->RCStatus()
            : $this->historicalRCStatus();

        /*
            Analyse Beta logs first
        */
        foreach (range(0, $this->count) as $beta_number) {
            $beta_start = ($beta_number == 0)
                ? 'FIREFOX_BETA_' . $this->release . '_BASE'
                : 'FIREFOX_' . $this->release . '_0b' . $beta_number . '_RELEASE';

            $beta_end = 'FIREFOX_' . $this->release . '_0b' . (string) ($beta_number + 1) . '_RELEASE';

            if ($beta_number == $this->count) {
                $beta_end = 'tip';
                // Just after merge day, we don't want to use tip for beta_end but the newly created tag
                if ($this->beta_cycle_ended) {
                    $beta_end = 'FIREFOX_BETA_' . $this->release . '_END'; // @codeCoverageIgnore
                }
            }

            // This is what landed on mozilla-beta after the last beta but before the merge and RC1
            $beta_version = ($beta_number == $this->number_betas)
                ?  (string) $this->release . '.0rc0' // @codeCoverageIgnore
                :  (string) $this->release . '.0b' . (string) ($beta_number + 1);

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

        if ($have_rc) {
            foreach (range(1, $number_rc_builds) as $rc_number) {
                if ($rc_number == 1) {
                    $rc_start = 'FIREFOX_RELEASE_' . $this->release . '_BASE';
                    $rc_end = 'FIREFOX_' . $this->release . '_0_BUILD1';
                } else {
                    // @codeCoverageIgnoreStart
                    $rc_start = 'FIREFOX_' . $this->release . '_0_BUILD' . (string) ($rc_number - 1);
                    $rc_end = 'FIREFOX_' . $this->release . '_0_BUILD' . (string) ($rc_number);
                    // @codeCoverageIgnoreEnd
                }

                $rc_version = (string) $this->release . '.0rc' . (string) $rc_number;

                // This is what landed on mozilla-beta after the last beta but before the merge and RC1

                $hg_end_points[$rc_version] =
                    'releases/mozilla-release/json-pushes?fromchange='
                    . $rc_start
                    . '&amp;tochange='
                    . $rc_end
                    . '&amp;full&amp;version=2';
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
        $endpoints = $this->getLogEndpoints();
        $shipped_ttl = 3600 * 24 * 30;
        $latest_key = (string) array_key_last($endpoints);
        $is_current_cycle = ($this->release === BETA);

        // Check cache for each endpoint. Shipped betas are cached for one month.
        // The latest beta in the current cycle still receives pushes and is never cached.
        $beta_logs = [];
        $to_fetch = [];
        foreach ($endpoints as $beta => $query) {
            $cacheable = !($is_current_cycle && $beta === $latest_key);
            if ($cacheable && ($cached = Cache::getKey('beta_logs_' . $beta, $shipped_ttl)) !== false) {
                $beta_logs[$beta] = $cached;
                continue;
            }
            $to_fetch[$beta] = ['query' => $query, 'cacheable' => $cacheable];
        }

        if (empty($to_fetch)) {
            return $beta_logs;
        }

        // Fetch cache misses in parallel
        $client = new Client(['base_uri' => URL::Mercurial->value]);
        $promises = [];
        foreach ($to_fetch as $beta => ['query' => $query]) {
            $promises[$beta] = $client->getAsync($query, ['http_errors' => false]);
        }

        $empty = ['bug_fixes' => [], 'backouts' => [], 'total' => [], 'files' => [], 'no_data' => true];

        foreach (Promise::settle($promises)->wait() as $key => $json_log) {
            if ($json_log['state'] !== 'fulfilled' || $json_log['value']->getStatusCode() !== 200) {
                $beta_logs[$key] = $empty;
                continue;
            }

            $data = $json_log['value']->getBody()->getContents();
            $result = Bugzilla::getBugsFromHgWeb(query: $data, detect_backouts: true);

            if ($to_fetch[$key]['cacheable']) {
                Cache::setKey('beta_logs_' . $key, $result, $shipped_ttl);
            }

            $beta_logs[$key] = $result;
        }

        return $beta_logs;
    }

    /**
     * Function relies heavily on external data, hard to unit test
     * @return array<mixed>
     * @codeCoverageIgnore
     */
    public function uplifts(): array
    {
        $uplifts_per_beta = $this->getBugsFromLogs();
        $log_links = array_map(fn($query) => URL::Mercurial->value . $query, $this->getLogEndpoints());
        $log_links = array_map(fn($query) => str_replace('json-pushes', 'pushloghtml', $query), $log_links);

        foreach ($log_links as $beta => $url) {
            $uplifts_per_beta[$beta]['hg_link'] = $url;
        }

        foreach ($log_links as $beta => $url) {
            $uplifts_per_beta[$beta]['bugzilla'] = Bugzilla::getBugListLink($uplifts_per_beta[$beta]['total']);
        }

        // We use a natural sort to avoid having a beta 10 listed after beta 1
        ksort($uplifts_per_beta, SORT_NATURAL);

        return $uplifts_per_beta;
    }

    /**
     * Return all beta crashes
     * @return array<mixed>
     */
    public function crashes(): array
    {
        $ttl = 3600;

        // Build version -> URL map for all betas
        $targets = [];
        foreach (range(1, $this->count) as $beta_number) {
            $version = (string) $this->release . '.0b' . $beta_number;
            if (defined('TESTING_CONTEXT')) {
                $version = str_replace('146', '131', $version);
                $targets[$version] = URL::Socorro->target() . 'crash-stats.mozilla.org_' . $version . '.json';
            } else {
                $targets[$version] = URL::Socorro->value . 'SuperSearch/?version=' . $version . '&_facets=signature&product=Firefox'; // @codeCoverageIgnore
            }
        }

        // Add RC builds
        [$have_rc, $number_rc_builds] = $this->RCStatus();
        if ($have_rc) {
            foreach (range(1, $number_rc_builds) as $rc_number) {
                $version = (string) $this->release . '.0rc' . $rc_number;
                if (defined('TESTING_CONTEXT')) {
                    $version = str_replace('94', '131', $version);
                    $targets[$version] = URL::Socorro->target() . 'crash-stats.mozilla.org_' . $version . '.json';
                } else {
                    $targets[$version] = URL::Socorro->value . 'SuperSearch/?version=' . $version . '&_facets=signature&product=Firefox'; // @codeCoverageIgnore
                }
            }
        }

        // Serve from cache where possible, collect misses
        $results = [];
        $to_fetch = [];
        foreach ($targets as $version => $url) {
            if ($cached = Cache::getKey($url, $ttl)) {
                $results[$version] = Json::toArray($cached); // @codeCoverageIgnore
            } else {
                $to_fetch[$version] = $url;
            }
        }

        if (! empty($to_fetch)) {
            if (defined('TESTING_CONTEXT')) {
                // Test URLs are local file paths, no HTTP involved
                foreach ($to_fetch as $version => $url) {
                    $results[$version] = Json::load($url, $ttl);
                }
            } else {
                // @codeCoverageIgnoreStart
                // Fetch all cache misses in parallel
                $client = new Client(['headers' => ['User-Agent' => 'WhatTrainIsItNow/1.0']]);
                $promises = [];
                foreach ($to_fetch as $version => $url) {
                    $promises[$version] = $client->getAsync($url, ['http_errors' => false]);
                }
                foreach (Promise::settle($promises)->wait() as $version => $result) {
                    $url = $to_fetch[$version];
                    if ($result['state'] === 'fulfilled' && $result['value']->getStatusCode() === 200) {
                        $body = $result['value']->getBody()->getContents();
                        if (! empty($body) && json_validate($body)) {
                            Cache::setKey($url, $body, $ttl);
                            $results[$version] = Json::toArray($body);
                        } else {
                            $results[$version] = [];
                        }
                    } else {
                        $results[$version] = [];
                    }
                }
                // @codeCoverageIgnoreEnd
            }
        }

        // Build data array from results
        $data = [];
        foreach ($targets as $version => $url) {
            $temp = $results[$version] ?? [];

            // @codeCoverageIgnoreStart
            if (empty($temp)) {
                $data[$version] = ['total' => 0, 'signatures' => []];
                continue;
            }
            // @codeCoverageIgnoreEnd

            $data[$version] = [
                'total'      => $temp['total'] ?? 0,
                'signatures' => $temp['facets']['signature'] ?? [],
            ];
        }

        // Create a summary of the crashes across betas
        $data['summary'] = ['total' => array_sum(array_column($data, 'total'))];

        return $data;
    }

    /**
     * Get the RC count for a past (completed) release by probing Mercurial tags.
     * Results are cached for one week since historical data never changes.
     *
     * @return array<mixed>
     * @codeCoverageIgnore
     */
    private function historicalRCStatus(): array
    {
        $cache_key = 'historical_rc_status_' . $this->release;

        if (($cached = Cache::getKey($cache_key, 86400 * 365)) !== false) {
            $n = (int) $cached;
            return [$n > 0, $n];
        }

        $number_rc = 0;
        $cacheable = true;

        for ($i = 1; $i <= 4; $i++) {
            $from = ($i === 1)
                ? 'FIREFOX_RELEASE_' . $this->release . '_BASE'
                : 'FIREFOX_' . $this->release . '_0_BUILD' . ($i - 1);
            $to = 'FIREFOX_' . $this->release . '_0_BUILD' . $i;

            $url = URL::Mercurial->value . 'releases/mozilla-release/json-pushes?fromchange=' . $from . '&tochange=' . $to;
            $headers = get_headers($url);
            $headers = array_filter((array) $headers, fn($v) => str_starts_with($v, 'HTTP/'));
            $http_code = (string) end($headers); // Last code to handle hg-edge 302 redirects

            if (str_contains($http_code, '200')) {
                $number_rc = $i;
            } elseif (str_contains($http_code, '404')) {
                break; // Tag doesn't exist — definitive answer
            } else {
                $cacheable = false; // 429, 5xx, or network failure — don't cache
                break;
            }
        }

        if ($cacheable) {
            Cache::setKey($cache_key, (string) $number_rc, 86400 * 365);
        }
        return [$number_rc > 0, $number_rc];
    }

    /**
     * Get the status of release candidates
     *
     * @return array<mixed>
     */
    public function RCStatus() : array
    {
       if (defined('TESTING_CONTEXT')) {
            $shipping_build = 'Firefox-94.0-build1';
        } else {
            $shipping_build = Json::load(URL::Balrog->value . 'rules/firefox-beta', 900)['mapping'] ?? 'N/A';// @codeCoverageIgnore
        }

        if ($shipping_build !== 'N/A') {
            // We have Release candidates
            [$product, $version, $build_number] = explode('-', (string) $shipping_build);
            $is_rc_build = ! str_contains($version, 'b');
            $number_rc_builds = $is_rc_build ? (int) str_replace('build', '', $build_number) : 0;

            return [$is_rc_build, $number_rc_builds];
        }

        return [false, 0];// @codeCoverageIgnore
    }
}