<?php

require __DIR__ . '/bootstrap.php';

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', fn() => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

expect()->extend('toBeNullOrInt', function () {
    expect(is_null($this->value) || is_int($this->value))->toBeTrue();

    return $this; // Allows expectation chaining
});

/*
    Milestones must never fall on a week-end. Throw our own exception rather
    than let PHPUnit append "Failed asserting that 6 is less than 6", which
    says nothing about which milestone is wrong.
*/
expect()->extend('toBeAWeekday', function (string $milestone = 'Date') {
    $date = new DateTime($this->value);

    if ((int) $date->format('N') > 5) {
        throw new PHPUnit\Framework\ExpectationFailedException(
            sprintf(
                '%s falls on a %s (%s)',
                $milestone,
                $date->format('l'),
                $date->format('Y-m-d')
            )
        );
    }

    // The expectation passed, register it so the test isn't reported as risky
    PHPUnit\Framework\Assert::assertTrue(true);

    return $this;
});
