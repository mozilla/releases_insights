<?php

declare(strict_types=1);

use ReleaseInsights\Template;

/* Maintenance page controller, no model needed, make a page in maintenance point to this controller when needed */
http_response_code(503);

new Template(
    'maintenance.html.twig',
    [
        'page_title'   => 'Page momentarily not available ',
        'page_content' => 'This page is currently not available, please check later.',
    ]
)->render();