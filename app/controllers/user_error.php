<?php

declare(strict_types=1);

use ReleaseInsights\Template;

/* Error page controller. No model needed. Use to display an error when GET parameters are invalid */
http_response_code(400);

new Template(
    'user_error.html.twig',
    [
        'page_title'   => 'Invalid parameters',
        'page_content' => $error ?? 'Request malformed or data not available yet.',
    ]
)->render();