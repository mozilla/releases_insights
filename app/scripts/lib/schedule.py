"""The whattrainisitnow release schedule API: milestone dates and channel versions."""
import datetime

from .fetch import fetch_json

API_URL = "https://whattrainisitnow.com/api/release/schedule/?version={}"

_schedules: dict[str, dict] = {}


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
