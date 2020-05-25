<?php

$link = function ($url, $text, $title = true) {
    $title = $title ? '&title=' . rawurlencode($text) : '';
    return '<a href="' . $url . $title . '" target="_blank" rel="noopener">' . $text . '</a>';
};

ob_start();

print '<h5>Patches uplifted for each beta</h5><ul>';

for ($i = 2; $i <= $last_beta + 1; $i++) {
    $beta_previous = ($i - 1) < 3 ? 'DEVEDITION_' : 'FIREFOX_';
    $beta_current_type = $i < 3 ? 'DEVEDITION_' : 'FIREFOX_';

    $beta_previous .= $main_beta . '_0b' . ($i - 1) . '_RELEASE';
    $beta_current = ($i - 1 === $last_beta)
        ? 'tip'
        : $main_beta . '_0b' . $i . '_RELEASE';

    if ($beta_current !== 'tip') {
        $beta_current = $beta_current_type . $beta_current;
    }

    $hg_link =
        'https://hg.mozilla.org/releases/mozilla-beta/pushloghtml?fromchange='
        . $beta_previous
        . '&amp;tochange='
        . $beta_current;
    print '            <li>' . $link($hg_link, 'Beta' . $i, $title = false) . "</li>\n";
}
print '</ul>';
$patches_beta = ob_get_contents();
ob_end_clean();
