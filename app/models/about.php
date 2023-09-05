<?php

declare(strict_types=1);

use \Cache\Cache;

$yesterday = date('Ymd', strtotime('yesterday'));

// We also use this page to flush the cache on demand
$flush_cache = $_GET['flush_cache'] ?? false;
if ($flush_cache === date('Ymd')) {
    Cache::flush();
}

// No data to prepare yet
$main_beta = BETA;

return $content = <<<"EOD"

<h3>Public Json API</h3>
<p>All APIs are under the <code>api/</code> endpoint.</p>
<h3>APIs</h3>
<table class="table table-light table-striped table-bordered table-sm">
    <colgroup>
      <col>
      <col>
    </colgroup>
    <tr class="table-dark">
        <th>URL</th><th>Description</th>
    </tr>
    <tr>
        <td><a href="/api/nightly/?date={$yesterday}">api/nightly/?date={$yesterday}</a></td>
        <td>Provides the list of nightly build IDs for a day and the changeset they were built from (data from buildhub).</td>
    </tr>
    <tr>
        <td><a href="/api/nightly/crashes/?buildid=20190927094817">api/nightly/crashes/?buildid=20190927094817</a></td>
        <td>Gives the crashes for a buildID (data from Socorro).</td>
    </tr>
    <tr>
        <td><a href="/api/release/schedule/?version=beta">api/release/schedule/?version=beta</a></td>
        <td>Gives the forecast release 4 week scheduled milestones for an upcoming major version. Can be a version number or one of the <code>beta</code> or <code>nightly</code> keywords.</td>
    </tr>
    <tr>
        <td><a href="/api/release/owners/">api/release/owners/</a></td>
        <td>Historical list of all release managers for Firefox major release. We don’t have the names before Firefox 27</td>
    </tr>
    <tr>
        <td><a href="/api/esr/releases/">api/esr/releases/</a></td>
        <td>Release dates for all ESR releases (including dot releases)</td>
    </tr>
    <tr>
        <td><a href="/api/firefox/releases/">api/firefox/releases/</a></td>
        <td>Release dates for all Firefox releases (including dot releases)</td>
    </tr>
</table>

<h3>Views</h3>
<table class="table table-light table-striped table-bordered table-sm">
    <colgroup>
      <col>
      <col>
    </colgroup>

    <tr class="table-dark">
        <th>URL</th><th>Description</th>
    </tr>
    <tr>
        <td><a href="/">/</a></td>
        <td>Homepage with a simple overview of the releases in flight.</td>
    </tr>
    <tr>
        <td><a href="/about">about</a></td>
        <td>Get the list of views and public JSON API endpoints.</td>
    </tr>
    <tr>
        <td><a href="/calendar">calendar</a></td>
        <td>Overview of our upcoming releases.</td>
    </tr>
    <tr>
        <td><a href="/calendar/monthly/">calendar/monthly/</a></td>
        <td>General calendar view of milestones for upcoming releases.</td>
    </tr>
    <tr>
        <td>
            <ul class="list-unstyled mb-0">
                <li><a href="/nightly/">nightly</a> (today)</li>
                <li><a href="/nightly/?date={$yesterday}">nightly/?date={$yesterday}</a></li>
            </ul>
        </td>
        <td>Provides the list of nightly buildIDs for a day, their crashes, changelog, bugs fixed.</td>
    </tr>
    <tr>
        <td>
            <ul class="list-unstyled mb-0">
                <li><a href="/release/?version=nightly">release/?version=nightly</a></li>
                <li><a href="/release/?version=beta">release/?version=beta</a></li>
                <li><a href="/release/?version=release">release/?version=release</a></li>
                <li><a href="/release/?version=esr">release/?version=esr</a></li>
                <li><a href="/release/?version=90">release/?version=90</a></li>
        </td>
        <td>
        Provides historical data for past releases and basic release date information for future releases.<br>
        The <code>nightly</code>, <code>beta</code>, <code>release</code> and <code>esr</code> values are aliases to the current real version numbers.<br>
        The <code>esr</code> view is mostly a schedule of incoming releases and indicates when a new ESR branch happens in the year.
        </td>
    </tr>
    <tr>
        <td><a href="/release/owners/">release/owners/</a></td>
        <td>List all past releases per release owner.</td>
    </tr>
    <tr>
        <td><a href="/calendar/release/schedule/?version={$main_beta}">calendar/release/schedule/?version={$main_beta}</a></td>
        <td>Download an icalendar (.ics) file of future milestones for a future release. Can be imported into your calendar application.</td>
    </tr>
</table>

<h3>Credits</h3>
<table class="table table-light table-striped table-bordered table-sm">
    <tr class="table-dark">
        <th>What</th>
        <th>Who</th>
    </tr>
    <tr>
        <td>Train favicon</td>
        <td><a href="https://www.iconfinder.com/iconsets/circle-icons-1" target="_blank">Circle Icons</a> by Nick Roach</td>
    </tr>
</table>
EOD;
