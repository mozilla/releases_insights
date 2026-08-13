"""Strict parsing of the environment variables the scripts are configured with."""
import os

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
