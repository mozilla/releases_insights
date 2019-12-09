<?php

require_once MODELS . 'beta.php';

$template = $twig->loadTemplate('beta.html.twig');

echo $template->render([
    'current_beta'   => FIREFOX_BETA,
    'page_title'     => $page_title,
    'patches_beta'   => $patches_beta
]);

