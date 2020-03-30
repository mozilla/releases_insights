<?php

require_once MODELS . 'beta.php';

echo $twig->render(
    'beta.html.twig',
    [
        'current_beta'   => FIREFOX_BETA,
        'page_title'     => $page_title,
        'patches_beta'   => $patches_beta
    ]
);

