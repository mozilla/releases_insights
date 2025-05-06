<?php

declare(strict_types=1);

use ReleaseInsights\ESR;

$data = [];
foreach(range(75, RELEASE) as $version) {
    $data[$version] = [ESR::getVersion($version), ESR::getOlderSupportedVersion($version)];
}

/*
'ESR'       => ESR::getVersion($requested_version_int),
'OLDER_ESR' => ESR::getOlderSupportedVersion($requested_version_int),
'ESR_115'   => ESR::getWin7SupportedVersion($requested_version_int),
*/

// Rebuild a version_number => date array
return $data;
