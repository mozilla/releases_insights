#!/usr/bin/env python3
"""
Reminds firefox-ios-dev about automated pull requests still open before we branch.

The l10n import and similar housekeeping PRs are opened by GitHub Actions and can sit
unreviewed. Anything still open when we branch misses the release, so this posts a
nudge while there is time to land them.

Run weekly by .github/workflows/ios-open-pr-slack.yml, on Thursday, the day before we
branch. Posts nothing when the queue is empty or when no merge day is imminent, so a
quiet week is silent rather than noisy.

Merge day is normally the Friday. When that Friday is a wellness day the merge moves
back onto the Thursday, so the message says "today" and gives the reason instead of
claiming a branch that is not happening tomorrow.

Requires the SLACK_WEBHOOK_URL environment variable (set from the
IOS_DEV_SLACK_WEBHOOK repo secret in the workflow).

Env vars for testing:
  DRY_RUN=1           print the message instead of posting it. Accepts
                      1/true/yes/on to enable and 0/false/no/off or empty to
                      disable; anything else is an error rather than a guess.
  TEST_DATE=Y-m-d     pretend today is this date, to exercise a merge day that
                      isn't today (e.g. 2026-08-27 for the wellness case)
"""
import os
import sys
from datetime import date, datetime, timedelta

from lib.env import env_flag
from lib.fetch import fetch_json
from lib.schedule import ios_merge_days, today as utc_today
from lib.slack import post_to_slack

REPO = "mozilla-mobile/firefox-ios"
# The GitHub Actions app, which opens the l10n import PRs. "app/" is how GitHub's
# search addresses an app rather than a user; the PRs show up as github-actions[bot].
AUTHOR = "app/github-actions"

SEARCH_URL = (
    "https://api.github.com/search/issues"
    f"?q=repo:{REPO}+is:pr+is:open+author:{AUTHOR}"
    "&sort=created&order=asc&per_page=100"
)

# When the branch happens, which is what makes the ask urgent. Merge day is normally
# the Friday after this Thursday run; a wellness day moves it back onto the Thursday,
# in which case say so, since "tomorrow" would be wrong and the reason is not obvious.
BRANCH_TOMORROW = "We'll be branching from main tomorrow for release."
BRANCH_TODAY_WELLNESS = (
    "We'll be branching from main today for release, as tomorrow is a wellness day."
)
BRANCH_TODAY = "We'll be branching from main today for release."

# One PR reads oddly with the plural ask, so it gets its own wording.
ASK_ONE = "There is an automated PR pending, could it be reviewed and merged?"
ASK_MANY = (
    "There are {count} automated PRs pending, could they be reviewed and merged?"
)
# The title carries the link, so the whole bullet is clickable. Slack has no list
# markdown — a literal bullet character is how you get a list.
LINE = "• <{url}|{title}>"


def open_prs() -> list[dict]:
    """The open PRs by AUTHOR, oldest first — the oldest has waited longest."""
    result = fetch_json(SEARCH_URL)
    if result.get("incomplete_results"):
        # A timed-out search returns a partial list, which would understate the queue.
        print("GitHub reported incomplete search results.", file=sys.stderr)
    return result["items"]


THURSDAY = 3


def branch_sentence(today: date) -> str | None:
    """
    How to describe the upcoming branch, or None when there isn't one to describe.

    None is the "no merge this week" case, and means nothing gets posted: the ask
    only makes sense when a branch is about to strand whatever is still open.
    """
    days = ios_merge_days()

    if today + timedelta(days=1) in days:
        return BRANCH_TOMORROW

    if today in days:
        # Merge days are Fridays. One on a Thursday has been moved back off the
        # Friday, and a wellness day is the reason that happens.
        if today.weekday() == THURSDAY:
            return BRANCH_TODAY_WELLNESS
        return BRANCH_TODAY

    return None


def build_message(prs: list[dict], branch: str) -> str:
    """The Slack message for a non-empty list of PRs."""
    ask = ASK_ONE if len(prs) == 1 else ASK_MANY.format(count=len(prs))
    lines = [f"{branch} {ask}"]
    lines += [
        LINE.format(url=pr["html_url"], title=pr["title"]) for pr in prs
    ]
    return "\n".join(lines)


def main() -> int:
    dry_run = env_flag("DRY_RUN")
    webhook_url = os.environ.get("SLACK_WEBHOOK_URL")
    if not webhook_url and not dry_run:
        print("SLACK_WEBHOOK_URL is not set — nothing to do.", file=sys.stderr)
        return 1

    test_date = os.environ.get("TEST_DATE")
    if test_date:
        today = datetime.strptime(test_date, "%Y-%m-%d").date()
        print(f"TEST MODE: pretending today is {today}")
    else:
        today = utc_today()

    # Check the schedule first: with no branch coming there is nothing to ask for,
    # however many PRs are open.
    branch = branch_sentence(today)
    if branch is None:
        print(f"{today}: no iOS merge day today or tomorrow. Nothing to post.")
        return 0

    prs = open_prs()
    if not prs:
        print(f"{today}: no open automated PRs in {REPO}. Nothing to post.")
        return 0

    text = build_message(prs, branch)

    if dry_run:
        print(f"DRY RUN — would post:\n{text}")
        return 0

    post_to_slack(webhook_url, text)
    print(f"{today}: posted a reminder about {len(prs)} open automated PR(s).")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
