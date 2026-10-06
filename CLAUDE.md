# Releases Insights (whattrainisitnow.com)

PHP 8.x app that computes and displays Firefox release schedules ("trains"),
milestones, ESR data and dashboards. No JS build step; templates are Twig.

Firefox moves to a 2-week release cadence from version 155; earlier versions
keep the legacy 4-week model. Both are computed in `Release.php` (see below).

## Commands

- Unit tests: `composer test:unit` (Pest) — or `vendor/bin/pest`
- Full check: `composer test:all` (phplint → twig-lint → Pest+coverage → PHPStan → content)
- Static analysis: `composer test:static` (PHPStan) · Lint: `composer test:lint`, `composer test:twig-lint`
- Clear caches: `composer cache:clear`

Tests run offline with `TESTING_CONTEXT` against fixtures in `tests/Files/`; the
train constants (`RELEASE`, `BETA`, `NIGHTLY`, `ESR`) are pinned in
`tests/bootstrap.php`. Update them when the fixtures are refreshed.

## Inspecting a schedule without a browser

The local web server renders "release date not yet available" for far-future
versions, so verify schedule logic in PHP directly (bootstrap defines the
constants the classes need):

```sh
php -r 'require "vendor/autoload.php"; require "tests/bootstrap.php";
  use ReleaseInsights\Release;
  foreach (new Release("162.0")->getSchedule() as $k => $v) {
    if ($k === "version") continue;
    printf("%-22s %s %s\n", $k, substr($v,0,10), (new DateTime($v))->format("D"));
  }'
```

## Where the schedule logic lives

- `app/classes/ReleaseInsights/Release.php`
  - `getSchedule()` → `getFutureSchedule()` (legacy 4-week, versions < 155) →
    `getTwoWeekSchedule()` (2-week cadence, 155+) → `getPastSchedule()` (shipped).
  - `getTwoWeekSchedule()`: Nightly cycle start chains from the previous version's
    merge day (`release(V-1) − 19`); merge day = nightly start + 2 weeks; betas are
    release-anchored. **Per-version overrides are hardcoded inline** — search for
    `$this->version->normalized ===`. Notable ones: **155** (transition: 4-week
    Nightly + 2-week Beta) and **163** (year-end: 2-week Nightly + stretched 5-week
    Beta, holiday build shutdown).
  - `getLabels()` — milestone labels (also used for calendar/iCal).
- `app/models/future_release.php` — computes the displayed **Nightly/Beta cycle
  lengths** from the schedule, on calendar days, floored to whole weeks.
- `app/classes/ReleaseInsights/Data.php` — release data; `getLastReleasesOfYear()`
  drives the end-of-year "beta milestones may be adjusted" notice.
- `app/data/upcoming_releases.php` — hand-maintained future major-release dates
  (all Tuesdays). Edit this when Release Management publishes new dates.
- `app/views/templates/future_release.html.twig` — milestone display
  (`cycle_labels` / `cycle_descriptions`). Every schedule key needs entries here
  **and** in `Release::getLabels()` — keep them in sync when adding/removing a milestone.
- iCal export: `app/classes/ReleaseInsights/ReleaseCalendar.php`.

## Schedule conventions (2-week cadence)

- Milestone dates are UTC strings formatted `Y-m-d H:i:sP`.
- Major releases ship on **Tuesdays**; betas are built **Monday/Wednesday/Friday**.
- `string_freeze` is the Wednesday before merge; `relnotes_deadline` and `rc_gtb`
  fall on the **same day** (Beta W2 Thursday). There is **no standalone
  "Release Candidate" milestone** — only `rc_gtb` (RC go-to-build).
- After any schedule change: `composer test:unit`, then re-dump the affected
  version(s) with the snippet above and check dates, weekdays and ordering.
  (There's a `verify-schedule` skill for exactly this.)

## Servers and deployment

The app must work on both **nginx** and **Apache**: keep `docker/nginx.conf` and
`public/.htaccess` in sync when changing routing. Security headers are sent by
PHP (`app/inc/router.php`, CSP in `app/inc/init.php`), not by the servers.

- Production: Docker image (`Dockerfile`), nginx + php-fpm (`docker/`).
- Internal demo: Heroku app `fx-trains` (https://fx-trains.herokuapp.com/),
  Apache via `heroku-php-apache2` (`Procfile`). Deploy with:

  ```sh
  git push heroku master:master
  ```

Only commit/push when asked.
