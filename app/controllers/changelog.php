<?php

declare(strict_types=1);

use ReleaseInsights\Model;

$data = new Model('changelog')->get();

if (empty($data['to']) || empty($data['from'])) {
    echo 'Missing sha1s';
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
