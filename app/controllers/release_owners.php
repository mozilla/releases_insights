<?php

declare(strict_types=1);

use ReleaseInsights\{Model, Template};

new Template(
    'release_owners.html.twig',
    [
        'page_title'  => 'Major releases per release owner',
        'css_page_id' => 'release_owners',
        'owners'      => new Model('owners')->get(),
    ]
)->render();
