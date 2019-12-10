<?php

require_once MODELS . 'release.php';

$template = $twig->loadTemplate('release.html.twig');

echo $template->render([
    'current_release'=> FIREFOX_RELEASE,
    'page_title'     => $page_title,
]);

