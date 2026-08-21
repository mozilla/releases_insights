#!/usr/bin/env python3
"""
Announces the Fenix beta cut in #mobile-android-team on desktop merge day.

Run daily by .github/workflows/fenix-beta-cut-slack.yml. It reads the current
Nightly's schedule and posts only when today is that version's merge day, so it is
safe to run every day of the year.

The run is deliberately scheduled ahead of the merge. The version bump
lands during merge day itself, and once it has, "nightly" answers with the next
major, whose merge day is a fortnight away. Asking before the merge gets the version
that is merging today.

Requires the SLACK_WEBHOOK_URL environment variable (set from the
MOBILE_ANDROID_TEAM_SLACK_WEBHOOK repo secret in the workflow, named for the
#mobile-android-team channel it posts to).

Env vars for testing:
  DRY_RUN=1           print the message instead of posting it. Accepts
                      1/true/yes/on to enable and 0/false/no/off or empty to
                      disable; anything else is an error rather than a guess.
  TEST_DATE=Y-m-d     pretend today is this date, to exercise a merge day that
                      isn't today (e.g. 2026-08-27)
"""
import os
import sys
from datetime import datetime

from lib.env import env_flag
from lib.schedule import fetch_schedule, parse_date, today as utc_today
from lib.slack import post_to_slack

# Slack has no list markdown — a literal bullet character is how you get a list.
MESSAGE = (
    "*Fenix Beta Cut Time*\n"
    "Today is the <https://whattrainisitnow.com/release/?version={version}|FX{version}> "
    "merge day. Later today, release management will merge main to beta, and then bump main to "
    "{next_version}.\n"
    "• Who's responsible? Check <https://www.whoisdoingwhat.dev/|here> to see which "
    "squad is assigned.\n"
    "• Taking on the starting the nightly Fx{next_version} development cycle? Please "
    "leave a message here to let others know.\n"
    "• Need instructions? Find them <https://firefox-source-docs.mozilla.org/mobile/"
    "android/fenix/release-checklist.html|here>."
)


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

    schedule = fetch_schedule("nightly")
    merge_day = parse_date(schedule["merge_day"])
    if today != merge_day:
        print(f"{today}: not merge day (next one is {merge_day}). Nothing to post.")
        return 0

    # "156.0" -> 156: the message talks about the major, not the build.
    version = int(schedule["version"].split(".")[0])
    text = MESSAGE.format(version=version, next_version=version + 1)

    if dry_run:
        print(f"DRY RUN — would post:\n{text}")
        return 0

    post_to_slack(webhook_url, text)
    print(f"{today}: posted the Fenix beta cut announcement for Firefox {version}.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
