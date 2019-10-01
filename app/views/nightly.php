<?php

echo '<h1>'.date('Y M d', strtotime($date)).'</h1>';
echo "<ul>\n";
foreach ($nightlies as $buildid => $changeset) {
    echo '<li>'.$buildid.' = <a href="https://hg.mozilla.org/mozilla-central/changeset/'.$changeset.'">'.$changeset."</a></li>\n";
}
echo "</ul>\n";
