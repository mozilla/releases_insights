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
import sys
import time
import urllib.error
import urllib.request
from datetime import date, datetime, timezone

SCHEDULE_URL = "https://whattrainisitnow.com/api/release/schedule/?version=nightly"
IOS_SCHEDULE_URL = "https://whattrainisitnow.com/api/release/schedule/ios/?version={}"
# Firefox iOS App Store listing
ITUNES_URL = "https://itunes.apple.com/lookup?id=989804926&country=us"

MESSAGE = "Firefox iOS v{version} is live with a phased rollout as of {rolled_out}"

MISMATCH_MESSAGE = (
    "Firefox iOS v{expected} was scheduled to roll out today, but the App Store is "
    "showing v{live} (live since {rolled_out}). Investigate why v{expected} is not live."
)

# The shipping iOS major is behind the current Nightly by two versions. Desktop merge day is a
# Thursday, so by the Monday rollout Nightly has always moved on.
NIGHTLY_OFFSET = 2

# The job runs once a week, so a few seconds of waiting is cheap next to missing
# an announcement because whattrainisitnow or the iTunes API blipped.
TIMEOUT_SECONDS = 15
RETRIES = 3
BACKOFF_SECONDS = 5

# HTTPError subclasses URLError, so this covers HTTP errors, connection failures
# and timeouts. JSONDecodeError catches an error page served in place of JSON.
RETRYABLE = (urllib.error.URLError, TimeoutError, json.JSONDecodeError)

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


def fetch_json(url: str) -> dict:
    """GET JSON, retrying transient failures. A 4xx other than 429 fails at once."""
    for attempt in range(1, RETRIES + 1):
        try:
            with urllib.request.urlopen(url, timeout=TIMEOUT_SECONDS) as resp:
                return json.load(resp)
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
    data = fetch_json(ITUNES_URL)
    if not data.get("results"):
        raise RuntimeError("iTunes lookup returned no results for Firefox iOS.")
    app = data["results"][0]
    rolled_out = datetime.strptime(
        app["currentVersionReleaseDate"], "%Y-%m-%dT%H:%M:%SZ"
    ).replace(tzinfo=timezone.utc)
    return app["version"], rolled_out


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

    live, rolled_out = live_release()
    if live != expected:
        # Scheduled but not live: rollout delayed, pulled, or the version we
        # derived is wrong. Release management want to know either way.
        print(f"{today}: iOS {expected} was due today but the App Store shows {live}.")
        alert = MISMATCH_MESSAGE.format(
            expected=expected, live=live, rolled_out=format_rollout(rolled_out)
        )
        if dry_run:
            print(f"DRY RUN — would alert release management:\n{alert}")
            return 0
        if not relman_webhook_url:
            print(
                "RELMAN_WEBHOOK_URL is not set — cannot alert release management.",
                file=sys.stderr,
            )
            return 1
        post_to_slack(relman_webhook_url, alert)
        print(f"Alerted release management that iOS {expected} has not shipped.")
        return 0

    text = MESSAGE.format(version=live, rolled_out=format_rollout(rolled_out))
    if dry_run:
        print(f"DRY RUN — would post:\n{text}")
        return 0

    post_to_slack(webhook_url, text)
    print(f"{today}: posted release announcement for Firefox iOS {live}.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
