<?php

require_once MODELS.'home.php';

$template = $twig->loadTemplate('normal.html.twig');
echo $template->render([
    'page_title' => $page_title,
    'css_files' => $css_files,
    'css_page_id' => $css_page_id,
    'page_content' => $content
]);
