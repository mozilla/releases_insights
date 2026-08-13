"""
Posting messages to Slack, either way round.

There are two ways to reach a channel, and post_to_slack takes whichever the caller
has:

  webhook URL   The original route. A webhook is created against one channel and is
                permanently bound to it, so the URL *is* the destination. It is a
                secret, so it arrives from a repo secret via the environment. Slack
                answers with a bare "ok" and no message ID, which is why threading is
                not possible this way.

  channel ID    A "C…" string — the last section of a channel's 'copy link' URL, or
                "D…" for a DM. Goes through the chat.postMessage Web API, which needs
                a bot token carrying chat:write, and the bot has to be in the channel
                unless the token also has chat:write.public. A channel ID is not a
                secret and can live in a workflow's env: block or a constant; the
                token is, and has to be a repo secret. Returns the message ID, so a
                follow-up can reply in thread.

Every current caller passes a webhook URL and none of the channel-ID machinery
applies to them. A new notifier that wants a channel passes an ID instead — same
function, same arguments, nothing else changes.

SLACK_API_URL, SLACK_ACCESS_TOKEN and the error wording follow taskcluster's notify
service (services/notify), which solves the same problem. SLACK_API_URL exists to
point at a test server, which is the only way to exercise the channel path without a
real token.
"""
import json
import os
import urllib.error
import urllib.request

TIMEOUT_SECONDS = 15

DEFAULT_API_URL = "https://slack.com/api/"
API_URL_VAR = "SLACK_API_URL"
TOKEN_VAR = "SLACK_ACCESS_TOKEN"


def post_to_slack(
    target: str,
    text: str,
    blocks: list[dict] | None = None,
    thread_ts: str | None = None,
    token: str | None = None,
) -> str | None:
    """
    Post a message to Slack, by webhook URL or by channel ID. See the module
    docstring for what each route needs.

    target is dispatched on: anything starting with https:// is a webhook URL, and
    anything else is treated as a channel ID. That is what lets a caller switch
    between the two without changing how it calls this.

    token overrides SLACK_ACCESS_TOKEN, and is only read on the channel-ID route.

    text is always sent: on a blocks message it is the notification and the
    fallback for clients that can't render blocks.

    Link previews are always suppressed. These messages are notifications built
    around their links, and an unfurl below one repeats what the message already
    says at several times the height.

    Returns the message timestamp on the channel-ID route, which is what thread_ts
    wants, and None on the webhook route, which has no ID to give.

    Not retried, unlike the reads: a POST that times out may well have arrived,
    so retrying risks posting the message twice. A failure here fails the job
    instead, which is visible and harmless to repeat by hand.
    """
    payload: dict = {
        "text": text,
        "unfurl_links": False,
        "unfurl_media": False,
    }
    if blocks is not None:
        payload["blocks"] = blocks

    # Route 1: webhook URL. The destination is the URL, so there is nothing to add
    # to the payload and no token involved.
    if target.startswith("https://"):
        if thread_ts is not None:
            raise ValueError(
                "thread_ts needs a channel ID: a webhook cannot reply in a thread"
            )
        # A rejected payload still comes back as HTTP 200, with the reason (say
        # "invalid_blocks") in place of "ok", so the body is what has to be checked.
        answer = _post(target, payload)
        if answer != "ok":
            raise RuntimeError(f"Slack webhook rejected the message: {answer}")
        return None

    # Route 2: channel ID. The destination goes in the payload, and the token in the
    # Authorization header.
    payload["channel"] = target
    if thread_ts is not None:
        payload["thread_ts"] = thread_ts

    bot_token = token or os.environ.get(TOKEN_VAR, "").strip()
    if not bot_token:
        # These are cron jobs whose whole purpose is the message,
        # so a missing token has to stop the run and be seen.
        raise RuntimeError(
            f"posting to channel {target} needs {TOKEN_VAR}, a bot token with the "
            "chat:write scope (chat:write.public to post without being invited)"
        )

    api_url = (os.environ.get(API_URL_VAR) or DEFAULT_API_URL).rstrip("/")

    # chat.postMessage reports application errors as HTTP 200 with ok=false, so the
    # body is what has to be checked here too, just in a different shape.
    result = json.loads(_post(f"{api_url}/chat.postMessage", payload, bot_token))
    if not result.get("ok"):
        reason = result.get("error", result)
        # On missing_scope Slack names the scope it wanted and the ones the token
        # carries. Without those two the error is very hard to act on.
        if result.get("needed"):
            reason += f" (needed {result['needed']}, token has {result['provided']})"
        raise RuntimeError(f"error posting slack message: {reason}")
    return result["ts"]


def _post(url: str, payload: dict, bot_token: str | None = None) -> str:
    """POST the payload as JSON and return the response body."""
    # Slack answers a bare application/json with a missing_charset warning.
    headers = {"Content-Type": "application/json; charset=utf-8"}
    if bot_token:
        headers["Authorization"] = f"Bearer {bot_token}"

    req = urllib.request.Request(
        url,
        data=json.dumps(payload).encode("utf-8"),
        headers=headers,
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=TIMEOUT_SECONDS) as resp:
            return resp.read().decode().strip()
    except urllib.error.HTTPError as error:
        # The reason is in the body, e.g. "no_team" for a webhook that's gone.
        raise RuntimeError(
            f"Slack returned HTTP {error.code}: {error.read().decode().strip()}"
        ) from error
