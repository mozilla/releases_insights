<?php

declare(strict_types=1);

$owners = (new ReleaseInsights\Data())->getReleaseDuty();

// Reconstruct the array to have integers as version numbers in keys
return array_combine(
    array_map('intval', array_keys($owners)),
    array_values($owners)
);
