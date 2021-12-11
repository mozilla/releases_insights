# Release Insights / Firefox Trains Dashboard

This is the code behind https://fx-trains.herokuapp.com/

This application gives the status of Firefox Destop Releases: when do we ship the next release, what are the upcoming milestones for our future Firefox releases, what are the main data points for our past releases.

There are different views and I also added a public JSON API, these endpoints are documented here:

https://fx-trains.herokuapp.com/about/

### Requirements:

The requirements are very simple, no Database, no heavy framework.

- Linux (should work fine on macOS and Windows too, but I didn't test it)

- PHP 8 with the `ext-mbstring`, `ext-intl`, `ext-curl`, `ext-dom` extensions

- [Composer](https://getcomposer.org/) to install dependencies

The application is set up to be deployed on Heroku with Apache but there is no need to install Apache for development work, the PHP built-in development server is fine for that purpose.

### Installation

1. Clone this repository
2. install dependencies: `composer install`
3. start PHP development server in a terminal either by launching the `starts.sh` script or with this command:<br>
  `php -S localhost:8082 -t public/ app/inc/router.php`


The website will be available at http://localhost:8082

If you want to set the site up with an Apache virtual host, make it point to the `public` folder and make sure that the `cache` folder is writable by Apache.

## Testing and CI

We use [Pest](https://pestphp.com/Pest) for unit testing and we have CI via Github Actions to ensure all tests are passing.

If you want to contribute a patch to an existing class, please make sure that unit tests pass. If there is no unit test yet for the method you are modifying, please add one thanks.
