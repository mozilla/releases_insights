<?php

declare(strict_types=1);

use ReleaseInsights\{Model, Template};

[$latest_release_date, $releases] = new Model('rss_esr')->get();

header("Content-Type: application/xml; charset=UTF-8");

new Template(
    'releases.rss.twig',
    [
        'title'                  => 'Firefox ESR releases',
        'description'            => 'Release dates for Firefox Extended Support Releases (ESR), for all supported ESR branches.',
        'site_link'              => 'https://whattrainisitnow.com/release/?version=esr',
        'feed_link'              => 'https://whattrainisitnow.com/rss/esr/',
        'latest_release_date'    => $latest_release_date,
        'releases'               => $releases,
       ]
)->render();
