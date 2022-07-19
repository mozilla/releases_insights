<?php

declare(strict_types=1);

// Rebuild a version_number => date array
return (new ReleaseInsights\Data())->getESRReleases();
