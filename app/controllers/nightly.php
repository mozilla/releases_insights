<?php
use ReleaseInsights\Utils as Utils;


require_once MODELS.'nightly.php';

$template = $twig->loadTemplate('nightly.html.twig');
foreach ($nightly_pairs as $dataset) {
    $build_crashes[$dataset['buildid']] = Utils::getCrashesForBuildID($dataset['buildid'])['total'];
}

echo $template->render([
    'page_title' => $page_title,
    'display_date' => $display_date,
    'css_files' => $css_files,
    'css_page_id' => $css_page_id,
    'page_content' => $content,
    'nightly_pairs' => $nightly_pairs,
    'build_crashes' => $build_crashes,
]);



