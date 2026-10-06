<?php

declare(strict_types=1);

use ReleaseInsights\Model;

$data = new Model('changelog')->get();

if (empty($data['to']) || empty($data['from']) || empty($data['repo'])) {
    http_response_code(400);
    echo 'Missing or invalid parameters';
    exit;
}

$target = 'https://github.com/'
    . $data['repo']
    . '/compare/'
    . $data['from']
    . '...'
    . $data['to']
    . ($data['files'] ? '#files_bucket' : '');

header('Location: ' . $target, true, 302);
exit;
