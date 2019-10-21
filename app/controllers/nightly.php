<?php

require_once MODELS.'nightly.php';

$template = $twig->loadTemplate('nightly.html.twig');

echo $template->render([
    'page_title' => $page_title,
    'display_date' => $display_date,
    'css_files' => $css_files,
    'css_page_id' => $css_page_id,
    'nightly_pairs' => $nightly_pairs,
    'build_crashes' => $build_crashes,
    'top_sigs' => $top_sigs,
]);



