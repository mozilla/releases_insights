<?php

declare(strict_types=1);

use ReleaseInsights\{Release, Version};

// We may call this file with a specific version number defined in the controller
if (! isset($requested_version)) {
    $requested_version = Version::get();
}

if ((int) $requested_version < BETA) {
    return ['error' => 'API only works with future release.'];
}

return (new Release($requested_version))->getSchedule();