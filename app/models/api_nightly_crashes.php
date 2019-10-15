<?php
use Cache\Cache;
use Json\Json;
Use ReleaseInsights\Utils as Utils;

// TODO: remove hardcoded value for buildid
$buildid = Utils::getBuildID();

if (!Utils::isBuildID($buildid)) {
    return ['Error' => 'Invalid build ID'];
}

return json_decode(file_get_contents('https://crash-stats.mozilla.com/api/SuperSearch/?build_id=' . $buildid . '&_facets=signature'), true);
