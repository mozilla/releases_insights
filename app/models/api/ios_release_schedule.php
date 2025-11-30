<?php

declare(strict_types=1);

use ReleaseInsights\{IOS, Version};

// We may call this file with a specific version number defined in the controller
$requested_version ??= Version::get();

return new IOS($requested_version)->getSchedule();