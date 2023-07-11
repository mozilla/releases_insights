<?php

declare(strict_types=1);

http_response_code(404);

(new ReleaseInsights\Template(
    'regular.html.twig',
    [
        'page_title'   => '404: Page Not Found',
        'css_page_id'  => 'notfound',
        'page_content' => require_once MODELS . '404.php',
    ]
))->render();
