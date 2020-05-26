<?php

require_once MODELS . 'home.php';

echo $twig->render(
    'regular.html.twig',
    [
        'page_title'   => $page_title,
        'css_files'    => $css_files,
        'css_page_id'  => $controller,
        'page_content' => $content,
    ]
);
