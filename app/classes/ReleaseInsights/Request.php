<?php

declare(strict_types=1);

namespace ReleaseInsights;

class Request
{
    public string  $request;
    public string  $path;
    public ?string $query;
    public bool    $invalid_slashes;


    public function __construct(string $path)
    {
        $this->request = '/';
        $this->path = '/';
        $this->query = null;
        $this->invalid_slashes = true;

        $request = parse_url($path);

        if ($request !== false) {
            $this->request = $path;
            $this->path = $this->cleanPath($request['path']);

            if (isset($request['query'])) {
                $this->query = $request['query'];
            }

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
            '/api/release/owners/'        => 'api/release_owners',
            '/api/nightly/crashes/'       => 'api/nightly_crashes',
            '/calendar/release/schedule/' => 'ics_release_schedule',
            '/release/owners/'            => 'release_owners',
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
