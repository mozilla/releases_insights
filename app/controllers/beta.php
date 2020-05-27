<?php

require_once MODELS . 'beta.php';

print $twig->render(
    'beta.html.twig',
    [
        'current_beta'   => FIREFOX_BETA,
        'css_files'      => $css_files,
        'page_title'     => $page_title,
        'patches_beta'   => $patches_beta,
    ]
);
