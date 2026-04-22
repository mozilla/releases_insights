<?php

declare(strict_types=1);

use Cache\Cache;

$yesterday = date('Ymd', strtotime('yesterday'));

// We also use this page to flush the cache on demand
$flush_cache = $_GET['flush_cache'] ?? false;
if ($flush_cache === date('Ymd')) {
    Cache::flush();
}

return [
    'yesterday' => $yesterday,
];
