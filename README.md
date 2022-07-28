[![PHP Composer & CI](https://github.com/pascalchevrel/releases_insights/actions/workflows/php.yml/badge.svg)](https://github.com/pascalchevrel/releases_insights/actions/workflows/php.yml)
# Release Insights / Firefox Trains Dashboard

This is the code behind https://fx-trains.herokuapp.com/

This application gives the status of Firefox Destop Releases: when do we ship the next release, what are the upcoming milestones for our future Firefox releases, what are the main data points for our past releases.

There are different views and I also added a public JSON API, these endpoints are documented here:

https://fx-trains.herokuapp.com/about/

### Requirements:

The requirements are very simple, no database, no framework.

- Linux (should work fine on macOS and Windows too, but I didn't test it)

- PHP >=8.1 with the `ext-mbstring`, `ext-intl`, `ext-curl`, `ext-dom` extensions

- [Composer](https://getcomposer.org/) to install dependencies

The application is set up to be deployed on Heroku with Apache but there is no need to install Apache for development work, the PHP built-in development server is fine for that purpose.

### Installation

1. Clone this repository
2. Install dependencies: `composer install`
3. Start the PHP development server in a terminal either by launching the `run` bash script or with this command:<br>
  `php -S localhost:8082 -t public/`

The website will be available at http://localhost:8082

If you have intalled the npm package [browser-sync](https://browsersync.io/) (`sudo npm install -g browser-sync`), the `run` script will detect it and launch automatically the website at http://localhost:3000 and any change to a file in the repository will automatically refresh the page in the browser.

If you have browser-sync installed but don't want to launch it, you can use the `run -no-reload` command.

If you want to set the site up with an Apache virtual host, make it point to the `public` folder and make sure that the `cache` folder is writable by Apache.

### Running with Docker

It's possible to use Docker to run in a containerised environment.

Build or update the image using:
```
docker build -t fx-trains .
```
and run it with
```
docker run --rm -p 8000:8000 fx-trains
```

The image is configured to listen on port 8000.

#### Dockerflow

[Dockerflow](https://github.com/mozilla-services/Dockerflow) is supported; with `version.json` optionally generated from build-time variables:

```bash
docker build . -t fx-trains \
  --build-arg source=https://github.com/pascalchevrel/releases_insights \
  --build-arg version= \
  --build-arg commit=$( git rev-parse HEAD ) \
  --build-arg build=
```

## Testing and CI

We use [Pest](https://pestphp.com/Pest) for unit testing, [PHPStan](https://phpstan.org/) for static analysis and custom scripts for basic functional scripts. We have CI via Github Actions to ensure all tests are passing.

All tests can be launched via Composer action scripts:

```bash
composer test:all       # Run all tests except mutation tests
composer test:api       # Run functional tests of external JSON API points
composer test:content   # Run functional tests of pages + external JSON API points
composer test:coverage  # Run unit tests, only display coverage (requires Xdebug)
composer test:lint      # Run linter to ensure all PHP files are valid
composer test:mutation  # Run Infection mutation tests (requires Xdebug)
composer test:pages     # Run functional tests of all pages
composer test:static    # Run PHPStan static analysis
composer test:unit      # Run unit tests
composer test:unitcov   # Run unit tests + code coverage (requires Xdebug)

```

You can also run locally all the tests we run in CI with the `run -tests` command.

If you want to contribute a patch to an existing class, please make sure that unit tests pass. If there is no unit test yet for the method you are modifying, please add one thanks.

## Bugs
> **Warning**
If you find a bug, please open an issue.

If the application is malfunctionning, the solution is to either flush the cache folder or to restart it as the restart flushes the cache. The most likely cause is that one of the remote source of data was down and the data fetch is missing.

## Production playbook

This application relies on external data from mozilla architecture.

If these external sources are unavailable or sending malformed data, they might cause application bugs or even 500 errors.

List of external sources that the app is pulling data from:
- https://product-details.mozilla.org/1.0/
- https://hg.mozilla.org/mozilla-central/json-pushes
- https://hg.mozilla.org/releases/mozilla-release/json-pushes
- https://hg.mozilla.org/releases/mozilla-beta/json-pushes
- https://buildhub.moz.tools/api/search
- https://crash-stats.mozilla.com/api/SuperSearch/
- https://bugzilla.mozilla.org/rest/
- https://aus-api.mozilla.org/api/v1/

Emptying the mutable data in the cache folder (either via the `composer cache:reset` command or by doing `rm cache/*.cache`) should fix any issue caused by external data sources listed above being unavailable and/or providing bogus data.

Restarting the app should solve most problems on production.

If the app is slow, this is most likely because the app can't write to the `cache` folder and makes http requests to external servers for every page load. Make sure that the `cache` folder is writable by the web server.


If there is a dependabot PR for a security vulnerability on a single dependency, it's preferable to update all dependencies and redeploy than to merge this single PR:

```bash
composer update
git add composer.lock
git commit -m "Dependencies update"
git push
````

The dependabot PR will autoclose.