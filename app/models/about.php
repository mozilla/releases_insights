<?php

declare(strict_types=1);

use Cache\Cache;

$yesterday = date('Ymd', strtotime('yesterday'));

// We also use this page to flush the cache on demand
$flush_cache = $_GET['flush_cache'] ?? false;
if ($flush_cache === date('Ymd')) {
    Cache::flush();
}


// Extract deployed sha1 to link to it
$filename = WEB_ROOT . 'deployed-version.txt';
$sha1 = 'master';
if (! LOCALHOST) {
    if (file_exists($filename)) {
        $sha1 = trim(file_get_contents($filename));

        // Optional: Validate that it is actually a SHA1 string
        if (! ctype_xdigit($sha1) && strlen($sha1) !== 40) {
            $sha1 = 'master';
        }
    }
}


return [
    'yesterday' => $yesterday,
    'sha1'      => $sha1,
];
