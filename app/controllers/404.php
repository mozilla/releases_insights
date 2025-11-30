<?php

declare(strict_types=1);

use ReleaseInsights\{Json, Model, Template};

if (str_starts_with($_SERVER['REQUEST_URI'], '/api/')) {
    // For a 404 on the API we want to return a JSON error, not HTML
    die(new Json(['error' => 'Not Found'])->outputError(404));
}

http_response_code(404);

new Template(
    'regular.html.twig',
    [
        'page_title'   => '',
        'css_page_id'  => 'notfound',
        'page_content' => new Model('404')->get(),
    ]
)->render();
