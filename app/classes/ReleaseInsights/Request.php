<?php

declare(strict_types=1);

namespace ReleaseInsights;

class Request
{
    public function __construct(public $path)
    {
    }

    /**
     * Return the name of the controller file for the requested URL
     * If the path is unknown, we send a 404 response
     */
    public function getController(): string
    {
        return match ($this->path) {
            '/'                         => 'homepage',
            'about'                     => 'about',
            'nightly'                   => 'nightly',
            'release'                   => 'release',
            'api/nightly'               => 'api/nightly',
            'api/release/schedule'      => 'api/release_schedule',
            'api/release/owners'        => 'api/release_owners',
            'api/nightly/crashes'       => 'api/nightly_crashes',
            'calendar/release/schedule' => 'ics_release_schedule',
            default                     => '404',
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

        return implode('/', $path);
    }
}
