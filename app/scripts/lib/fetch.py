"""GET a URL over HTTP, retrying the failures that are worth retrying."""
import json
import sys
import time
import urllib.error
import urllib.request
from collections.abc import Callable
from typing import Any

# These jobs run at most once a day, so waiting out a blip is cheap next to
# losing a notification because an API was briefly unavailable. Callers that hit
# a slower API (a Bugzilla search, say) pass a longer timeout.
TIMEOUT_SECONDS = 15
RETRIES = 3
BACKOFF_SECONDS = 5

# HTTPError subclasses URLError, so this covers HTTP errors, connection failures
# and timeouts. JSONDecodeError catches an error page served in place of JSON.
RETRYABLE = (urllib.error.URLError, TimeoutError, json.JSONDecodeError)


def fetch(
    url: str,
    parse: Callable[[Any], Any],
    timeout: int = TIMEOUT_SECONDS,
    retry_on: tuple[type[BaseException], ...] = (),
) -> Any:
    """
    GET url and hand the response to parse, retrying transient failures. A 4xx other
    than 429 fails at once.

    parse runs inside the retry so that a half-read or error page is retried rather
    than raised, which is why it takes the response instead of the caller reading it.
    retry_on adds caller-specific failures to RETRYABLE, for a parse that has its own
    way of saying "this response was not usable".
    """
    retryable = RETRYABLE + retry_on
    for attempt in range(1, RETRIES + 1):
        try:
            with urllib.request.urlopen(url, timeout=timeout) as resp:
                return parse(resp)
        except retryable as error:
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


def fetch_json(url: str, timeout: int = TIMEOUT_SECONDS) -> dict | list:
    """GET JSON, retrying transient failures."""
    return fetch(url, json.load, timeout)
