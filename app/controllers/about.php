<?php

declare(strict_types=1);

require_once MODELS . 'about.php';

print $twig->render(
    'regular.html.twig',
    [
        'page_title'   => $page_title,
        'css_files'    => $css_files,
        'css_page_id'  => $controller,
        'page_content' => $content,
    ]
);
