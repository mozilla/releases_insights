<?php
// Analyse version requested

// If there is no version requested show the latest beta
if (!isset($_GET['version'])) {
    $_GET['version'] = FIREFOX_BETA;
}
// Normalize version number to XX.y
$requested_version = abs((int) $_GET['version']);
$requested_version = number_format($requested_version, 1);

// Planned releases
$upcoming_releases = include DATA .'upcoming_releases.php';

// our Json view outputs data stored in the $json variable
$json = include MODELS . 'api_releaseschedule.php';
require_once VIEWS . 'json.php';
