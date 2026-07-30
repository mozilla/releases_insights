#!/usr/bin/env python3
"""
Checks the Firefox beta release schedule and posts Slack reminders on milestone days.

Run daily by .github/workflows/release-notes-slack.yml. No-ops on days that
don't match a milestone, so it's safe to run every day of the year.

Requires the SLACK_WEBHOOK_URL environment variable (set from the
RELEASE_NOTES_SLACK_WEBHOOK repo secret in the workflow).
"""
import json
import os
import sys
import urllib.request
from datetime import date, datetime

API_BASE_URL = "https://whattrainisitnow.com/api/release/schedule/?version={}"

MESSAGES = {
    "beta_1": (
        "We're at the start of the Fx{version} Beta cycle, which means it is time for a new release notes cycle. "
        "The draft template for the Firefox {version} Release Notes is <https://whattrainisitnow.com/release-notes|here>.\n\n"
        "The DEADLINE for submissions is {relnotes_deadline}. This will give us time to make necessary edits and/or "
        "changes before publishing on {release}.\n\n"
        "Note: We are still monitoring relnote nomination in bugzilla via setting relnote-firefox? for Fx{version}.\n\n"
        "Fx{version} beta preliminary release notes: https://www.mozilla.org/firefox/{version}.0beta/releasenotes/\n\n"
        "If you know of anything worth mentioning but is not yet listed, then please reach out. You can also add it "
        "in the document or nominate it for a release note in Bugzilla.\n\n"
        "If any changes require a Knowledge Base article update, please let the Sumo team know via a "
        "<https://bugzilla.mozilla.org/enter_bug.cgi?product=support.mozilla.org&component=Knowledge+Base+Content|request in bugzilla>"
    ),
    "beta_5": (
        "Hi! We're at the mid-point of the Fx{version} Beta cycle, which means it is time for a release notes reminder!\n\n"
        "Draft template for the Firefox {version} Release Notes are <https://whattrainisitnow.com/release-notes|here>.\n\n"
        "The DEADLINE for submissions is {relnotes_deadline}. This will give us time to make necessary edits and/or "
        "changes before publishing on {release}.\n\n"
        "Note: We are still monitoring relnote nomination in bugzilla via setting relnote-firefox? for Fx{version}."
    ),
    "relnotes_deadline": (
        "Today is the deadline for Fx{version} release note submissions.\n\n"
        "Tomorrow we will start adding them to the release notes system. If there are any delays or we need to be "
        "aware of anything, please let us know."
    ),
}


def parse_date(value: str) -> date:
    """Parse API date string (e.g. '2026-07-15 00:00:00+00:00') to date object."""
    return datetime.strptime(value.split(" ")[0], "%Y-%m-%d").date()


def fetch_schedule() -> dict:
    """
    Fetch the beta release schedule from the API.

    First fetches the current nightly version, then fetches the schedule
    for (nightly - 1) to get the beta version's schedule reliably.
    """
    # Get current nightly version
    nightly_url = API_BASE_URL.format("nightly")
    with urllib.request.urlopen(nightly_url, timeout=15) as resp:
        nightly_data = json.load(resp)

    # Calculate beta version (nightly - 1)
    nightly_version = int(nightly_data["version"].split(".")[0])
    beta_version = nightly_version - 1

    # Fetch the beta version's schedule
    beta_url = API_BASE_URL.format(beta_version)
    with urllib.request.urlopen(beta_url, timeout=15) as resp:
        return json.load(resp)


def determine_milestone(schedule: dict, today: date) -> tuple[str | None, dict]:
    """
    Check if today matches a milestone date.

    Returns:
        (milestone_name, format_data) or (None, format_data)
    """
    milestones = {
        "beta_1": parse_date(schedule["beta_1"]),
        "beta_5": parse_date(schedule["beta_5"]),
        "relnotes_deadline": parse_date(schedule["relnotes_deadline"]),
    }

    # Extract major version number (e.g. "155.0b1" -> "155")
    version = schedule["version"].split(".")[0]

    format_data = {
        "version": version,
        "relnotes_deadline": parse_date(schedule["relnotes_deadline"]).strftime("%B %d, %Y"),
        "release": parse_date(schedule["release"]).strftime("%B %d, %Y"),
    }

    for milestone_name, milestone_date in milestones.items():
        if today == milestone_date:
            return milestone_name, format_data

    return None, format_data


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
    webhook_url = os.environ.get("SLACK_WEBHOOK_URL")
    if not webhook_url:
        print("SLACK_WEBHOOK_URL is not set — nothing to do.", file=sys.stderr)
        return 1

    today = date.today()
    schedule = fetch_schedule()

    # Test mode: force a specific milestone via TEST_MILESTONE env var
    test_milestone = os.environ.get("TEST_MILESTONE")
    if test_milestone:
        if test_milestone not in MESSAGES:
            print(f"Invalid TEST_MILESTONE: {test_milestone}", file=sys.stderr)
            print(f"Valid options: {', '.join(MESSAGES.keys())}", file=sys.stderr)
            return 1
        print(f"TEST MODE: forcing '{test_milestone}' message")
        milestone = test_milestone
        _, format_data = determine_milestone(schedule, today)
    else:
        # Normal mode: check if today matches a milestone
        milestone, format_data = determine_milestone(schedule, today)
        if milestone is None:
            print(f"{today}: no milestone today. Nothing to post.")
            return 0

    text = MESSAGES[milestone].format(**format_data)
    post_to_slack(webhook_url, text)

    mode_label = "TEST MODE" if test_milestone else f"{today}"
    print(f"{mode_label}: posted '{milestone}' message for Firefox {format_data['version']}.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
