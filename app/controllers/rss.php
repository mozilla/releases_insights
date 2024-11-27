<?php

declare(strict_types=1);

use ReleaseInsights\{Model, Template};

[$latest_release_date, $releases] = new Model('rss')->get();

header("Content-Type: application/xml; charset=UTF-8");

new Template(
    'releases.rss.twig',
    [
        'title'                  => 'Firefox Desktop and Android releases',
        'description'            => 'Release dates for Firefox Desktop and Android. Includes major and minor releases.',
        'site_link'              => 'https://whattrainisitnow.com',
        'latest_release_date'    => $latest_release_date,
        'releases'               => $releases,
       ]
)->render();