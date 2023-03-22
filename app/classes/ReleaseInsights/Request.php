<?php

declare(strict_types=1);

namespace ReleaseInsights;

class Request
{
    public string  $request = '/';
    public string  $path = '/';
    public ?string $query = null;
    public bool    $invalid_slashes = true;


    public function __construct(string $path)
    {
        $request = parse_url($path);

        // Paths that start with multiple slashes don't have a correct (or any) 'path' field via parse_url()
        if (str_starts_with($path, '//')) {
            $this->invalid_slashes = true;
        }

        // Real files are not processes as paths to route
        if ($request !== false) {
            // We sometimes use a fake query on a static asset to force the browser to refresh the cache,
            // we take the query out when checking if the file path exists
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . explode('?', $path)[0])) {
                $this->invalid_slashes = false;
                $this->path = explode('?', $path)[0];
            } else {
            /**
             *  We have a real path to route and clean up before usage
             */
                $this->request = $path;

                if (isset($request['path'])) {
                    $this->path = $this->cleanPath($request['path']);
                }

                if (isset($request['query'])) {
                    $this->query = $request['query'];
                }

                if (isset($request['path'])) {
                    if (str_ends_with($request['path'], '//')) {
                        // Multiple slashes at the end of the path
                        $this->invalid_slashes = true;
                    } elseif (! str_ends_with($request['path'], '/')) {
                        // Missing slash at the end of the path
                        $this->invalid_slashes = true;
                    } else {
                        $this->invalid_slashes = false;
                    }
                }
             }
         }
    }

    /**
     * Return the name of the controller file for the requested URL
     * If the path is unknown, we send a 404 response
     */
    public function getController(): string
    {
        return match ($this->path) {
            '/'                           => 'homepage',
            '/about/'                     => 'about',
            '/nightly/'                   => 'nightly',
            '/release/'                   => 'release',
            '/api/nightly/'               => 'api/nightly',
            '/api/release/schedule/'      => 'api/release_schedule',
            '/api/esr/releases/'          => 'api/esr_releases',
            '/api/firefox/releases/'      => 'api/firefox_releases',
            '/api/release/owners/'        => 'api/release_owners',
            '/api/nightly/crashes/'       => 'api/nightly_crashes',
            '/calendar/'                  => 'calendar',
            '/calendar/monthly/'          => 'calendar_monthly',
            '/calendar/release/schedule/' => 'ics_release_schedule',
            '/release/owners/'            => 'release_owners',
            '/sitemap/'                   => 'sitemap',
            default                       => '404',
        };
    }

    /**
     * Normalize path before comparing the string to a list of valid paths
     */
    public static function cleanPath(string $path): string
    {
        if ($path == '/' || $path == '//') {
            return '/';
        }

        $path = explode('/', $path);
        $path = array_filter($path); // Remove empty items
        $path = array_values($path); // Reorder keys

        return '/' . implode('/', $path) . '/';
    }
}
