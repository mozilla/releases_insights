<?php

declare(strict_types=1);

use ReleaseInsights\Request;
use ReleaseInsights\Utils;

// We import the Utils class manually as we haven't autoloaded classes yet
include realpath(__DIR__ . '/../../')  . '/app/classes/ReleaseInsights/Utils.php';

// Is that a known suspicious IP?
$ips = [];
$target = realpath(__DIR__ . '/../../cache/')  . '/blockedIPs.json.cache';

if (file_exists($target)) {
    $ips = json_decode(file_get_contents($target));
}

$client_ip = Utils::getIP();

// Log suspicious IPs
$url_inspected = new Request(filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL));
if (Utils::inString(
    $url_inspected->request,
    ['wp-', 'adminer', 'hbk_ios', 'go.php', 'wordpress', 'phpmyadmin',
     'xmlrpc', 'civicrm', 'backup', 'health-check', 'wallet', 'php.php', '.env',
     'vendor', 'phpunit', 'includes', 'relatedlink', 'administrator', 'lock360',
     'administrator', '0z.php', ]
    )) {
    if (! in_array($client_ip, $ips) ) {
        $ips[] = $client_ip;
        file_put_contents($target, json_encode($ips));
        error_log("Suspicious $client_ip added to $target");
    }
}

// Block suspicious IPs
 if (in_array($client_ip, $ips)) {
    http_response_code(403);
    die('IP blocked.');
}

// Clean up temp variables from global space
unset ($client_ip, $ips, $target, $url_inspected);