"""The whattrainisitnow release schedule API: milestone dates and channel versions."""
import datetime

from .fetch import fetch_json

API_URL = "https://whattrainisitnow.com/api/release/schedule/?version={}"
IOS_API_URL = "https://whattrainisitnow.com/api/release/schedule/ios/?version={}"

# Which majors carry the merge days worth knowing about: Release and Beta.
#
# A major's merge days all fall before it ships, so the next one belongs to Beta.
# Release is included because Nightly advances during merge day itself, which shifts
# what Beta means on exactly the day the answer has to be right. Since Release always
# trails Beta by one, the pair covers the same dates either side of that rollover.
IOS_MERGE_CHANNELS = ("release", "beta")

_schedules: dict[str, dict] = {}
_ios_schedules: dict[str, dict] = {}


def parse_date(value: str) -> datetime.date:
    """Parse an API date string (e.g. '2026-07-27 02:00:00+00:00') to a date."""
    return datetime.date.fromisoformat(value.split(" ")[0])


def today() -> datetime.date:
    """Today in UTC: milestone dates are UTC and the runners may not be."""
    return datetime.datetime.now(datetime.timezone.utc).date()


def fetch_schedule(version: str) -> dict:
    """
    Fetch a version's milestone dates from the whattrainisitnow API.

    Cached under both the version asked for and the one it turned out to be, so
    that looking up "nightly" also answers a later lookup by its number.
    """
    if version in _schedules:
        return _schedules[version]

    schedule = fetch_json(API_URL.format(version))

    _schedules[version] = schedule
    _schedules[schedule["version"].split(".")[0]] = schedule

    return schedule


def channel_versions() -> dict[str, int]:
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


def fetch_ios_schedule(major: int | str) -> dict:
    """Fetch a Firefox iOS major's milestone dates from the whattrainisitnow API."""
    key = str(major)
    if key not in _ios_schedules:
        _ios_schedules[key] = fetch_json(IOS_API_URL.format(key))

    return _ios_schedules[key]


def ios_merge_days() -> set[datetime.date]:
    """
    Every iOS merge day in the Release and Beta schedules. See IOS_MERGE_CHANNELS.

    A merge day is normally a Friday. One that lands on a Thursday has been moved
    back off a Friday that is a wellness day.
    """
    versions = channel_versions()
    days: set[datetime.date] = set()
    for channel in IOS_MERGE_CHANNELS:
        schedule = fetch_ios_schedule(versions[channel])
        days |= {
            parse_date(value)
            for key, value in schedule.items()
            if key.startswith("merge_day")
        }

    return days
