<?php

$yesterday = date('Ymd', strtotime('yesterday'));
// No data to prepare yet
$content = <<<"EOD"

<h3>Public Json API</h3>
<p>All APIs are under the <code>api/</code> endpoint.
<h3>APIs</h3>
<table class="table table-light table-striped table-bordered table-sm">
    <tr class="thead-dark">
        <th>url</th><th>Description</th>
    </tr>
    <tr>
        <td><a href="/api/nightly/?date={$yesterday}">api/nightly/?date={$yesterday}</a></td>
        <td>Provides the list of nightly buildids for a day and the changeset they were built from (data from buildhub).</td>
    </tr>
    <tr>
        <td><a href="/api/nightly/crashes/?buildid=20190927094817">api/nightly/crashes/?buildid=20190927094817</a></td>
        <td>Gives the crashes for a buildID (data from Socorro).</td>
    </tr>
    <tr>
        <td><a href="/api/release/schedule/?version=beta">api/release/schedule/?version=beta</a></td>
        <td>Gives the forecast release 4 week scheduled milestones for an upcoming major version. Can be a version number or one of the <code>beta</code> or <code>nightly</code> keywords.</td>
    </tr>
</table>

<h3>Views</h3>
<table class="table table-light table-striped table-bordered table-sm">
    <tr class="thead-dark">
        <th>url</th><th>Description</th>
    </tr>
    <tr>
        <td><a href="/">/</a></td>
        <td>Homepage with a simple overview of the releases in flight.</td>
    </tr>
    <tr>
        <td><a href="/about">about</a></td>
        <td>Get the list of views and APIs available in this application.</td>
    </tr>
    <tr>
        <td>
            <ul class="list-unstyled mb-0">
                <li><a href="/nightly/">nightly/</a> (today)</li>
                <li><a href="/nightly/?date={$yesterday}">nightly/?date={$yesterday}</a></li>
            </ul>
        </td>
        <td>Provides the list of nightly buildIDs for a day, their crashes, changelog, bugs fixed.</td>
    </tr>
    <tr>
        <td><a href="/beta/">beta</a></td>
        <td><b>WIP:</b> Will provides the list of beta builds for the current release and uplifts per beta.</td>
    </tr>
    <tr>
        <td><a href="/release/">release</a></td>
        <td>Provides historical data for past releases and basic release date information for future releases.</td>
    </tr>
    <tr>
        <td><a href="/calendar/release/schedule/?version={$main_beta}">/calendar/release/schedule/?version={$main_beta}</a></td>
        <td>Download an icalendar (.ics) file of future milestones for a future release. Can be imported into your calendar application.</td>
    </tr>
</table>

EOD;
