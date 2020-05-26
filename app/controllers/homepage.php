<?php

require_once MODELS . 'home.php';

echo $twig->render(
    'overview.html.twig',
    [
        'page_title'   => $page_title,
        'css_files'    => $css_files,
        'css_page_id'  => $controller,
        'page_content' => $content,
    ]
);
