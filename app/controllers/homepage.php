<?php

require_once MODELS . 'home.php';

print $twig->render(
    'overview.html.twig',
    [
        'page_title'          => $page_title,
        'css_files'           => $css_files,
        'css_page_id'         => $controller,
        'beta_cycle_dates'    => $beta_cycle_dates,
        'nightly_cycle_dates' => $nightly_cycle_dates,
        'release_day'         => $today_is_release_day,
        'shipping_release'    => $shipping_release,
    ]
);
