<?php

declare(strict_types=1);

use Cache\Cache;

$yesterday = date('Ymd', strtotime('yesterday'));

// We also use this page to flush the cache on demand
$flush_cache = $_GET['flush_cache'] ?? false;
if ($flush_cache === date('Ymd')) {
    Cache::flush();
}

// No data to prepare yet
$main_beta = BETA;
$nonce = NONCE;

return <<<"EOD"
<div class="w-75 alert alert-primary mx-auto text-center mb-3" role="alert">
This website is Mozilla's official reference for all things related to the Firefox release schedule.<br>
It is maintained by the <a href="https://wiki.mozilla.org/Release_Management/Team_members">Release Management team</a>.
</div>
<p class="mt-3 mb-3 w-75 justify-content-center mx-auto text-center fw-normal fs-5">

</p>

<h3 class="text-center w-75 mx-auto" id="pages"><a href="#pages" class="bi-link-45deg anchor-link link-info"></a>Pages</h3>
<table class="table table-light table-fxt-clean table-sm mb-3 w-75 justify-content-center">
    <tr class="table-warning">
        <th class="text-secondary-emphasis fw-semibold w-25">Endpoint</th><th class="text-secondary-emphasis fw-semibold">Description</th>
    </tr>
    <tr>
        <td><a href="/">/</a></td>
        <td>Homepage with a simple overview of the releases in flight</td>
    </tr>
    <tr>
        <td><a href="/about">about</a></td>
        <td>Get the list of views and public JSON API endpoints</td>
    </tr>
    <tr>
        <td><a href="/beta">beta</a></td>
        <td>Get the state of what was uplifted in the current beta cycle</td>
    </tr>
    <tr>
        <td><a href="/calendar">calendar</a></td>
        <td>Overview of our upcoming releases</td>
    </tr>
    <tr>
        <td><a href="/calendar/monthly/">calendar/monthly</a></td>
        <td>General calendar view of milestones for upcoming releases</td>
    </tr>
    <tr>
        <td>
            <ul class="list-unstyled mb-0">
                <li><a href="/nightly/">nightly</a> (today)</li>
                <li><a href="/nightly/?date={$yesterday}">nightly/?date={$yesterday}</a></li>
            </ul>
        </td>
        <td>Provides the list of nightly buildIDs for a day: crashes, changelog, bugs fixed</td>
    </tr>
    <tr>
        <td>
            <ul class="list-unstyled mb-0">
                <li><a href="/release/?version=nightly">release/?version=nightly</a></li>
                <li><a href="/release/?version=beta">release/?version=beta</a></li>
                <li><a href="/release/?version=release">release/?version=release</a></li>
                <li><a href="/release/?version=esr">release/?version=esr</a></li>
                <li><a href="/release/?version=90">release/?version=90</a></li>
            </ul>
        </td>
        <td>
        Provides historical data for past releases and basic release date information for future releases.<br>
        The <code>nightly</code>, <code>beta</code>, <code>release</code> and <code>esr</code> values are aliases to the current real version numbers.<br>
        The <code>esr</code> view is mostly a schedule of incoming releases and indicates when a new ESR branch happens in the year.
        </td>
    </tr>
    <tr>
        <td><a href="/release/owners/">release/owners</a></td>
        <td>List all past releases per release owner</td>
    </tr>
</table>


<h3 class="text-center w-75 mx-auto" id="json"><a href="#json" class="bi-link-45deg anchor-link link-info"></a>Json API</h3>
<table class="table table-light table-fxt-clean table-sm mb-3 w-75 justify-content-center">
    <tr class="table-warning">
        <th class="text-secondary-emphasis fw-semibold w-25">Endpoint</th><th class="text-secondary-emphasis fw-semibold">Description</th>
    </tr>

    <tr>
        <td><a href="/api/beta/crashes/"><span class="text-body-tertiary me-1">api/</span>beta/crashes</a></td>
        <td>Gives the crashes for all our current betas (data from Socorro)</td>
    </tr>

    <tr>
        <td><a href="/api/esr/releases/"><span class="text-body-tertiary me-1">api/</span>esr/releases</a></td>
        <td>Release dates for all ESR releases (including dot releases)</td>
    </tr>

    <tr>
        <td><a href="/api/external/"><span class="text-body-tertiary me-1">api/</span>external</a></td>
        <td>Gives the list of external APIs this site depends on to build data.</td>
    </tr>

    <tr>
        <td><a href="/api/firefox/chemspills/"><span class="text-body-tertiary me-1">api/</span>firefox/chemspills</a></td>
        <td>List Firefox dot releases which were in immediate response to a security incident or major incident.</td>
    </tr>

    <tr>
        <td><a href="/api/firefox/releases/"><span class="text-body-tertiary me-1">api/</span>firefox/releases</a></td>
        <td>Release dates for all past Firefox releases (including dot releases)</td>
    </tr>

    <tr>
        <td><a href="/api/firefox/releases/future/"><span class="text-body-tertiary me-1">api/</span>firefox/releases/future/</a></td>
        <td>Release dates for all future Firefox releases (including planned dot releases)</td>
    </tr>

    <tr>
        <td><a href="/api/nightly/?date={$yesterday}"><span class="text-body-tertiary me-1">api/</span>nightly/?date={$yesterday}</a></td>
        <td>Provides the list of nightly build IDs for a day and the changeset they were built from (data from buildhub)</td>
    </tr>

    <tr>
        <td><a href="/api/nightly/crashes/?buildid=20190927094817"><span class="text-body-tertiary me-1">api/</span>nightly/crashes/?buildid=20190927094817</a></td>
        <td>Gives the crashes for a buildID (data from Socorro)</td>
    </tr>

    <tr>
        <td><a href="/api/release/owners/"><span class="text-body-tertiary me-1">api/</span>release/owners</a></td>
        <td>Historical list of all release managers for Firefox major releases</td>
    </tr>

    <tr>
        <td>
            <ul class="list-unstyled mb-0">
                <li><a href="/api/release/schedule/?version=beta"><span class="text-body-tertiary me-1">api/</span>release/schedule/?version=beta</a> <span class="text-body-tertiary">(forecast)</span></li>
                <li><a href="/api/release/schedule/?version=release"><span class="text-body-tertiary me-1">api/</span>release/schedule/?version=release</a> <span class="text-body-tertiary">(actual)</span></li>
            </ul>
        </td>
        <td>Gives either the scheduled milestones for an upcoming major Desktop version or the actual schedule of builds for a past release.
        <br>Can be a version number or one of the <code>release</code>, <code>beta</code> or <code>nightly</code> keywords.</td>
    </tr>
</table>

<h3 class="text-center w-75 mx-auto" id="other"><a href="#other" class="bi-link-45deg anchor-link link-info"></a>Other resources</h3>
<table class="table table-light table-fxt-clean table-sm mb-3 w-75 justify-content-center">
    <tr class="table-warning">
        <th class="text-secondary-emphasis fw-semibold w-25">Endpoint</th><th class="text-secondary-emphasis fw-semibold">Description</th>
    </tr>
    <tr>
        <td><a href="/calendar/release/schedule/?version={$main_beta}">calendar/release/schedule/?version={$main_beta}</a></td>
        <td>Download an icalendar (.ics) file of an upcoming release milestones.</td>
    </tr>

    <tr>
        <td><a href="/rss/">rss</a></td>
        <td><img class="align-text-bottom" src="/assets/img/feed_icon.svg" alt="RSS feed logo">&thinsp;Subscribe to our RSS feed to get a notification when a new Firefox release is out.</td>
    </tr>

</table>

<h3 class="text-center w-75 mx-auto" id="credits"><a href="#credits" class="bi-link-45deg anchor-link link-info"></a>Credits</h3>
<table class="table table-light table-fxt-clean table-sm mb-3 w-75 justify-content-center">
    <tr class="table-warning">
        <th class="text-secondary-emphasis fw-semibold w-25">What</th>
        <th class="text-secondary-emphasis fw-semibold">Who</th>
    </tr>
    <tr>
        <td><a href="https://www.iconfinder.com/iconsets/circle-icons-1" target="_blank">Circle Icons</a></td>
        <td><img class="align-text-bottom" src="/assets/img/site_icon.svg" style="width:20px" nonce="{$nonce}">&thinsp;Train favicon by Nick Roach</td>
    </tr>
    </tr>
    <tr>
        <td><a href="https://github.com/rsms/inter/" target="_blank">Inter Font</a></td>
        <td>A⃝&thinsp;Inter Font by Rasmus</td>
    </tr>
    <tr>
        <td><a href="https://github.com/mozilla/releases_insights">Code</a></td>
        <td><img class="align-text-bottom" src="/assets/img/github-mark.svg" alt="GitHub logo" style="width:20px" nonce="{$nonce}">&thinsp;Source code for this website.</td>
    </tr>
</table>
EOD;
