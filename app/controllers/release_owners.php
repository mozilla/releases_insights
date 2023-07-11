<?php

declare(strict_types=1);

(new ReleaseInsights\Template(
    'release_owners.html.twig',
    [
        'page_title'  => 'Major releases per release owner',
        'css_page_id' => 'release_owners',
        'owners'      => require_once MODELS . 'release_owners.php',
    ]
))->render();
