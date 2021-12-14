<?php

declare(strict_types=1);

require_once MODELS . 'about.php';

print $twig->render(
    'regular.html.twig',
    [
        'page_title'   => 'Firefox Desktop Release insights',
        'css_files'    => ['base.css'],
        'css_page_id'  => $controller,
        'page_content' => $content,
    ]
);
