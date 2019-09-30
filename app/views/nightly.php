<?php

print "<ul>\n";
foreach($nightlies as $buildid => $changeset) {
    print '<li>' . $buildid . ' = <a href="https://hg.mozilla.org/mozilla-central/changeset/' . $changeset . '">' . $changeset . "</a></li>\n";
}
print "</ul>\n";
