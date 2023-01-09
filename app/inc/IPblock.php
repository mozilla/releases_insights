<?php

declare(strict_types=1);

use ReleaseInsights\Request;
use ReleaseInsights\Utils;

// Is that a known suspicious IP?
$ips = [];
$target = CACHE_PATH . 'blockedIPs.json.cache';
if (file_exists($target)) {
    $ips = json_decode(file_get_contents($target));
}

$client_ip = Utils::getIP();

// Log suspicious IPs
$url = new Request(filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL));
if (Utils::inString(
    $url->path,
    ['wp-', 'adminer.php', 'hbk_ios.php', 'go.php', 'wordpress']
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
unset ($client_ip, $ips, $target, $url);