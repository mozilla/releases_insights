<?php
use Cache\Cache;

// Get nightlies for the GET Request (or today's nightly)
$nightlies = include MODELS.'api_nightly.php';
cli_dump($nightlies);
// We now fetch the previous day nightlies because we need them for changelogs
$_GET['date'] = date('Ymd', strtotime($date . ' -1 day'));
$nightlies_day_before = include MODELS.'api_nightly.php';
cli_dump($nightlies_day_before);

// Associate nightly with the previous one
$nightly_pairs = [];

$i = true;
foreach ($nightlies as $buildid => $changeset) {

    // The first build of the day is to associate with yesterday's last build
    if ($i === true) {
        $nightly_pairs[] = [
            'buildid' => $buildid,
            'changeset' => $changeset,
            'prev_changeset' => end($nightlies_day_before) ];
        $i = false;
        $previous_changeset = $changeset;
        continue;
    }


    $nightly_pairs[] = [
        'buildid' => $buildid,
        'changeset' => $changeset,
        'prev_changeset' => $previous_changeset,
    ];
    $previous_changeset = $changeset;
}


cli_dump($nightly_pairs);

// Cache Socorro data
$crashes = [];

foreach ($nightly_pairs as $dataset) {

    $crashes = json_decode($cache_id, true);
}

function getCrashesForBuildID(int $buildid) : array {
    // The date in the string varies so we create a unique file name in cache
    $cache_id = 'https://crash-stats.mozilla.com/api/SuperSearch/?build_id=' . $buildid . '&_facets=signature';

    // If we can't retrieve cached data, we create and cache it.
    // We cache because we want to avoid http request latency
    if (!$crashes = Cache::getKey($cache_id)) {
        $crashes = file_get_contents($cache_id);
    }

    return json_decode($crashes, true);
}
