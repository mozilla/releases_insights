#!/usr/bin/env python3
"""
Announces a Firefox iOS release in Slack once the App Store confirms it is live.
Firefox iOS scheduled releases are always scheduled to rollout at the same time at
02:00 UTC on Mondays.

Run weekly by .github/workflows/ios-release-slack.yml, a couple of hours after the
02:00 UTC Monday scheduled rollout.

Two independent sources have to agree before anything is posted:
  1. The iOS schedule says a release was due today (so we know which version).
  2. The App Store says that exact version is the one now live.

That means a delayed or pulled rollout is never announced as live. When a release
was due but has not shipped, release management get a heads-up instead.

The App Store is read twice before that heads-up goes out. Apple serves the lookup
API with a 24 hour cache lifetime, so an edge node that answered before the rollout
can report the previous version for the rest of the day and make a shipped release
look late. The web listing carries the same two facts with a 15 minute lifetime, so
it settles the disagreement.

Two webhooks, set from repo secrets in the workflow:
  SLACK_WEBHOOK_URL    announcement channel      (IOS_RELEASE_SLACK_WEBHOOK)
  RELMAN_WEBHOOK_URL   release management channel (RELMAN_SLACK_WEBHOOK), used
                       only when a scheduled release has not actually shipped

Env vars for testing:
  DRY_RUN=1           print the message instead of posting it. Accepts
                      1/true/yes/on to enable and 0/false/no/off or empty to
                      disable; anything else is an error rather than a guess.
  TEST_DATE=Y-m-d     pretend today is this date (e.g. 2026-07-27)
"""
import json
import os
import re
import sys
import time
import urllib.error
import urllib.request
from collections.abc import Callable
from datetime import date, datetime, timezone
from typing import Any

SCHEDULE_URL = "https://whattrainisitnow.com/api/release/schedule/?version=nightly"
IOS_SCHEDULE_URL = "https://whattrainisitnow.com/api/release/schedule/ios/?version={}"
# Firefox iOS App Store listing. Unknown query parameters form part of Apple's cache
# key, so filling the unused one with the current time asks for a URL no edge node
# holds yet, which gets us today's answer rather than yesterday's.
ITUNES_URL = "https://itunes.apple.com/lookup?id=989804926&country=us&_={}"
# The same listing as a web page, read only when the lookup API disagrees with the
# schedule.
APPS_URL = "https://apps.apple.com/us/app/firefox-fast-private-browser/id989804926"

# The page ships its data as JSON in a script tag. Scraping the rendered markup instead
# would mean matching class names like "svelte-1t5dyc5", which are build hashes and
# rotate whenever Apple redeploys.
SERVER_DATA = re.compile(
    r'<script type="application/json" id="serialized-server-data">(.*?)</script>',
    re.DOTALL,
)
# Where the version sits in that blob. Undocumented and unversioned, hence
# AppStorePageError below: a change of shape has to read as "could not confirm".
VERSION_ITEM = ("data", 0, "data", "shelfMapping", "mostRecentVersion", "items", 0)
# secondarySubtitle is a JavaScript Date.toString(), e.g. "Mon Aug 17 2026 03:01:57
# GMT+0000 (Coordinated Universal Time)". Keep the offset, drop the trailing name.
JS_DATE = re.compile(r"(.+? GMT[+-]\d{4})")
JS_DATE_FORMAT = "%a %b %d %Y %H:%M:%S GMT%z"

MESSAGE = "Firefox iOS v{version} is live with a phased rollout as of {rolled_out}"

MISMATCH_MESSAGE = (
    "Firefox iOS v{expected} was scheduled to roll out today, but the App Store is "
    "showing v{live} (live since {rolled_out}). Investigate why v{expected} is not live."
)

# Sent when the two App Store sources cannot be made to agree. It has to claim less
# than MISMATCH_MESSAGE does: all we know is that a release was due and that nothing
# confirmed it, which is worth a look either way.
UNCONFIRMED_MESSAGE = (
    "Firefox iOS v{expected} was scheduled to roll out today. The App Store lookup "
    "shows v{live}, and that could not be checked against the App Store page "
    "({reason}), so whether v{expected} is live is unknown. Please check manually."
)

# The shipping iOS major is behind the current Nightly by two versions. Desktop merge day is a
# Thursday, so by the Monday rollout Nightly has always moved on.
NIGHTLY_OFFSET = 2

# The job runs once a week, so a few seconds of waiting is cheap next to missing
# an announcement because whattrainisitnow or the iTunes API blipped.
TIMEOUT_SECONDS = 15
RETRIES = 3
BACKOFF_SECONDS = 5


class AppStorePageError(Exception):
    """The App Store page did not hold a version where one was expected."""


# HTTPError subclasses URLError, so this covers HTTP errors, connection failures
# and timeouts. JSONDecodeError catches an error page served in place of JSON.
# A truncated page is worth another go too, so AppStorePageError joins them.
RETRYABLE = (
    urllib.error.URLError,
    TimeoutError,
    json.JSONDecodeError,
    AppStorePageError,
)

# Env vars are strings, and every non-empty string is truthy, so bool("0") is True.
# Spell the accepted values out rather than let DRY_RUN=false enable a dry run.
TRUTHY = frozenset({"1", "true", "yes", "on"})
FALSEY = frozenset({"", "0", "false", "no", "off"})


def env_flag(name: str) -> bool:
    """Parse a boolean env var. An unrecognised value is an error, not a guess."""
    value = os.environ.get(name, "").strip().lower()
    if value in TRUTHY:
        return True
    if value in FALSEY:
        return False
    raise ValueError(f"{name} must be a boolean-ish value, got {value!r}")


def fetch(url: str, parse: Callable[[Any], Any]) -> Any:
    """
    GET url and hand the response to parse, retrying transient failures. A 4xx other
    than 429 fails at once.

    parse runs inside the retry so that a half-read or error page is retried rather
    than raised, which is why it takes the response instead of the caller reading it.
    """
    for attempt in range(1, RETRIES + 1):
        try:
            with urllib.request.urlopen(url, timeout=TIMEOUT_SECONDS) as resp:
                return parse(resp)
        except RETRYABLE as error:
            # A bad URL or a rejected request will not fix itself on a retry.
            permanent = (
                isinstance(error, urllib.error.HTTPError)
                and error.code != 429
                and error.code < 500
            )
            if permanent or attempt == RETRIES:
                raise
            wait = BACKOFF_SECONDS * attempt
            print(
                f"{url} failed ({error}); retry {attempt}/{RETRIES - 1} in {wait}s",
                file=sys.stderr,
            )
            time.sleep(wait)

    raise AssertionError("unreachable: the loop either returns or raises")


def fetch_json(url: str) -> dict:
    """GET JSON, retrying transient failures."""
    return fetch(url, json.load)


def parse_date(value: str) -> date:
    """Parse API date string (e.g. '2026-07-27 02:00:00+00:00') to date object."""
    return datetime.strptime(value.split(" ")[0], "%Y-%m-%d").date()


def get_ios_major() -> int:
    """The iOS major currently shipping, derived from the Nightly version."""
    nightly = fetch_json(SCHEDULE_URL)
    return int(nightly["version"].split(".")[0]) - NIGHTLY_OFFSET


def expected_version(major: int, today: date) -> str | None:
    """
    The version due to ship today, or None if no release is scheduled.

    release_N maps to {major}.N — release_0 for major 153 is 153.0, release_1 for major 153 is 153.1

    Dot releases shipped inbetween release_0 and release_1 use the pattern {major}.N.X, for example 153.0.1
    These versions are not covered by the slack notification automation since they are ad-hoc
    """
    schedule = fetch_json(IOS_SCHEDULE_URL.format(major))

    # Sorted numerically once, so the lookup and the log line cannot disagree:
    # a plain sort would order release_10 before release_2.
    releases = sorted(
        ((k, v) for k, v in schedule.items() if k.startswith("release_")),
        key=lambda kv: int(kv[0].split("_")[1]),
    )
    if not releases:
        print(f"No release_* milestones for iOS {major} — nothing scheduled.")
        return None

    for key, value in releases:
        if parse_date(value) == today:
            dot = key.split("_")[1]
            return f"{major}.{dot}"

    dates = ", ".join(f"{k}={v[:10]}" for k, v in releases)
    print(f"{today}: no iOS {major} release due today ({dates}).")
    return None


def live_release() -> tuple[str, datetime]:
    """The version currently live on the App Store, and when its rollout began."""
    data = fetch_json(ITUNES_URL.format(int(time.time())))
    if not data.get("results"):
        raise RuntimeError("iTunes lookup returned no results for Firefox iOS.")
    app = data["results"][0]
    rolled_out = datetime.strptime(
        app["currentVersionReleaseDate"], "%Y-%m-%dT%H:%M:%SZ"
    ).replace(tzinfo=timezone.utc)
    return app["version"], rolled_out


def parse_listing(resp: Any) -> tuple[str, datetime]:
    """Pull the version and rollout time out of the App Store page's JSON blob."""
    page = SERVER_DATA.search(resp.read().decode("utf-8", "replace"))
    if not page:
        raise AppStorePageError("no serialized-server-data on the App Store page")

    node = json.loads(page.group(1))
    try:
        for step in VERSION_ITEM:
            node = node[step]
        # The same value appears with and without the prefix elsewhere in the blob.
        version = node["primarySubtitle"].removeprefix("Version ").strip()
        when = node["secondarySubtitle"]
    except (LookupError, TypeError, AttributeError) as error:
        raise AppStorePageError(f"unexpected App Store page shape: {error}") from error

    stamp = JS_DATE.match(when)
    if not stamp:
        raise AppStorePageError(f"could not read a rollout date from {when!r}")
    return version, datetime.strptime(stamp.group(1), JS_DATE_FORMAT)


def live_release_from_page() -> tuple[str, datetime]:
    """As live_release, from the web listing. Cached for minutes rather than a day."""
    return fetch(APPS_URL, parse_listing)


def live_release_confirmed(expected: str) -> tuple[str, datetime, str | None]:
    """
    The live version and rollout time, cross-checked against the web listing whenever
    the lookup API disagrees with the schedule.

    The third element is None when the answer can be trusted, otherwise why it could
    not be confirmed. The page is only read on disagreement: it is a large response
    parsed out of an undocumented blob, so there is no sense paying for it to
    re-confirm an answer that already matches.
    """
    live, rolled_out = live_release()
    if live == expected:
        return live, rolled_out, None

    # Not evidence of a delay yet — the lookup API may just be serving a cached
    # pre-rollout answer. Ask the page, which goes stale in minutes, before anyone's
    # phone buzzes.
    print(f"Lookup API shows {live}, expected {expected} — checking the page.")
    try:
        page_live, page_rolled_out = live_release_from_page()
    except RETRYABLE as error:
        # Unreachable or reshaped, so we cannot tell a stale lookup API from a release
        # that really has not shipped. Hand back what the API said along with the
        # reason, so the alert can quote both.
        return live, rolled_out, str(error)

    print(f"The page shows {page_live} (rolled out {format_rollout(page_rolled_out)}).")
    return page_live, page_rolled_out, None


def format_rollout(when: datetime) -> str:
    """Render the rollout time for Slack, e.g. '02:11 UTC on 2026-07-27'."""
    return f"{when:%H:%M} UTC on {when:%Y-%m-%d}"


def post_to_slack(webhook_url: str, text: str) -> None:
    """Post a message to Slack via incoming webhook."""
    body = json.dumps({"text": text}).encode("utf-8")
    req = urllib.request.Request(
        webhook_url,
        data=body,
        headers={"Content-Type": "application/json"},
        method="POST",
    )
    with urllib.request.urlopen(req, timeout=15) as resp:
        if resp.status >= 300:
            raise RuntimeError(f"Slack webhook returned HTTP {resp.status}")


def alert_relman(webhook_url: str | None, text: str, dry_run: bool, done: str) -> bool:
    """Alert release management. False if the alert could not be delivered."""
    if dry_run:
        print(f"DRY RUN — would alert release management:\n{text}")
        return True
    if not webhook_url:
        print(
            "RELMAN_WEBHOOK_URL is not set — cannot alert release management.",
            file=sys.stderr,
        )
        return False
    post_to_slack(webhook_url, text)
    print(done)
    return True


def main() -> int:
    dry_run = env_flag("DRY_RUN")
    webhook_url = os.environ.get("SLACK_WEBHOOK_URL")
    relman_webhook_url = os.environ.get("RELMAN_WEBHOOK_URL")
    if not webhook_url and not dry_run:
        print("SLACK_WEBHOOK_URL is not set — nothing to do.", file=sys.stderr)
        return 1

    test_date = os.environ.get("TEST_DATE")
    if test_date:
        today = datetime.strptime(test_date, "%Y-%m-%d").date()
        print(f"TEST MODE: pretending today is {today}")
    else:
        # Explicitly UTC: release_* milestones are UTC and runners may not be.
        today = datetime.now(timezone.utc).date()

    major = get_ios_major()
    expected = expected_version(major, today)
    if expected is None:
        return 0

    live, rolled_out, unconfirmed = live_release_confirmed(expected)
    if unconfirmed:
        # Neither source could settle it. Say exactly that and let a human decide,
        # rather than going quiet on a release that was due today.
        print(
            f"{today}: could not confirm iOS {expected}: {unconfirmed}",
            file=sys.stderr,
        )
        alert_relman(
            relman_webhook_url,
            UNCONFIRMED_MESSAGE.format(
                expected=expected, live=live, reason=unconfirmed
            ),
            dry_run,
            f"Asked release management to check iOS {expected} manually.",
        )
        # Non-zero regardless: the check itself broke and wants fixing.
        return 1

    if live != expected:
        # Scheduled but not live: rollout delayed, pulled, or the version we
        # derived is wrong. Release management want to know either way.
        print(f"{today}: iOS {expected} was due today but the App Store shows {live}.")
        alert = MISMATCH_MESSAGE.format(
            expected=expected, live=live, rolled_out=format_rollout(rolled_out)
        )
        delivered = alert_relman(
            relman_webhook_url,
            alert,
            dry_run,
            f"Alerted release management that iOS {expected} has not shipped.",
        )
        return 0 if delivered else 1

    text = MESSAGE.format(version=live, rolled_out=format_rollout(rolled_out))
    if dry_run:
        print(f"DRY RUN — would post:\n{text}")
        return 0

    post_to_slack(webhook_url, text)
    print(f"{today}: posted release announcement for Firefox iOS {live}.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
