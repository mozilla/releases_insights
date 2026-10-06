<?php

declare(strict_types=1);

use \ReleaseInsights\{Json, URL};

/*
    Only accept values that fit in a single URL path segment (sha1s, tags,
    branch names) and an owner/name GitHub repository, anything else would
    let users craft redirections to arbitrary GitHub pages or alter the Lando
    API path we query.
*/
$ref = fn (string $param): string
    => is_string($_GET[$param] ?? null) && preg_match('/^[\w.-]{1,100}$/', $_GET[$param])
        ? $_GET[$param]
        : '';

$to     = $ref('to');
$from   = $ref('from');
$repo   = $_GET['repo'] ?? 'mozilla-firefox/firefox';
$repo   = is_string($repo) && preg_match('/^[\w.-]{1,100}\/[\w.-]{1,100}$/', $repo)
    ? $repo
    : '';
$hg2git = isset($_GET['hg2git']);
$files  = isset($_GET['files']);

if ($hg2git && $to !== '' && $from !== '') {
    $to   = Json::load(URL::Lando->value . $to)['git_hash'] ?? '';
    $from = Json::load(URL::Lando->value . $from)['git_hash'] ?? '';
}

return  [
    'from'   => $from,
    'to'     => $to,
    'repo'   => $repo,
    'hg2git' => $hg2git,
    'files' => $files,
];
