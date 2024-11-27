<?php

declare(strict_types=1);

use ReleaseInsights\{Model, Template};

$data = new Model('404')->get();

http_response_code(404);

new Template(
    'regular.html.twig',
    [
        'page_title'   => '404: Page Not Found',
        'css_page_id'  => 'notfound',
        'page_content' => $data,
    ]
)->render();
