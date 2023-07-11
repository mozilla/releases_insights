<?php

declare(strict_types=1);

(new ReleaseInsights\Template(
    'regular.html.twig',
    [
        'page_title'   => 'Firefox Trains - List of public APIs and pages',
        'css_page_id'  => 'about',
        'page_content' => require_once MODELS . 'about.php',
    ]
))->render();
