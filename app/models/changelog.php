<?php

declare(strict_types=1);

use \ReleaseInsights\{Json, URL, Utils};

function s(mixed ...$args): string {
    return Utils::secureText(...$args);
}

$to     = s($_GET['to'] ?? null);
$from   = s($_GET['from'] ?? null);
$repo   = s($_GET['repo'] ?? 'mozilla-firefox/firefox');
$hg2git = isset($_GET['hg2git']);
$files  = isset($_GET['files']);

if ($hg2git) {
    $to   = Json::load(URL::Lando->value . $to)['git_hash'];
    $from = Json::load(URL::Lando->value . $from)['git_hash'];
}

return  [
    'from'   => $from,
    'to'     => $to,
    'repo'   => $repo,
    'hg2git' => $hg2git,
    'files' => $files,
];