<?php

declare(strict_types=1);

use ReleaseInsights\{Request, Utils};

// We import the Utils class manually as we haven't autoloaded classes yet
include dirname(__DIR__, 2)  . '/app/classes/ReleaseInsights/Utils.php';

// We store Blocked query paths in this file
$bad_paths = include dirname(__DIR__, 2)  . '/app/data/suspicious_paths.php';

// Is that a known suspicious IP?
$IPs = [];
$blocked_IP_file = dirname(__DIR__, 2) . '/cache/blockedIPs.json.cache';

if (file_exists($blocked_IP_file)) {
    $IPs = json_decode(file_get_contents($blocked_IP_file), true);
}

$client_IP = Utils::getIP();

// Log suspicious IPs that access paths that are known vulnerabilities in frameworks
$url_inspected = new Request(filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL));
if (Utils::inString($url_inspected->request, $bad_paths)) {
    if (! in_array($client_IP, $IPs) ) {
        $IPs[] = $client_IP;
        file_put_contents($blocked_IP_file, json_encode($IPs));
        error_log("Suspicious $client_IP added to $blocked_IP_file: access to  $url_inspected->request");
    }
}

// Block XSS scanners that are hammering the server creating dozens of 404s per minute
$not_found_IPs = [];
$not_found_query_IP_file = dirname(__DIR__, 2) . '/cache/404_IPs.json.cache';

if (file_exists($not_found_query_IP_file)) {
    $not_found_IPs = json_decode(file_get_contents($not_found_query_IP_file), true);
    // This is a safety measure in case the file is corrupted or note readable (permissions)
    if (! is_array($not_found_IPs)) {
        $not_found_IPs = [];
    }
}

if ($url_inspected->getController() == '404') {
    // We don't want to block 404 IPs in page content testing via the Verif libraries as we test 404 behaviour
    if (! file_exists(dirname(__DIR__, 2) . '/cache/devmachine.cache')) {
        if (array_key_exists($client_IP, $not_found_IPs)) {
            $not_found_IPs[$client_IP]++;
        } else {
            $not_found_IPs[$client_IP] = 1;
        }
        file_put_contents($not_found_query_IP_file, json_encode($not_found_IPs));
    }
}

// Block suspicious IPs by 404
if (array_key_exists($client_IP, $not_found_IPs) && $not_found_IPs[$client_IP] > 6) {
    if (! in_array($client_IP, $IPs) ) {
        $IPs[] = $client_IP;
        file_put_contents($blocked_IP_file, json_encode($IPs));
        error_log("Suspicious $client_IP added to $not_found_query_IP_file: (XSS scanner?). Last endpoint: $url_inspected->path");
    }
}

// Block suspicious IPs by url
if (in_array($client_IP, $IPs)) {
    http_response_code(403);
    error_log("IP $client_IP blocked.");
    exit('Access denied.');
}

// Clean up temp variables from global space
unset (
    $bad_paths,
    $blocked_IP_file,
    $client_IP,
    $IPs,
    $not_found_IPs,
    $not_found_query_IP_file,
    $url_inspected
);