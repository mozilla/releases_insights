<?php

declare(strict_types=1);

require_once MODELS . '404.php';

http_response_code(404);

print $twig->render(
    'regular.html.twig',
    [
        'page_title'   => '404: Page Not Found',
        'css_files'    => ['base.css'],
        'css_page_id'  => 'notfound',
        'page_content' => $content,
    ]
);
