<?php
use ReleaseInsights\Bugzilla as Bz;
use ReleaseInsights\Utils as Utils;

// Get nightlies for the GET Request (or today's nightly)
$nightlies = include MODELS . 'api_nightly.php';

// Strore a value for the View title
$display_date = date('Y M d', strtotime($date));

// We now fetch the previous day nightlies because we need them for changelogs
$_GET['date'] = date('Ymd', strtotime($date . ' -1 day'));
$nightlies_day_before = include MODELS . 'api_nightly.php';

// Associate nightly with the previous one
$nightly_pairs = [];

$i = true;
foreach ($nightlies as $buildid => $changeset) {
    // The first build of the day is to associate with yesterday's last build
    if ($i === true) {
        $nightly_pairs[] = [
            'buildid'        => $buildid,
            'changeset'      => $changeset,
            'prev_changeset' => end($nightlies_day_before), ];
        $i = false;
        $previous_changeset = $changeset;
        continue;
    }

    $nightly_pairs[] = [
        'buildid'        => $buildid,
        'changeset'      => $changeset,
        'prev_changeset' => $previous_changeset,
    ];
    $previous_changeset = $changeset;
}

$build_crashes = [];
foreach ($nightly_pairs as $dataset) {
    $build_crashes[$dataset['buildid']] = Utils::getCrashesForBuildID($dataset['buildid'])['total'];
}
cli_dump($build_crashes);
$top_sigs = [];
foreach ($nightly_pairs as $dataset) {
    $top_sigs[$dataset['buildid']] = array_splice(
        Utils::getCrashesForBuildID($dataset['buildid'])['facets']['signature'], 0, 10
    );
}

$bug_list = [];
foreach ($nightly_pairs as $dataset) {
    $bugs = Bz::getBugsFromHgWeb(
            'https://hg.mozilla.org/mozilla-central/json-pushes?fromchange=' . $dataset['prev_changeset'] . '&tochange=' . $dataset['changeset'] . '&full&version=2'
        )['total'];
    $url = Bz::getBugListLink($bugs);

    $bug_list[$dataset['buildid']] = [
        'bugs'  => $bugs,
        'url'   => $url,
        'count' => count($bugs),
    ];
}
