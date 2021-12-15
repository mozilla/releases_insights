<?php

declare(strict_types=1);

namespace ReleaseInsights;

class Request
{
    public $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Return the name of the controller file for the requested URL
     * If the path is unknown, we send a 404 response
     */
    public function getController(): string
    {
        Utils::dump($this->path);
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
}
