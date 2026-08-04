#!/usr/bin/env python3
"""
Posts a summary of the current regression status for each Firefox channel to Slack.

Run by .github/workflows/reo-regression-slack.yml. For Release, Beta and Nightly
it reports two bug lists, both taken from the REO tab of https://bugdash.moz.tools/:

- "new regressions" carry the regression keyword and are affected in version N
  while N-1 is unaffected or unknown, so they regressed during this cycle
- "carry over regressions" are the same query negated: N-1 has a real status, so
  the bug was already there

Those two partition every open regression affecting N. Each count links to a
Bugzilla list of exactly the bugs counted, and is broken down by severity, with
New Regressions also broken down by owning team. Beta and Nightly get a working
day countdown to the end of their cycle.

Nothing here needs Bugzilla credentials; every query is over public data.

Requires the SLACK_WEBHOOK_URL environment variable (set from the
REO_SLACK_WEBHOOK repo secret in the workflow).

Env vars for testing:
  DRY_RUN=1           print the message instead of posting it. Accepts
                      1/true/yes/on to enable and 0/false/no/off or empty to
                      disable; anything else is an error rather than a guess.
"""
import datetime
import functools
import json
import os
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

SCHEDULE_API_URL = "https://whattrainisitnow.com/api/release/schedule/?version={}"
RELEASE_PAGE_URL = "https://whattrainisitnow.com/release/?version={}"

WELLNESS_API_URL = "https://whattrainisitnow.com/api/wellness/days/"

BZ_REST_URL = "https://bugzilla.mozilla.org/rest/bug"
BZ_BUGLIST_URL = "https://bugzilla.mozilla.org/buglist.cgi"
BZ_PRODUCT_URL = (
    "https://bugzilla.mozilla.org/rest/product?type=accessible"
    "&include_fields=name,components.name,components.team_name"
)

# Every Bugzilla classification except Graveyard, which holds the ~100 retired
# products. Same list bugdash's REO queries use.
CLASSIFICATIONS = [
    "Client Software",
    "Components",
    "Developer Infrastructure",
    "Other",
    "Server Software",
]

# The severities we call out: the most serious ones, and the ones that mean no
# triage decision has been made yet. Bugs are filtered on these locally, so the
# values have to be exactly what Bugzilla reports in a bug's severity field.
HIGH_SEVERITIES = ("S1", "S2")
MISSING_SEVERITIES = ("--", "n/a")

HEADING = "REO release regression status:"

# Shown instead of dropping a channel entirely, so a silent channel reads as
# good news rather than as the script having failed.
NOTHING_TO_REPORT = "•  No open release regressions"

# For a component with no team_name, or one missing from the mapping entirely.
# Every component had a team when this was written, so this is only a guard
# against silently dropping bugs out of the per-team line.
UNKNOWN_TEAM = "Unknown team"

# A Slack section block holds at most 3000 characters.
SECTION_LIMIT = 3000

# Above this a snapshot URL is dropped in favour of the (fixed length) query
# URL, so that one very long bug list can't push a section over SECTION_LIMIT.
MAX_SNAPSHOT_URL = 2000

# Stands in for a milestone key, as the last beta is numbered differently from
# one version to the next (beta_10 for 154, beta_5 under the 2 week cadence).
LAST_BETA = "last_beta"

# The milestone that ends each channel's cycle, and the cycle's name. Both the
# countdown ("End of Beta ...") and the finished line ("Beta cycle finished") are
# built from that one name, so they can't drift apart. Release has no equivalent
# deadline, so it gets no countdown.
CYCLE_ENDS = {
    "beta": ("Beta", LAST_BETA),
    "nightly": ("Nightly", "merge_day"),
}

# Custom emoji in the Mozilla workspace, one per channel. A name that doesn't exist
# there renders as the literal :name: rather than failing, so these have to stay
# in step with the workspace.
CHANNEL_EMOJI = {
    "release": ":firefox-browser:",
    "beta": ":beta-browser:",
    "nightly": ":nightly-browser:",
}

# The job runs twice a week, so waiting out a blip is cheap next to losing the
# summary because whattrainisitnow or Bugzilla was briefly unavailable. Matches
# ios_release_slack_notifier.py.
TIMEOUT_SECONDS = 15
RETRIES = 3
BACKOFF_SECONDS = 5

# Bugzilla searches do real work, so they get longer than the small JSON APIs.
BZ_TIMEOUT_SECONDS = 60

# HTTPError subclasses URLError, so this covers HTTP errors, connection failures
# and timeouts. JSONDecodeError catches an error page served in place of JSON.
RETRYABLE = (urllib.error.URLError, TimeoutError, json.JSONDecodeError)

# Env vars are strings, and every non-empty string is truthy, so bool("0") is True.
# Spell the accepted values out rather than let DRY_RUN=false enable a dry run.
TRUTHY = frozenset({"1", "true", "yes", "on"})
FALSEY = frozenset({"", "0", "false", "no", "off"})

# Slack has no nested lists in message text, so indent sub-bullets by hand.
# Non-breaking spaces, as Slack collapses runs of regular ones.
SUB_BULLET = "    ◦ "


def env_flag(name: str) -> bool:
    """Parse a boolean env var. An unrecognised value is an error, not a guess."""
    value = os.environ.get(name, "").strip().lower()
    if value in TRUTHY:
        return True
    if value in FALSEY:
        return False
    raise ValueError(f"{name} must be a boolean-ish value, got {value!r}")


def fetch_json(url: str, timeout: int = TIMEOUT_SECONDS) -> dict | list:
    """GET JSON, retrying transient failures. A 4xx other than 429 fails at once."""
    for attempt in range(1, RETRIES + 1):
        try:
            with urllib.request.urlopen(url, timeout=timeout) as resp:
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


_schedules: dict[str, dict] = {}


def fetch_schedule(version: str) -> dict:
    """
    Fetch a version's milestone dates from the whattrainisitnow API.

    Cached under both the version asked for and the one it turned out to be, so
    that looking up "nightly" also answers a later lookup by its number.
    """
    if version in _schedules:
        return _schedules[version]

    schedule = fetch_json(SCHEDULE_API_URL.format(version))

    _schedules[version] = schedule
    _schedules[schedule["version"].split(".")[0]] = schedule

    return schedule


@functools.cache
def wellness_days() -> frozenset[datetime.date]:
    """Fetch the days off that don't count as working days."""
    return frozenset(
        datetime.date.fromisoformat(day) for day in fetch_json(WELLNESS_API_URL)
    )


def work_days_until(end: datetime.date) -> int:
    """
    Count working days between today and end, end excluded.

    Mirrors ReleaseInsights\\Duration::workDays() so this agrees with the
    countdowns on the release pages: weekends, wellness days and the current
    day are all left out.
    """
    today = datetime.date.today()
    days = (end - today).days
    if days <= 0:
        return 0

    # Counting from tomorrow is what leaves the current day out.
    return sum(
        1
        for offset in range(1, days)
        if (day := today + datetime.timedelta(days=offset)).weekday() < 5  # Mon-Fri
        and day not in wellness_days()
    )


def fetch_versions() -> dict[str, int]:
    """
    Return the current major version number for each channel.

    Only Nightly is fetched; Beta is Nightly - 1 and Release is Nightly - 2.
    """
    nightly = int(fetch_schedule("nightly")["version"].split(".")[0])

    return {
        "release": nightly - 2,
        "beta": nightly - 1,
        "nightly": nightly,
    }


def regressions_query(version: int, carry_over: bool = False) -> dict:
    """
    Build the "new regressions" or "carry over regressions" query for a version.

    Bugs with all of the following:
    - regression keyword
    - open (unresolved)
    - status-firefox{version} is affected
    - status-firefox{version - 1} is one of unaffected, ?, ---
    Bugs with any of the following are ignored:
    - tracking-firefox{version} is -
    - stalled or intermittent-failure keywords
    - within the Testing product

    With carry_over the status-firefox{version - 1} condition is negated, so the
    bug also affected the previous version instead of being new to this one. The
    two variants therefore partition every open regression affecting the version.

    Field numbering is Bugzilla's boolean charts: f/o/v are the field, operator
    and value for a numbered condition, OP and CP open and close a group, j sets
    how a group joins (OR here, AND otherwise) and n negates. The gap at f7 comes
    from bugdash and is harmless, as Bugzilla ignores unused numbers.
    """
    previous = version - 1
    query = {
        "classification": CLASSIFICATIONS,
        "keywords": "regression",
        "keywords_type": "allwords",
        "resolution": "---",
        "f1": f"cf_status_firefox{version}",
        "o1": "equals",
        "v1": "affected",
        "f2": "OP",
        "j2": "OR",
        "f3": f"cf_status_firefox{previous}",
        "o3": "equals",
        "v3": "unaffected",
        "f4": f"cf_status_firefox{previous}",
        "o4": "equals",
        "v4": "?",
        "f5": f"cf_status_firefox{previous}",
        "o5": "equals",
        "v5": "---",
        "f6": "CP",
        "f8": f"cf_tracking_firefox{version}",
        "o8": "notequals",
        "v8": "-",
        "f9": "product",
        "o9": "notequals",
        "v9": "Testing",
        "f10": "keywords",
        "o10": "nowordssubstr",
        "v10": "stalled,intermittent-failure",
    }

    if carry_over:
        # n2 attaches to the OP at f2, so it negates the whole f3-f5 group rather
        # than just the first condition in it.
        query["n2"] = "1"

    return query


def with_severities(query: dict, severities: tuple[str, ...]) -> dict:
    """
    Narrow a query to some severities, for a link that stays live.

    The counts themselves are filtered locally, so this is only needed to build a
    URL when a bug list is too long to link by id. Slot 11 is free because the
    REO queries stop at f10.
    """
    return {
        **query,
        "f11": "bug_severity",
        "o11": "anyexact",
        "v11": ", ".join(severities),
    }


@functools.cache
def component_teams() -> dict[tuple[str, str], str]:
    """
    Map every (product, component) to the team that owns it.

    team_name is a Bugzilla field on components, the same one bugdash's Teams
    filter uses. One request covers every product, around 120KB for 2000-odd
    components, which is why it's cached for the life of the run.
    """
    products = fetch_json(BZ_PRODUCT_URL, BZ_TIMEOUT_SECONDS)["products"]

    return {
        (product["name"], component["name"]): component.get("team_name") or UNKNOWN_TEAM
        for product in products
        for component in product.get("components", [])
    }


def team_of(bug: dict) -> str:
    """The team owning a bug's component."""
    return component_teams().get((bug["product"], bug["component"]), UNKNOWN_TEAM)


def fetch_bugs(query: dict) -> list[dict]:
    """
    Return the id, severity and component of every bug matching a query.

    Fetching the bugs rather than asking for count_only is what lets the severity
    and team breakdowns be derived from one request, and lets each count link to
    the exact bugs behind it. limit=0 lifts Bugzilla's default page size.
    """
    params = {
        **query,
        "include_fields": "id,severity,product,component",
        "limit": "0",
    }
    url = f"{BZ_REST_URL}?{urllib.parse.urlencode(params, doseq=True)}"
    return fetch_json(url, BZ_TIMEOUT_SECONDS)["bugs"]


def query_url(query: dict) -> str:
    """A Bugzilla URL that re-runs a query, so its results change over time."""
    return f"{BZ_BUGLIST_URL}?{urllib.parse.urlencode(query, doseq=True)}"


def snapshot_url(bugs: list[dict]) -> str:
    """
    A Bugzilla URL listing exactly these bugs, as bugdash's bug lists do.

    Linking the bug ids rather than the query means the list still matches the
    count in the message when it is read days later. order=bug_list keeps
    Bugzilla showing them in the order given rather than re-sorting.
    """
    ids = ",".join(str(bug["id"]) for bug in bugs)
    return f"{BZ_BUGLIST_URL}?bug_id={ids}&order=bug_list"


def bug_link(
    bugs: list[dict], label_template: str, fallback_query: dict | None = None
) -> str:
    """
    Format a non-empty bug list as a Slack link labelled with its count.

    label_template is formatted with the count, e.g. "{} New Regressions".
    If the snapshot URL comes out too long it is replaced by fallback_query, or
    left unlinked when there is no query for just these bugs. Team lines pass no
    fallback, as reproducing a team as a query means listing all its components.

    Callers are expected to skip empty lists: an empty bug_id would link to a
    broken list, and a count of zero is left out of the message anyway.
    """
    label = label_template.format(len(bugs))
    url = snapshot_url(bugs)

    if len(url) > MAX_SNAPSHOT_URL:
        if fallback_query is None:
            return label
        url = query_url(fallback_query)

    return f"<{url}|{label}>"


def milestone_date(schedule: dict, milestone: str) -> datetime.date:
    """
    The date of a milestone, resolving LAST_BETA to the highest numbered beta.

    The number of betas differs per version, so the last one has to be found
    rather than named. Sorting on the number matters: as strings, beta_9 would
    come after beta_10.
    """
    if milestone == LAST_BETA:
        betas = [key for key in schedule if re.fullmatch(r"beta_\d+", key)]
        milestone = max(betas, key=lambda key: int(key.removeprefix("beta_")))

    return datetime.date.fromisoformat(schedule[milestone].split(" ")[0])


def cycle_countdown(version: int, channel: str) -> str:
    """
    A countdown to the end of this version's time on the channel.

    Beta ends with the last beta build; Nightly ends on merge day, when the
    version moves to Beta. Release has no such deadline.

    The version numbers roll over on merge day, so the day of and the days after
    that deadline each only show up briefly, but they read badly as a countdown
    ("in 0 working days") and so get their own wording.
    """
    if channel not in CYCLE_ENDS:
        return ""

    cycle, milestone = CYCLE_ENDS[channel]
    label = f"End of {cycle}"
    end = milestone_date(fetch_schedule(str(version)), milestone)
    today = datetime.date.today()

    if end < today:
        return f"{cycle} cycle finished"

    if end == today:
        return f"{label} today"

    if end == today + datetime.timedelta(days=1):
        return f"{label} {end:%Y-%m-%d} — tomorrow"

    days = work_days_until(end)
    working_days = "1 working day" if days == 1 else f"{days} working days"

    return f"{label} {end:%Y-%m-%d} in {working_days}"


def team_breakdown(bugs: list[dict]) -> str:
    """
    Count the bugs owned by each team, busiest team first.

    Every team is listed rather than just the top few, so that the line works
    as a nudge to each team that owns something.
    """
    by_team: dict[str, list[dict]] = {}
    for bug in bugs:
        by_team.setdefault(team_of(bug), []).append(bug)

    ranked = sorted(by_team.items(), key=lambda item: (-len(item[1]), item[0]))

    return ", ".join(bug_link(team_bugs, f"{{}} {team}") for team, team_bugs in ranked)


def regression_group(
    version: int, carry_over: bool, label: str, by_team: bool = False
) -> str:
    """
    Build the bullet and severity sub-bullets for one bug list.

    The list is fetched once and split by severity and team here, rather than
    asking Bugzilla for each subset, so the sub-bullets are guaranteed to be
    part of the count above them.

    Bug lists that are empty are left out entirely rather than reported as a
    zero, so a quiet channel is short instead of a wall of "0". Returns an
    empty string when there are no bugs at all.
    """
    query = regressions_query(version, carry_over)
    bugs = fetch_bugs(query)
    if not bugs:
        return ""

    lines = [f"• {bug_link(bugs, f'{{}} {label} Regressions', query)}"]

    if by_team:
        lines.append(SUB_BULLET + team_breakdown(bugs))

    severity_counts = []
    for severities, template in (
        (HIGH_SEVERITIES, "{} S2+"),
        (MISSING_SEVERITIES, "{} missing severity"),
    ):
        subset = [bug for bug in bugs if bug["severity"] in severities]
        if subset:
            severity_counts.append(
                bug_link(subset, template, with_severities(query, severities))
            )

    if severity_counts:
        lines.append(SUB_BULLET + ", ".join(severity_counts))

    return "\n".join(lines)


def build_blocks(versions: dict[str, int]) -> list[dict]:
    """
    Build the Slack message as Block Kit sections, one per bug list.

    Slack silently splits a message whose text runs past about 4000 characters
    into several messages, which is what happened when every count linked to a
    full query URL. Snapshot URLs brought the total well under that, but each
    section block gets its own 3000 character allowance, so keeping the sections
    means a busier cycle can't start splitting the message again.

    A section that does overflow raises rather than posting something malformed.
    The team breakdown is the part that could get there, at roughly 90 characters
    per team; capping or splitting it is the fix if that ever fires.
    """
    sections = [HEADING]

    for channel in ("release", "beta", "nightly"):
        version = versions[channel]
        page = RELEASE_PAGE_URL.format(version)
        emoji = CHANNEL_EMOJI[channel]
        header = f"{emoji} *<{page}|Fx{version} {channel.title()}>*"

        countdown = cycle_countdown(version, channel)
        if countdown:
            header += f"\n{countdown}"

        groups = [
            group
            for group in (
                regression_group(version, False, "New", by_team=True),
                regression_group(version, True, "Carry Over"),
            )
            if group
        ]

        if not groups:
            sections.append(f"{header}\n{NOTHING_TO_REPORT}")
            continue

        # The header rides along with the first surviving group, so that a
        # channel with only carry over bugs isn't left with a stray heading.
        sections.append(f"{header}\n{groups[0]}")
        sections.extend(groups[1:])

    for section in sections:
        if len(section) > SECTION_LIMIT:
            raise RuntimeError(
                f"Slack section block is {len(section)} characters, over the "
                f"{SECTION_LIMIT} limit:\n{section[:200]}..."
            )

    return [
        {"type": "section", "text": {"type": "mrkdwn", "text": section}}
        for section in sections
    ]


def post_to_slack(webhook_url: str, blocks: list[dict]) -> None:
    """
    Post a message to Slack via incoming webhook.

    Not retried, unlike the reads: a POST that times out may well have arrived,
    so retrying risks posting the summary twice. A failure here fails the job
    instead, which is visible and harmless to repeat by hand.
    """
    # text is the notification/fallback for clients that can't render blocks.
    # Slack unfurls links by default: the release pages carry Open Graph tags, so
    # a heading link would add a large preview card below an already dense list.
    body = json.dumps(
        {
            "text": HEADING,
            "blocks": blocks,
            "unfurl_links": False,
            "unfurl_media": False,
        }
    ).encode("utf-8")
    req = urllib.request.Request(
        webhook_url,
        data=body,
        headers={"Content-Type": "application/json"},
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=15) as resp:
            answer = resp.read().decode().strip()
    except urllib.error.HTTPError as error:
        # The reason is in the body, e.g. "no_team" for a webhook that's gone.
        raise RuntimeError(
            f"Slack webhook returned HTTP {error.code}: {error.read().decode().strip()}"
        ) from error

    # A rejected payload still comes back as HTTP 200, with the reason (say
    # "invalid_blocks") in place of "ok", so the body is what has to be checked.
    if answer != "ok":
        raise RuntimeError(f"Slack webhook rejected the message: {answer}")


def main() -> int:
    dry_run = env_flag("DRY_RUN")
    webhook_url = os.environ.get("SLACK_WEBHOOK_URL")
    if not webhook_url and not dry_run:
        print("SLACK_WEBHOOK_URL is not set — nothing to do.", file=sys.stderr)
        return 1

    versions = fetch_versions()
    blocks = build_blocks(versions)

    if dry_run:
        print("DRY RUN: message not posted.\n")
        for block in blocks:
            print(block["text"]["text"])
        return 0

    post_to_slack(webhook_url, blocks)
    print(
        "Posted regression summary for Firefox "
        f"{versions['release']} / {versions['beta']} / {versions['nightly']}."
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
