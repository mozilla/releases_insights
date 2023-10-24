<?php

declare(strict_types=1);

if (count($argv) < 5) {
    echo "Usage: version.php {source} {version} {commit} {build}\n";
    exit(1);
}

$json = [];
$json['source'] = $argv[1];
$json['version'] = $argv[2];
$json['commit'] = $argv[3];
$json['build'] = $argv[4];

echo json_encode($json);
