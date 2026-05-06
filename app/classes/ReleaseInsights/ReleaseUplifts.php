<?php

declare(strict_types=1);

namespace ReleaseInsights;

use Cache\Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils as Promise;
use ReleaseInsights\Bugzilla;
use ReleaseInsights\Data;
use ReleaseInsights\Json;
use ReleaseInsights\URL;

readonly class ReleaseUplifts
{
    public int $dot_release_count;

    /** @var array<string, array<string, string>> */
    public array $dot_releases;

    public function __construct(public int $release = RELEASE) {
        $all_dot_releases = new Data()->getDotReleases();
        $this->dot_releases = array_filter(
            $all_dot_releases,
            fn(string $key) => str_starts_with($key, $release . '.'),
            ARRAY_FILTER_USE_KEY
        );
        $this->dot_release_count = count($this->dot_releases);
    }

    /**
     * @return array<string, string>
     */
    public function getLogEndpoints(): array
    {
        $endpoints = [];

        $from_tag = 'FIREFOX_' . $this->release . '_0_RELEASE';

        foreach ($this->dot_releases as $version => $release_info) {
            $dot_num = (int) explode('.', $version)[2];
            $platform = $release_info['platform'] ?? 'desktop';

            // Android-only releases use a different tag prefix on releases/mozilla-release
            $tag_prefix = ($platform === 'android') ? 'FIREFOX-ANDROID' : 'FIREFOX';
            $to_tag = $tag_prefix . '_' . $this->release . '_0_' . $dot_num . '_RELEASE';

            $endpoints[$version] =
                'releases/mozilla-release/json-pushes?fromchange=' . $from_tag
                . '&tochange=' . $to_tag
                . '&full&version=2';

            // Always advance from_tag — android tags are on releases/mozilla-release too
            $from_tag = $to_tag;
        }

        // For the current release cycle add an in-progress section (from last dot to tip)
        if ($this->release === RELEASE) {
            $next_num = $this->dot_release_count + 1;
            $endpoints[$this->release . '.0.' . $next_num . '-next'] =
                'releases/mozilla-release/json-pushes?fromchange=' . $from_tag
                . '&tochange=tip&full&version=2';
        }

        return $endpoints;
    }

    /**
     * We don't unit test this function as this is all http requests
     *
     * @return array<mixed>
     * @codeCoverageIgnore
     */
    public function getBugsFromLogs(): array
    {
        $release_logs = [];
        foreach ($this->getLogEndpoints() as $version => $path) {
            $is_next = str_ends_with($version, '-next');
            $url = URL::Mercurial->target() . $path;
            $release_logs[$version] = Bugzilla::getBugsFromHgWeb(
                query: $url,
                detect_backouts: true,
                cache_ttl: $is_next ? 0 : 86400 * 365
            );
        }
        return $release_logs;
    }

    /**
     * Function relies heavily on external data, hard to unit test
     *
     * @return array<mixed>
     * @codeCoverageIgnore
     */
    public function uplifts(): array
    {
        $uplifts_per_version = $this->getBugsFromLogs();
        $log_links = array_map(fn($query) => URL::Mercurial->target() . $query, $this->getLogEndpoints());
        $log_links = array_map(fn($query) => str_replace('json-pushes', 'pushloghtml', $query), $log_links);

        foreach ($log_links as $version => $url) {
            $uplifts_per_version[$version]['hg_link'] = $url;
        }

        foreach ($log_links as $version => $url) {
            $uplifts_per_version[$version]['bugzilla'] = Bugzilla::getBugListLink($uplifts_per_version[$version]['total']);
        }

        ksort($uplifts_per_version, SORT_NATURAL);

        return $uplifts_per_version;
    }

    /**
     * Return crashes for each dot release version of this release
     *
     * @return array<mixed>
     */
    public function crashes(): array
    {
        $ttl = 3600;
        $targets = [];

        // Only query desktop crash stats; Android crash stats are not supported (see Utils::getCrashesForBuildID)
        $desktop_releases = array_filter(
            $this->dot_releases,
            fn($v) => in_array($v['platform'], ['desktop', 'both'])
        );

        foreach (array_keys($desktop_releases) as $dot_version) {
            if (defined('TESTING_CONTEXT')) {
                $targets[$dot_version] = URL::Socorro->target() . 'crash-stats.mozilla.org_' . $dot_version . '.json';
            } else {
                $targets[$dot_version] = URL::Socorro->value . 'SuperSearch/?version=' . $dot_version . '&_facets=signature&product=Firefox'; // @codeCoverageIgnore
            }
        }

        $results = [];
        $to_fetch = [];
        foreach ($targets as $version => $url) {
            if ($cached = Cache::getKey($url, $ttl)) {
                $results[$version] = Json::toArray($cached); // @codeCoverageIgnore
            } else {
                $to_fetch[$version] = $url;
            }
        }

        if (!empty($to_fetch)) {
            if (defined('TESTING_CONTEXT')) {
                foreach ($to_fetch as $version => $url) {
                    $results[$version] = Json::load($url, $ttl);
                }
            } else {
                // @codeCoverageIgnoreStart
                $client = new Client(['headers' => ['User-Agent' => 'WhatTrainIsItNow/1.0']]);
                $promises = [];
                foreach ($to_fetch as $version => $url) {
                    $promises[$version] = $client->getAsync($url, ['http_errors' => false]);
                }
                foreach (Promise::settle($promises)->wait() as $version => $result) {
                    $url = $to_fetch[$version];
                    if ($result['state'] === 'fulfilled' && $result['value']->getStatusCode() === 200) {
                        $body = $result['value']->getBody()->getContents();
                        if (!empty($body) && json_validate($body)) {
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

        $data['summary'] = ['total' => array_sum(array_column($data, 'total'))];

        return $data;
    }
}
