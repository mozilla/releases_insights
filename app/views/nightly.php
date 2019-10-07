<?php
use Cache\Cache;

echo '<h1>'.date('Y M d', strtotime($date)).'</h1>';
echo "<ul>\n";
foreach ($nightly_pairs as $dataset) {
    echo '<li><span>'
    . '<a href="https://hg.mozilla.org/mozilla-central/changeset/'.$dataset['changeset'] .'">' . $dataset['buildid'] . '</a>'
    . ' </span> '
    . ' <span> '
    . '<a href="https://hg.mozilla.org/mozilla-central/pushloghtml?fromchange='. $dataset['prev_changeset'] .'&tochange=' .$dataset['changeset']. '">Changelog</a>'
    . ' </span>'
    . '<span><a href="https://crash-stats.mozilla.com/api/SuperSearch/?build_id='
    . $dataset['buildid']
    . '&_facets=signature">'
    . getCrashesForBuildID($dataset['buildid'])['total']
    . ' crashes</a> </span>'
    . '</li>'
    . "\n";
}
echo "</ul>\n";
