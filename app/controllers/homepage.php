<?php

require_once MODELS . 'home.php';

print $twig->render(
    'overview.html.twig',
    [
        'page_title'   => $page_title,
        'css_files'    => $css_files,
        'css_page_id'  => $controller,
        'cycle_dates'  => $cycle_dates,
    ]
);
