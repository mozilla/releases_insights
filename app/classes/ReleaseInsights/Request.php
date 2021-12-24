<?php

declare(strict_types=1);

namespace ReleaseInsights;

class Request
{
    public string  $request;
    public string  $path;
    public ?string $query;

    public function __construct(string $path)
    {
        $request = parse_url($path);
        if ($request === false ) {
            $this->request = '/';
            $this->path = '/';
            $this->query = null;
        } else {
            $this->request = $path;
            $this->path  = $this->cleanPath($request['path']);
            if (isset($request['query'])) {
                $this->query = $request['query'];
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
            '/'                          => 'homepage',
            '/about'                     => 'about',
            '/nightly'                   => 'nightly',
            '/release'                   => 'release',
            '/api/nightly'               => 'api/nightly',
            '/api/release/schedule'      => 'api/release_schedule',
            '/api/release/owners'        => 'api/release_owners',
            '/api/nightly/crashes'       => 'api/nightly_crashes',
            '/calendar/release/schedule' => 'ics_release_schedule',
            default                         => '404',
        };
    }

    /**
     * Normalize path before comparing the string to a list of valid paths
     */
    public static function cleanPath(string $path): string
    {
        $path = explode('/', $path);
        $path = array_filter($path); // Remove empty items
        $path = array_values($path); // Reorder keys

        return '/' . implode('/', $path);
    }
}
